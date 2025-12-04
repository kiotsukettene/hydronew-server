<?php

use App\Http\Controllers\AccountSettings\AccountController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\FirebaseController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\HelpCenter\HelpCenterController;
use App\Http\Controllers\WaterQuality\WaterMonitoringController;
use App\Http\Controllers\Notification\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Devices\DeviceController;
use App\Http\Controllers\Hydroponics\HydroponicSetupController;
use App\Http\Controllers\Hydroponics\HydroponicYieldController;
use App\Http\Controllers\TipsSuggestions\TipsController;
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

Route::get('v1/help-center', [HelpCenterController::class, 'index']);

Route::post('v1/google-login', [FirebaseController::class, 'signInWithGoogleAuth']);



Route::middleware('auth:sanctum')->group(function () {
    // Protected routes go here

    Route::post('v1/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('v1/resend-otp', [AuthController::class, 'resendOtp']);

    Route::post('v1/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Routes that require both Sanctum auth and email verification

     Route::post('v1/broadcasting/auth', function (Request $request) {
    \Log::info('=== Broadcasting Auth Request ===');
    \Log::info('Socket ID: ' . $request->input('socket_id'));
    \Log::info('Channel Name: ' . $request->input('channel_name'));
    \Log::info('Raw Channel Name: ' . json_encode($request->input('channel_name')));
    \Log::info('User: ' . ($request->user() ? $request->user()->id : 'Not authenticated'));

    // Check if channel name starts with 'private-'
    $channelName = $request->input('channel_name');
    if (!str_starts_with($channelName, 'private-')) {
        \Log::error('Channel name does not start with private-');
    }

    // Extract the actual channel name without prefix
    $cleanChannelName = str_replace('private-', '', $channelName);
    \Log::info('Clean channel name: ' . $cleanChannelName);

    if (!$request->user()) {
        \Log::error('No authenticated user found');
        return response()->json(['error' => 'Unauthenticated'], 403);
    }

    try {
        $result = Broadcast::auth($request);
        \Log::info('Broadcasting auth successful');
        return $result;
    } catch (\Exception $e) {
        \Log::error('Broadcasting auth failed: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return response()->json(['error' => $e->getMessage()], 403);
    }
});

    Route::get('v1/dashboard', [DashboardController::class, 'index']);

    Route::get('v1/notifications', [NotificationController::class, 'index']);
    Route::post('v1/create-notifications', [NotificationController::class, 'createNotification']);
    Route::patch('v1/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::get('v1/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::post('v1/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

    Route::get('v1/devices', [DeviceController::class, 'index']);
    Route::post('v1/devices/connect', [DeviceController::class, 'connectDevice']);
    Route::post('v1/devices/{device}/disconnect', [DeviceController::class, 'disconnectDevice']);

    Route::get('v1/water-monitoring', [WaterMonitoringController::class, 'index']);

    Route::get('v1/manage-account', [AccountController::class, 'index']);
    Route::put('v1/update-account', [AccountController::class, 'update']);
    Route::post('v1/update-profile-picture', [AccountController::class, 'updateProfilePicture']);
    Route::put('v1/manage-account/update-password', [AccountController::class, 'updatePassword']);

    Route::get('v1/manage-account/login-history', [AccountController::class, 'loginHistory']);

    Route::get('v1/hydroponic-setups', [HydroponicSetupController::class, 'index']);
    Route::get('v1/hydroponic-setups/{setup}', [HydroponicSetupController::class, 'show']);
    Route::post('v1/hydroponic-setups', [HydroponicSetupController::class, 'store']);

    Route::get('v1/hydroponic-yields', [HydroponicYieldController::class, 'index']);
    Route::get('v1/hydroponic-yields/{setup}', [HydroponicYieldController::class, 'show']);
    Route::put('v1/hydroponic-yields/{yield}/update-actual-yield', [HydroponicYieldController::class, 'updateActualYield']);

    Route::get('v1/tips-suggestion', [TipsController::class, 'generateTips']);
});
