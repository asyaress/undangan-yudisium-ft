<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MobileDeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /**
     * @return array{plain: string, token: self}
     */
    public static function issue(User $user, ?string $deviceName = null): array
    {
        $plain = Str::lower(Str::random(48));

        $token = static::query()->create([
            'user_id' => $user->id,
            'name' => $deviceName ?: 'HP panitia',
            'token_hash' => static::hashToken($plain),
            'last_used_at' => now(),
        ]);

        return [
            'plain' => $plain,
            'token' => $token,
        ];
    }
}
