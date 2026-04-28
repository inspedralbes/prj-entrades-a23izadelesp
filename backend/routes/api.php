<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\SeatController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\VenueTemplateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Google OAuth
    Route::get('/google', [GoogleAuthController::class, 'redirect']);
    Route::get('/google/callback', [GoogleAuthController::class, 'callback']);

    // Email verification
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');
});

// Protected auth routes
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Health check
Route::get('/ping', fn () => response()->json(['status' => 'ok']));

// Events
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);

// Sessions
Route::get('/sessions/{session}', [SessionController::class, 'show']);
Route::get('/sessions/{session}/seats', [SessionController::class, 'seats']);
Route::get('/sessions/{session}/zones/{zone}/seats', [SessionController::class, 'zoneSeatsDetail']);

// Seat Lock (cine - grid)
Route::post('/sessions/{session}/seats/lock', [SeatController::class, 'lockSeat']);
Route::delete('/sessions/{session}/seats/unlock', [SeatController::class, 'unlockSeat']);

// Zone Lock (concierto - zones)
Route::post('/sessions/{session}/zones/lock', [SeatController::class, 'lockZone']);
Route::delete('/sessions/{session}/zones/unlock', [SeatController::class, 'unlockZone']);
Route::post('/sessions/{session}/zones/{zone}/seats/lock', [SeatController::class, 'lockZoneSeat']);
Route::delete('/sessions/{session}/zones/{zone}/seats/unlock', [SeatController::class, 'unlockZoneSeat']);

// Venue templates (concert layout CRUD)
Route::get('/venue-templates', [VenueTemplateController::class, 'index']);
Route::post('/venue-templates', [VenueTemplateController::class, 'store']);
Route::get('/venue-templates/{venueTemplate}', [VenueTemplateController::class, 'show']);
Route::put('/venue-templates/{venueTemplate}', [VenueTemplateController::class, 'update']);
Route::delete('/venue-templates/{venueTemplate}', [VenueTemplateController::class, 'destroy']);
Route::post('/venue-templates/{venueTemplate}/sessions/{session}/apply', [VenueTemplateController::class, 'applyToSession']);

// Queue (sala de espera)
Route::post('/sessions/{session}/queue/join', [QueueController::class, 'join']);
Route::get('/sessions/{session}/queue/position', [QueueController::class, 'position']);
Route::post('/sessions/{session}/queue/admit', [QueueController::class, 'admit']);

// Profile (historial de compras)
Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
    Route::get('/tickets', [ProfileController::class, 'tickets']);
    Route::get('/tickets/{booking}', [ProfileController::class, 'ticket']);
});

// Bookings (cola de compra)
Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/bookings/{booking}', [BookingController::class, 'status']);
Route::get('/bookings/{booking}/qr', [BookingController::class, 'qr']);
