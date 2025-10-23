<?php

use App\Http\Controllers\AccountSettings\AccountController;
use App\Http\Controllers\SensorReadings\SensorReadingsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\HelpCenter\HelpCenterController;
use App\Http\Controllers\WaterQuality\WaterMonitoringController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Devices\DeviceController;
use App\Http\Controllers\Hydroponics\HydroponicSetupController;
use App\Http\Controllers\Hydroponics\HydroponicYieldController;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('v1/register', [AuthController::class, 'register']);
Route::post('v1/login', [AuthController::class, 'login']);
Route::post('v1/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('v1/verify-reset-code', [PasswordResetController::class, 'verifyResetCode']);
Route::post('v1/resend-reset-code', [PasswordResetController::class, 'resendResetCode']);
Route::post('v1/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::get('v1/help-center', [HelpCenterController::class,'index']);



Route::middleware('auth:sanctum')->group(function () {
    // Protected routes go here

    Route::post('v1/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('v1/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:3,1'); // Limit to 3 requests per minute

    Route::post('v1/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Routes that require both Sanctum auth and email verification
    Route::get('v1/dashboard', [DashboardController::class, 'index']);

    Route::get('v1/devices', [DeviceController::class, 'index']);
    Route::post('v1/devices/connect', [DeviceController::class, 'connectDevice']);
    Route::post('v1/devices/{device}/disconnect', [DeviceController::class, 'disconnectDevice']);

    Route::get('v1/water-monitoring', [WaterMonitoringController::class, 'index']);

    Route::get('v1/manage-account', [AccountController::class, 'index']);
    Route::put('v1/manage-account/{user}', [AccountController::class, 'update']);
    Route::put('v1/manage-account/{user}/update-password', [AccountController::class, 'updatePassword']);

    Route::get('v1/manage-account/login-history', [AccountController::class, 'loginHistory']);

    Route::get('v1/hydroponic-setups', [HydroponicSetupController::class, 'index']);
    Route::post('v1/hydroponic-setups', [HydroponicSetupController::class, 'store']);

    Route::get('v1/hydroponic-yields', [HydroponicYieldController::class, 'index']);
    Route::get('v1/hydroponic-yields/{setup}', [HydroponicYieldController::class, 'show']);
    Route::put('v1/hydroponic-yields/{yield}/update-actual-yield', [HydroponicYieldController::class, 'updateActualYield']);


});


Route::post('/v1/sensor-reading', [SensorReadingsController::class, 'store'])
    ->middleware('auth.secret');
