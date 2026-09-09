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
        'identifier',
        'position',
        'token',
        'rsvp_status',
        'rsvp_note',
        'rsvp_signature',
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

        static::created(function (self $recipient): void {
            if ($recipient->roles()->exists()) {
                return;
            }

            $recipient->roles()->create([
                'category_id' => $recipient->category_id,
                'position' => $recipient->position,
                'show_on_invitation' => true,
            ]);
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

    public function roles()
    {
        return $this->hasMany(InvitationRecipientRole::class, 'recipient_id')->orderBy('id');
    }

    public function invitationCategory(): ?InvitationCategory
    {
        $this->loadMissing(['roles.category', 'category']);

        return $this->roles->firstWhere('show_on_invitation', true)?->category
            ?? $this->category;
    }

    public function displayPosition(): ?string
    {
        $this->loadMissing('roles');

        return $this->roles->firstWhere('show_on_invitation', true)?->position
            ?: $this->position;
    }

    public function positionFor(?InvitationCategory $category = null): ?string
    {
        if (! $category) {
            return $this->displayPosition();
        }

        $this->loadMissing('roles');

        return $this->roles->firstWhere('category_id', $category->id)?->position
            ?: $this->displayPosition();
    }

    public function extraPositionCount(): int
    {
        $this->loadMissing('roles');

        return max(0, $this->roles->count() - 1);
    }

    public function belongsToCategory(int $categoryId): bool
    {
        $this->loadMissing('roles');

        return (int) $this->category_id === $categoryId
            || $this->roles->contains(fn (InvitationRecipientRole $role) => (int) $role->category_id === $categoryId);
    }

    public function invitationUrl(): string
    {
        return app(\App\Services\RecipientDirectory::class)->invitationUrl($this);
    }

    public function submitRsvp(string $status, ?string $note = null, ?string $signature = null): void
    {
        $this->forceFill([
            'rsvp_status' => $status,
            'rsvp_note' => $note,
            'rsvp_signature' => $signature,
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
