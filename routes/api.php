<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\WaterQuality\WaterMonitoringController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/verify-reset-code', [PasswordResetController::class, 'verifyResetCode']);
Route::post('/resend-reset-code', [PasswordResetController::class, 'resendResetCode']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::get('/water-monitoring', [WaterMonitoringController::class, 'index']);



Route::middleware('auth:sanctum')->group(function () {
    // Protected routes go here

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:3,1'); // Limit to 3 requests per minute

    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Routes that require both Sanctum auth and email verification
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
