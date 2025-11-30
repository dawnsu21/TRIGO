<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    public const TYPE_MAINTENANCE = 'maintenance';
    public const TYPE_SYSTEM_UPDATE = 'system_update';
    public const TYPE_GENERAL = 'general';
    public const TYPE_URGENT = 'urgent';

    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_DRIVERS = 'drivers';
    public const AUDIENCE_PASSENGERS = 'passengers';
    public const AUDIENCE_ADMINS = 'admins';

    protected $fillable = [
        'admin_id',
        'type',
        'title',
        'message',
        'start_date',
        'end_date',
        'is_active',
        'target_audience',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeForAudience($query, $audience)
    {
        return $query->where(function ($q) use ($audience) {
            $q->where('target_audience', self::AUDIENCE_ALL)
              ->orWhere('target_audience', $audience);
        });
    }
}

