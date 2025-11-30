<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\EmergencyController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PassengerRideController;
use App\Http\Controllers\Api\PlaceController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // Unified registration with role
Route::post('/register/passenger', [AuthController::class, 'registerPassenger']); // Legacy endpoint
Route::post('/register/driver', [AuthController::class, 'registerDriver']); // Legacy endpoint

// Places endpoints (public for listing, admin for management)
Route::get('/places', [PlaceController::class, 'index']);
Route::get('/places/search', [PlaceController::class, 'search']);
Route::get('/places/{place}', [PlaceController::class, 'show']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Notifications (all authenticated users)
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{notification}', [NotificationController::class, 'destroy']);
    });

    // Announcements (all authenticated users)
    Route::get('/announcements', [AnnouncementController::class, 'index']);

    // Feedback (drivers and passengers)
    Route::prefix('feedback')->group(function () {
        Route::post('/', [FeedbackController::class, 'store']);
        Route::get('/my-feedbacks', [FeedbackController::class, 'myFeedbacks']);
        Route::get('/rides/{ride}', [FeedbackController::class, 'rideFeedback']);
    });

    // Emergency reports (drivers and passengers)
    Route::prefix('emergencies')->group(function () {
        Route::post('/', [EmergencyController::class, 'store']);
        Route::get('/my-reports', [EmergencyController::class, 'myReports']);
    });

    Route::middleware('role:passenger')->prefix('passenger')->group(function () {
        Route::get('/dashboard', [PassengerRideController::class, 'dashboard']);
        Route::get('/drivers/available', [PassengerRideController::class, 'availableDrivers']);
        Route::post('/rides', [PassengerRideController::class, 'store']);
        Route::get('/rides/current', [PassengerRideController::class, 'current']);
        Route::get('/rides', [PassengerRideController::class, 'history']);
        Route::post('/rides/{ride}/cancel', [PassengerRideController::class, 'cancel']);
    });

    Route::middleware('role:driver')->prefix('driver')->group(function () {
        Route::get('/dashboard', [DriverController::class, 'dashboard']);
        Route::get('/profile', [DriverController::class, 'profile']);
        Route::post('/availability', [DriverController::class, 'updateAvailability']);
        Route::post('/location', [DriverController::class, 'updateLocation']);
        Route::get('/rides/queue', [DriverController::class, 'queue']);
        Route::get('/rides/history', [DriverController::class, 'history']);
        Route::post('/rides/{ride}/accept', [DriverController::class, 'accept']);
        Route::post('/rides/{ride}/decline', [DriverController::class, 'decline']);
        Route::post('/rides/{ride}/pickup', [DriverController::class, 'pickup']);
        Route::post('/rides/{ride}/complete', [DriverController::class, 'complete']);
        Route::get('/passengers/{passenger}', [DriverController::class, 'viewPassengerProfile']);
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index']);
        
        // Drivers
        Route::get('/drivers', [AdminController::class, 'drivers']);
        Route::get('/drivers/{driverProfile}', [AdminController::class, 'showDriver']);
        Route::post('/drivers/{driverProfile}/status', [AdminController::class, 'updateDriverStatus']);
        
        // Rides
        Route::get('/rides', [AdminController::class, 'rides']);
        Route::get('/rides/{ride}', [AdminController::class, 'showRide']);
        
        // Places management (Admin only)
        Route::get('/places', [PlaceController::class, 'adminIndex']);
        Route::post('/places', [PlaceController::class, 'store']);
        Route::put('/places/{place}', [PlaceController::class, 'update']);
        Route::delete('/places/{place}', [PlaceController::class, 'destroy']);
        
        // Emergency management
        Route::prefix('emergencies')->group(function () {
            Route::get('/', [EmergencyController::class, 'adminIndex']);
            Route::post('/{emergency}/acknowledge', [EmergencyController::class, 'acknowledge']);
            Route::post('/{emergency}/resolve', [EmergencyController::class, 'resolve']);
        });
        
        // Announcements
        Route::prefix('announcements')->group(function () {
            Route::get('/', [AnnouncementController::class, 'adminIndex']);
            Route::post('/', [AnnouncementController::class, 'store']);
            Route::put('/{announcement}', [AnnouncementController::class, 'update']);
            Route::delete('/{announcement}', [AnnouncementController::class, 'destroy']);
        });
    });
});