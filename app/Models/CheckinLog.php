<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckinLog extends Model
{
    protected $fillable = [
        'period_id',
        'participant_id',
        'admin_id',
        'nim',
        'status',
        'source',
        'latitude',
        'longitude',
        'distance_meter',
        'accuracy_meter',
        'radius_meter',
        'message',
        'manual_note',
        'ip_address',
        'user_agent',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'distance_meter' => 'integer',
            'accuracy_meter' => 'integer',
            'radius_meter' => 'integer',
            'attempted_at' => 'datetime',
        ];
    }

    public function period()
    {
        return $this->belongsTo(YudisiumPeriod::class, 'period_id');
    }

    public function participant()
    {
        return $this->belongsTo(YudisiumParticipant::class, 'participant_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
