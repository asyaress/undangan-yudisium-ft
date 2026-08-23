<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class YudisiumPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'event_year',
        'cohort_label',
        'period_label',
        'event_date',
        'event_time',
        'location',
        'address',
        'agenda_items',
        'event_notes',
        'signature_city',
        'signer_name',
        'signer_title',
        'is_active',
        'rsvp_deadline',
        'checkin_opens_at',
        'checkin_closes_at',
        'checkin_latitude',
        'checkin_longitude',
        'checkin_radius_meter',
        'checkin_location_required',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'agenda_items' => 'array',
            'event_notes' => 'array',
            'is_active' => 'boolean',
            'rsvp_deadline' => 'datetime',
            'checkin_opens_at' => 'datetime',
            'checkin_closes_at' => 'datetime',
            'checkin_latitude' => 'decimal:7',
            'checkin_longitude' => 'decimal:7',
            'checkin_radius_meter' => 'integer',
            'checkin_location_required' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $period): void {
            if (! $period->slug) {
                $period->slug = static::uniqueSlug($period->name ?: 'yudisium');
            }
        });
    }

    public function participants()
    {
        return $this->hasMany(YudisiumParticipant::class, 'period_id');
    }

    public function recipients()
    {
        return $this->hasMany(InvitationRecipient::class, 'period_id');
    }

    public function categories()
    {
        return $this->hasMany(InvitationCategory::class, 'period_id');
    }

    public function checkinLogs()
    {
        return $this->hasMany(CheckinLog::class, 'period_id');
    }

    public function getArchiveTitleAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        }

        $segments = array_filter([
            $this->event_year ? 'Yudisium '.$this->event_year : 'Yudisium',
            $this->cohort_label,
            $this->period_label,
        ]);

        return implode(' ', $segments);
    }

    public function getArchiveSubtitleAttribute(): string
    {
        $segments = array_filter([
            $this->event_date?->translatedFormat('d F Y'),
            $this->location,
        ]);

        return $segments ? implode(' - ', $segments) : 'Informasi acara akan diumumkan pada undangan.';
    }

    public function getArchiveStartsAtAttribute(): ?Carbon
    {
        if (! $this->event_date) {
            return null;
        }

        $startsAt = $this->event_date->copy()->startOfDay();

        if (! $this->event_time) {
            return $startsAt;
        }

        if (preg_match('/(\d{1,2})[.:](\d{2})/', $this->event_time, $matches)) {
            $startsAt->setTime((int) $matches[1], (int) $matches[2]);
            return $startsAt;
        }

        return $startsAt;
    }

    public function getArchiveStatusAttribute(): string
    {
        $startsAt = $this->archive_starts_at;

        if (! $startsAt) {
            return 'Jadwal belum diatur';
        }

        return now()->lessThan($startsAt) ? 'Belum mulai' : 'Tersimpan';
    }

    public function isArchiveUpcoming(): bool
    {
        $startsAt = $this->archive_starts_at;

        return $startsAt !== null && now()->lessThan($startsAt);
    }

    public function rsvpIsClosed(): bool
    {
        return $this->rsvp_deadline !== null && now()->greaterThan($this->rsvp_deadline);
    }

    public function checkinStatus(): string
    {
        $now = now();

        if ($this->checkin_opens_at && $now->lessThan($this->checkin_opens_at)) {
            return 'not_open';
        }

        if ($this->checkin_closes_at && $now->greaterThan($this->checkin_closes_at)) {
            return 'closed';
        }

        return 'open';
    }

    public function checkinIsOpen(): bool
    {
        return $this->checkinStatus() === 'open';
    }

    public function hasCheckinCoordinate(): bool
    {
        return $this->checkin_latitude !== null && $this->checkin_longitude !== null;
    }

    public function getAgendaListAttribute(): array
    {
        return $this->normalizeAgendaItems($this->agenda_items ?: $this->defaultAgendaItems());
    }

    public function getEventNoteListAttribute(): array
    {
        return $this->event_notes ?: [
            'Hadir 30 menit sebelum acara dimulai.',
            'Pakaian PSL untuk dosen dan karyawan, serta menyesuaikan ketentuan panitia bagi peserta lainnya.',
        ];
    }

    private static function uniqueSlug(string $value): string
    {
        $base = Str::slug($value);
        $slug = $base !== '' ? $base : 'yudisium';
        $suffix = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = ($base !== '' ? $base : 'yudisium').'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function defaultAgendaItems(): array
    {
        return [
            ['title' => 'Pembukaan'],
            ['title' => 'Lagu Kebangsaan Indonesia Raya dan Mars Fakultas Teknik'],
            ['title' => 'Mengheningkan Cipta'],
            ['title' => "Pembacaan Ayat Suci Al-Qur'an"],
            ['title' => 'Pembacaan Doa'],
            ['title' => 'Laporan Wakil Dekan Bidang Akademik Fakultas Teknik Universitas Mulawarman'],
            [
                'title' => 'Prosesi Yudisium',
                'children' => [
                    'Pembacaan SK Yudisium',
                    'Penyerahan Medali dan Sertifikat Yudisium',
                    'Penyerahan Sertifikat untuk lulusan Terbaik Fakultas',
                    'Pembacaan Naskah Yudisium',
                    'Lagu Bagimu Negeri',
                ],
            ],
            ['title' => 'Sambutan oleh Wakil Mahasiswa'],
            ['title' => 'Sambutan Dekan Fakultas Teknik'],
            ['title' => 'Foto Bersama'],
        ];
    }

    private function normalizeAgendaItems(array $items): array
    {
        $agenda = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $title = trim((string) ($item['title'] ?? $item['name'] ?? $item['label'] ?? ''));
                $children = $this->normalizeAgendaChildren($item['children'] ?? $item['items'] ?? []);

                if ($title !== '') {
                    $agenda[] = [
                        'title' => $title,
                        'children' => $children,
                    ];
                }

                continue;
            }

            $line = trim((string) $item);

            if ($line === '') {
                continue;
            }

            $childTitle = $this->extractAgendaChildTitle($line);
            if ($childTitle !== null && $agenda !== []) {
                $lastIndex = array_key_last($agenda);
                $agenda[$lastIndex]['children'][] = $childTitle;

                continue;
            }

            $agenda[] = [
                'title' => preg_replace('/^\d+\s*[\.\)]\s*/', '', $line),
                'children' => [],
            ];
        }

        return $agenda;
    }

    private function normalizeAgendaChildren(array $children): array
    {
        return collect($children)
            ->map(fn ($child) => trim((string) (is_array($child) ? ($child['title'] ?? $child['name'] ?? $child['label'] ?? '') : $child)))
            ->filter()
            ->values()
            ->all();
    }

    private function extractAgendaChildTitle(string $line): ?string
    {
        if (preg_match('/^(?:[-*]\s+|[a-z]\s*[\.\)]\s+)(.+)$/i', $line, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
