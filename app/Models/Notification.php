<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public const TYPE_RIDE_ACCEPTED = 'ride_accepted';
    public const TYPE_DRIVER_ON_WAY = 'driver_on_way';
    public const TYPE_TRIP_COMPLETED = 'trip_completed';
    public const TYPE_DRIVER_CANCELLED = 'driver_cancelled';
    public const TYPE_RIDE_CANCELLED = 'ride_cancelled';
    public const TYPE_EMERGENCY_ALERT = 'emergency_alert';
    public const TYPE_SYSTEM_ANNOUNCEMENT = 'system_announcement';

    protected $fillable = [
        'user_id',
        'ride_id',
        'type',
        'title',
        'message',
        'is_read',
        'data',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRecent($query, $limit = 20)
    {
        return $query->latest()->limit($limit);
    }
}

