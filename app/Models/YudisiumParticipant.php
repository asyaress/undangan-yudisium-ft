<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class YudisiumParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'sequence_number',
        'study_program_id',
        'nim',
        'name',
        'birth_date',
        'study_program',
        'faculty',
        'email',
        'phone',
        'invitation_token',
        'checkin_status',
        'checked_in_at',
        'checkin_source',
        'rsvp_status',
        'rsvp_note',
        'rsvp_companion_count',
        'rsvp_whatsapp',
        'rsvp_proof_code',
        'rsvp_responded_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'rsvp_responded_at' => 'datetime',
            'sequence_number' => 'integer',
            'birth_date' => 'date',
            'rsvp_companion_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $participant): void {
            if (! $participant->invitation_token) {
                $participant->invitation_token = static::newToken();
            }
        });
    }

    public function period()
    {
        return $this->belongsTo(YudisiumPeriod::class, 'period_id');
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }

    public function recipient()
    {
        return $this->hasOne(InvitationRecipient::class, 'participant_id');
    }

    public function checkinLogs()
    {
        return $this->hasMany(CheckinLog::class, 'participant_id');
    }

    public function markCheckedIn(string $source = 'web'): void
    {
        if ($this->checked_in_at) {
            return;
        }

        $this->forceFill([
            'checkin_status' => 'checked_in',
            'checked_in_at' => now(),
            'checkin_source' => $source,
        ])->save();
    }

    public function submitRsvp(
        string $status,
        ?string $note = null,
        ?int $companionCount = null,
        ?string $whatsapp = null
    ): void
    {
        $this->forceFill([
            'rsvp_status' => $status,
            'rsvp_note' => $note,
            'rsvp_companion_count' => $companionCount,
            'rsvp_whatsapp' => $whatsapp,
            'rsvp_proof_code' => $this->rsvp_proof_code ?: static::newProofCode(),
            'rsvp_responded_at' => now(),
        ])->save();
    }

    private static function newToken(): string
    {
        do {
            $token = Str::lower(Str::random(24));
        } while (static::query()->where('invitation_token', $token)->exists());

        return $token;
    }

    private static function newProofCode(): string
    {
        do {
            $code = 'YUD-'.Str::upper(Str::random(10));
        } while (static::query()->where('rsvp_proof_code', $code)->exists());

        return $code;
    }
}
