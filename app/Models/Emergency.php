<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emergency extends Model
{
    use HasFactory;

    public const TYPE_SAFETY_CONCERN = 'safety_concern';
    public const TYPE_DRIVER_EMERGENCY = 'driver_emergency';
    public const TYPE_PASSENGER_EMERGENCY = 'passenger_emergency';
    public const TYPE_ACCIDENT = 'accident';
    public const TYPE_OTHER = 'other';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = [
        'user_id',
        'ride_id',
        'reported_by_role',
        'type',
        'title',
        'description',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'admin_notes',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}

