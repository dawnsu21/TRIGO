<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\PassengerRideController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register/passenger', [AuthController::class, 'registerPassenger']);
Route::post('/register/driver', [AuthController::class, 'registerDriver']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:passenger')->prefix('passenger')->group(function () {
        Route::post('/rides', [PassengerRideController::class, 'store']);
        Route::get('/rides/current', [PassengerRideController::class, 'current']);
        Route::get('/rides', [PassengerRideController::class, 'history']);
        Route::post('/rides/{ride}/cancel', [PassengerRideController::class, 'cancel']);
    });

    Route::middleware('role:driver')->prefix('driver')->group(function () {
        Route::get('/profile', [DriverController::class, 'profile']);
        Route::post('/availability', [DriverController::class, 'updateAvailability']);
        Route::post('/location', [DriverController::class, 'updateLocation']);
        Route::get('/rides/queue', [DriverController::class, 'queue']);
        Route::post('/rides/{ride}/accept', [DriverController::class, 'accept']);
        Route::post('/rides/{ride}/pickup', [DriverController::class, 'pickup']);
        Route::post('/rides/{ride}/complete', [DriverController::class, 'complete']);
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index']);
        Route::get('/drivers', [AdminController::class, 'drivers']);
        Route::post('/drivers/{driverProfile}/status', [AdminController::class, 'updateDriverStatus']);
        Route::get('/rides', [AdminController::class, 'rides']);
    });
});