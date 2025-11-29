<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverProfile extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'vehicle_type',
        'plate_number',
        'license_number',
        'franchise_number',
        'status',
        'is_online',
        'current_lat',
        'current_lng',
        'location_updated_at',
        'document_path',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'current_lat' => 'float',
        'current_lng' => 'float',
        'location_updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}

