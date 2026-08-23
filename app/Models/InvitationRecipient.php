<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InvitationRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'category_id',
        'participant_id',
        'salutation',
        'name',
        'display_name',
        'email',
        'phone',
        'context_note',
        'token',
        'rsvp_status',
        'rsvp_note',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $recipient): void {
            if (! $recipient->token) {
                $recipient->token = static::newToken();
            }
        });

        static::saving(function (self $recipient): void {
            $recipient->display_name = $recipient->name;
        });
    }

    public function period()
    {
        return $this->belongsTo(YudisiumPeriod::class, 'period_id');
    }

    public function category()
    {
        return $this->belongsTo(InvitationCategory::class, 'category_id');
    }

    public function participant()
    {
        return $this->belongsTo(YudisiumParticipant::class, 'participant_id');
    }

    public function submitRsvp(string $status, ?string $note = null): void
    {
        $this->forceFill([
            'rsvp_status' => $status,
            'rsvp_note' => $note,
            'responded_at' => now(),
        ])->save();
    }

    public function getInvitationNameAttribute(): string
    {
        return trim(collect([$this->salutation, $this->name])
            ->filter()
            ->implode(' '));
    }

    private static function newToken(): string
    {
        do {
            $token = Str::lower(Str::random(24));
        } while (static::query()->where('token', $token)->exists());

        return $token;
    }
}
