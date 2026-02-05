<?php

use App\Http\Controllers\AccountSettings\AccountController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\FirebaseController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\HelpCenter\HelpCenterController;
use App\Http\Controllers\WaterQuality\WaterMonitoringController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Reports\ReportsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Devices\DeviceController;
use App\Http\Controllers\Devices\MQTTSensorDataController;
use App\Http\Controllers\Hydroponics\HydroponicSetupController;
use App\Http\Controllers\Hydroponics\HydroponicYieldController;
use App\Http\Controllers\TipsSuggestions\TipsController;
use App\Http\Controllers\Treatment\TreatmentController;
use App\Models\HydroponicSetup;
use App\Models\HydroponicYield;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('v1/register', [AuthController::class, 'register']);
Route::post('v1/login', [AuthController::class, 'login']);
Route::post('v1/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('v1/verify-reset-code', [PasswordResetController::class, 'verifyResetCode']);
Route::post('v1/resend-reset-code', [PasswordResetController::class, 'resendResetCode']);
Route::post('v1/reset-password', [PasswordResetController::class, 'resetPassword']);



Route::post('v1/google-login', [FirebaseController::class, 'signInWithGoogleAuth']);

Route::post('v1/devices/provision', [DeviceController::class, 'provision']);



Route::middleware('auth:sanctum')->group(function () {
    // Protected routes go here

    Route::post('v1/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('v1/resend-otp', [AuthController::class, 'resendOtp']);

    Route::post('v1/logout', [AuthController::class, 'logout']);

    // Broadcasting auth endpoint - MUST be inside auth:sanctum middleware
    Route::post('v1/broadcasting/auth', function (Request $request) {
        Log::info('=== Broadcasting Auth Request ===');
        Log::info('Socket ID: ' . $request->input('socket_id'));
        Log::info('Channel Name: ' . $request->input('channel_name'));
        Log::info('User: ' . ($request->user() ? $request->user()->id : 'Not authenticated'));

        if (!$request->user()) {
            Log::error('No authenticated user found');
            return response()->json(['error' => 'Unauthenticated'], 403);
        }

        try {
            // Laravel's Broadcast::auth() returns a response
            return Broadcast::auth($request);
        } catch (\Exception $e) {
            Log::error('Broadcasting auth failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 403);
        }
    });
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Routes that require both Sanctum auth and email verification

//     Route::post('v1/broadcasting/auth', function (Request $request) {
//     Log::info('=== Broadcasting Auth Request ===');
//     Log::info('Socket ID: ' . $request->input('socket_id'));
//     Log::info('Channel Name: ' . $request->input('channel_name'));
//     Log::info('Raw Channel Name: ' . json_encode($request->input('channel_name')));
//     Log::info('User: ' . ($request->user() ? $request->user()->id : 'Not authenticated'));

//     // Check if channel name starts with 'private-'
//     $channelName = $request->input('channel_name');
//     if (!str_starts_with($channelName, 'private-')) {
//         Log::error('Channel name does not start with private-');
//     }

//     // Extract the actual channel name without prefix
//     $cleanChannelName = str_replace('private-', '', $channelName);
//     Log::info('Clean channel name: ' . $cleanChannelName);

//     if (!$request->user()) {
//         Log::error('No authenticated user found');
//         return response()->json(['error' => 'Unauthenticated'], 403);
//     }

//     try {
//         $result = Broadcast::auth($request);
//         Log::info('Broadcasting auth successful');
//         return $result;
//     } catch (\Exception $e) {
//         Log::error('Broadcasting auth failed: ' . $e->getMessage());
//         Log::error('Stack trace: ' . $e->getTraceAsString());
//         return response()->json(['error' => $e->getMessage()], 403);
//     }
// });

    Route::get('v1/dashboard', [DashboardController::class, 'index']);

    Route::get('v1/notifications', [NotificationController::class, 'index']);
    Route::post('v1/create-notifications', [NotificationController::class, 'createNotification']);
    Route::patch('v1/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::get('v1/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::post('v1/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

    Route::get('v1/devices', [DeviceController::class, 'fetchDevices']);
    Route::post('v1/devices/connect', [DeviceController::class, 'connectDevice']);
    Route::post('v1/devices/{device}/disconnect', [DeviceController::class, 'disconnectDevice']);
    Route::post('v1/devices/pairing-token', [DeviceController::class, 'pairingToken']);
    Route::post('v1/devices/unpair', [DeviceController::class, 'unpair']);
    Route::post('v1/devices/generate-qr-payload', [DeviceController::class, 'generateQrPayload']);
    Route::post('v1/devices/pair-by-qr', [DeviceController::class, 'pairByQr']);

    Route::get('v1/water-monitoring', [WaterMonitoringController::class, 'index']);

    Route::get('v1/manage-account', [AccountController::class, 'index']);
    Route::put('v1/update-account', [AccountController::class, 'update']);
    Route::post('v1/update-profile-picture', [AccountController::class, 'updateProfilePicture']);
    Route::put('v1/manage-account/update-password', [AccountController::class, 'updatePassword']);

    Route::get('v1/manage-account/login-history', [AccountController::class, 'loginHistory']);

    Route::get('v1/hydroponic-setups', [HydroponicSetupController::class, 'index']);
    Route::get('v1/hydroponic-setups/{setup}', [HydroponicSetupController::class, 'show']);
    Route::post('v1/hydroponic-setups/store', [HydroponicSetupController::class, 'store']);
    Route::put('v1/hydroponic-setups/{setup}', [HydroponicSetupController::class, 'update']);
    Route::post('v1/hydroponic-setups/{setup}/mark-harvested',[HydroponicSetupController::class, 'markAsHarvested']);

    Route::get('v1/hydroponic-yields', [HydroponicYieldController::class, 'index']);
    Route::get('v1/hydroponic-yields/{setup}', [HydroponicYieldController::class, 'show']);
    Route::post('v1/hydroponic-yields/{setup}/store', [HydroponicYieldController::class, 'storeYield']);

    Route::get('v1/tips-suggestion', [TipsController::class, 'generateTips']);

    Route::get('v1/help-center', [HelpCenterController::class, 'index']);

    // Feedback endpoints
    Route::post('v1/feedback', [FeedbackController::class, 'store']);
    Route::get('v1/feedback', [FeedbackController::class, 'index']);

    Route::post('v1/treatment', [TreatmentController::class, 'saveTreatment']);
    Route::put('v1/treatment/update-treatment', [TreatmentController::class, 'updateTreatment']);
    Route::post('v1/treatment/stages', [TreatmentController::class, 'saveTreatmentStage']);
    Route::put('v1/treatment/update-stages', [TreatmentController::class, 'updateTreatmentStage']);
    // Reports and Analytics endpoints
    Route::prefix('v1/reports')->group(function () {
        // Crop analytics
        Route::get('/crop-performance', [ReportsController::class, 'cropPerformance']);
        Route::get('/crop-comparison', [ReportsController::class, 'cropComparison']);
        Route::get('/yield-summary', [ReportsController::class, 'yieldSummary']);

        // Water quality
        Route::get('/water-quality/historical', [ReportsController::class, 'waterQualityHistorical']);
        Route::get('/water-quality/trends', [ReportsController::class, 'waterQualityTrends']);
        Route::get('/water-comparison', [ReportsController::class, 'waterComparison']);

        // Treatment performance
        Route::get('/treatment-performance', [ReportsController::class, 'treatmentPerformance']);
        Route::get('/treatment-efficiency', [ReportsController::class, 'treatmentEfficiency']);
    });
});
