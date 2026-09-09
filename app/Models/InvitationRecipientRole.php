<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationRecipientRole extends Model
{
    protected $fillable = [
        'recipient_id',
        'category_id',
        'position',
        'show_on_invitation',
    ];

    protected function casts(): array
    {
        return [
            'show_on_invitation' => 'boolean',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(InvitationRecipient::class, 'recipient_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InvitationCategory::class, 'category_id');
    }
};
