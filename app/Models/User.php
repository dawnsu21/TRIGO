<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int,string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string,string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function driverProfile()
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function passengerRides()
    {
        return $this->hasMany(Ride::class, 'passenger_id');
    }

    public function driverRides()
    {
        return $this->hasMany(Ride::class, 'driver_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications()
    {
        return $this->hasMany(Notification::class)->where('is_read', false);
    }

    public function feedbacksGiven()
    {
        return $this->hasMany(Feedback::class, 'from_user_id');
    }

    public function feedbacksReceived()
    {
        return $this->hasMany(Feedback::class, 'to_user_id');
    }

    public function emergencies()
    {
        return $this->hasMany(Emergency::class);
    }
}
