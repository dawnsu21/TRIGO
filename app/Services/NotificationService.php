<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Ride;
use App\Models\User;

class NotificationService
{
    /**
     * Create a notification for a user
     */
    public static function create(User $user, string $type, string $title, string $message, ?Ride $ride = null, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'ride_id' => $ride?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    /**
     * Notify passenger: Driver accepted the ride
     */
    public static function notifyDriverAccepted(Ride $ride): void
    {
        if ($ride->driver && $ride->passenger) {
            self::create(
                $ride->passenger,
                Notification::TYPE_RIDE_ACCEPTED,
                'Driver Accepted Your Ride',
                "🎉 Your driver has accepted your ride! Driver: {$ride->driver->name}",
                $ride,
                ['driver_name' => $ride->driver->name, 'driver_id' => $ride->driver->id]
            );
        }
    }

    /**
     * Notify passenger: Driver is on the way
     */
    public static function notifyDriverOnWay(Ride $ride): void
    {
        if ($ride->driver && $ride->passenger) {
            self::create(
                $ride->passenger,
                Notification::TYPE_DRIVER_ON_WAY,
                'Driver is on the Way',
                "Your driver {$ride->driver->name} is on the way to pick you up.",
                $ride,
                ['driver_name' => $ride->driver->name]
            );
        }
    }

    /**
     * Notify passenger: Trip completed
     */
    public static function notifyTripCompleted(Ride $ride): void
    {
        if ($ride->passenger) {
            self::create(
                $ride->passenger,
                Notification::TYPE_TRIP_COMPLETED,
                'Trip Completed',
                'Trip completed, thank you for riding with TriGo!',
                $ride,
                ['fare' => $ride->fare]
            );
        }
    }

    /**
     * Notify passenger: Driver cancelled
     */
    public static function notifyDriverCancelled(Ride $ride, string $reason = null): void
    {
        if ($ride->passenger) {
            $message = 'Your driver cancelled the trip. Search for a new one.';
            if ($reason) {
                $message .= " Reason: {$reason}";
            }
            
            self::create(
                $ride->passenger,
                Notification::TYPE_DRIVER_CANCELLED,
                'Driver Cancelled Trip',
                $message,
                $ride
            );
        }
    }

    /**
     * Notify passenger: Ride cancelled by passenger
     */
    public static function notifyRideCancelled(Ride $ride): void
    {
        if ($ride->passenger) {
            self::create(
                $ride->passenger,
                Notification::TYPE_RIDE_CANCELLED,
                'Ride Cancelled',
                'You have successfully cancelled your ride.',
                $ride
            );
        }
    }

    /**
     * Notify admin: Emergency alert
     */
    public static function notifyEmergencyToAdmins(int $emergencyId, string $title, string $message, array $data = []): void
    {
        $admins = User::role('admin')->get();
        
        foreach ($admins as $admin) {
            self::create(
                $admin,
                Notification::TYPE_EMERGENCY_ALERT,
                $title,
                $message,
                null,
                array_merge($data, ['emergency_id' => $emergencyId])
            );
        }
    }

    /**
     * Notify users: System announcement
     */
    public static function notifySystemAnnouncement(string $title, string $message, string $audience = 'all', array $userIds = []): void
    {
        if ($audience === 'all') {
            $users = User::all();
        } elseif ($audience === 'drivers') {
            $users = User::role('driver')->get();
        } elseif ($audience === 'passengers') {
            $users = User::role('passenger')->get();
        } elseif ($audience === 'admins') {
            $users = User::role('admin')->get();
        } else {
            $users = User::whereIn('id', $userIds)->get();
        }

        foreach ($users as $user) {
            self::create(
                $user,
                Notification::TYPE_SYSTEM_ANNOUNCEMENT,
                $title,
                $message,
                null,
                ['audience' => $audience]
            );
        }
    }
}

