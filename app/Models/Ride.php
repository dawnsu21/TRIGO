<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ride extends Model
{
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_ASSIGNED = 'assigned'; // Passenger selected driver, but driver hasn't accepted yet
    public const STATUS_ACCEPTED = 'accepted'; // Driver accepted the ride
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'passenger_id',
        'driver_id',
        'pickup_place_id',
        'dropoff_place_id',
        'pickup_lat',
        'pickup_lng',
        'pickup_address',
        'drop_lat',
        'drop_lng',
        'drop_address',
        'fare',
        'status',
        'requested_at',
        'accepted_at',
        'picked_up_at',
        'completed_at',
        'canceled_at',
        'cancellation_reason',
        'driver_declined_at',
        'declined_by_driver_id',
        'decline_reason',
        'notes',
    ];

    protected $casts = [
        'pickup_lat' => 'float',
        'pickup_lng' => 'float',
        'drop_lat' => 'float',
        'drop_lng' => 'float',
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'completed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'driver_declined_at' => 'datetime',
        'fare' => 'float',
    ];

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function pickupPlace()
    {
        return $this->belongsTo(Place::class, 'pickup_place_id');
    }

    public function dropoffPlace()
    {
        return $this->belongsTo(Place::class, 'dropoff_place_id');
    }
}

