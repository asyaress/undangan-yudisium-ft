<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvitationCategory extends Model
{
    use HasFactory;

    public const ACCESS_NIM = 'nim';

    public const ACCESS_PRIVATE = 'private';

    public const ACCESS_NIP = 'nip';

    public const ACCESS_NAME = 'name';

    public const ACCESS_PUBLIC = 'public';

    protected $fillable = [
        'period_id',
        'slug',
        'title',
        'recipient_label',
        'cover_text',
        'invitation_text',
        'closing_text',
        'sort_order',
        'access_mode',
        'rsvp_enabled',
    ];

    protected function casts(): array
    {
        return [
            'rsvp_enabled' => 'boolean',
        ];
    }

    public function recipients()
    {
        return $this->hasMany(InvitationRecipient::class, 'category_id');
    }

    public function period()
    {
        return $this->belongsTo(YudisiumPeriod::class, 'period_id');
    }

    public function usesNimAccess(): bool
    {
        return $this->access_mode === self::ACCESS_NIM;
    }

    public function usesPrivateAccess(): bool
    {
        return $this->access_mode === self::ACCESS_PRIVATE;
    }

    public function usesNipAccess(): bool
    {
        return $this->access_mode === self::ACCESS_NIP;
    }

    public function usesNameAccess(): bool
    {
        return $this->access_mode === self::ACCESS_NAME;
    }

    public function usesRecipientLookupAccess(): bool
    {
        return in_array($this->access_mode, [self::ACCESS_NIP, self::ACCESS_NAME], true);
    }

    public function usesRecipientDataAccess(): bool
    {
        return $this->usesPrivateAccess() || $this->usesRecipientLookupAccess();
    }

    public function usesPublicAccess(): bool
    {
        return $this->access_mode === self::ACCESS_PUBLIC;
    }

    public function requiresRsvp(): bool
    {
        return (bool) $this->rsvp_enabled;
    }

    public function getAccessModeLabelAttribute(): string
    {
        return match ($this->access_mode) {
            self::ACCESS_NIM => 'Verifikasi NIM',
            self::ACCESS_NIP => 'Verifikasi NIP',
            self::ACCESS_NAME => 'Verifikasi Nama',
            self::ACCESS_PUBLIC => 'Umum (tanpa token)',
            default => 'Private (token)',
        };
    }
}
