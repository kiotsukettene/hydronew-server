<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\VerifyResetCodeRequest;
use App\Models\User;
use App\Notifications\ForgotPasswordCodeNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $request->validated();

        // rate limit: 60s between requests
        $existing = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        if ($existing && Carbon::parse($existing->created_at)->diffInSeconds(now()) < 60) {
            return response()->json(['message' => 'Please wait before requesting another code.'], 429);
        }

        $code = random_int(100000, 999999); // secure numeric OTP

        // insert/replace hashed token
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make((string) $code),
                'created_at' => now(),
            ]
        );

        // Send the code via your notification
        $user = User::where('email', $request->email)->first();
        $user->notify(new ForgotPasswordCodeNotification($code));

        return response()->json(['message' => 'Password reset code sent to your email.'], 200);
    }
    public function verifyResetCode(VerifyResetCodeRequest $request)
    {
        $request->validated();

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid request.'], 400);
        }

        // check expiry of the code (15 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return response()->json(['message' => 'Reset code expired.'], 400);
        }

        // verify the 6-digit code against the hashed token
        if (!Hash::check($request->code, $record->token)) {
            return response()->json(['message' => 'Invalid reset code.'], 400);
        }

        // generate a one-time reset token (long random string)
        $resetToken = Str::random(64);

        // replace the token in the DB with the HASH of the reset token and reset timestamp
        DB::table('password_reset_tokens')->where('email', $request->email)->update([
            'token' => Hash::make($resetToken),
            'created_at' => now(), // reset the timer for the reset_token expiry window
        ]);

        // Return the reset token to the client (must be sent over HTTPS)
        return response()->json([
            'message' => 'Code verified. Use the returned reset_token to set a new password.',
            'reset_token' => $resetToken,
            'expires_in' => 15 * 60 // seconds
        ], 200);
    }
    public function resendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        $record = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        // Check cooldown (60 seconds)
        if ($record && Carbon::parse($record->created_at)->diffInSeconds(now()) < 60) {
            return response()->json([
                'message' => 'Please wait before requesting another reset code.'
            ], 429);
        }

        // Generate new 6-digit OTP
        $otp = random_int(100000, 999999);

        // Store new OTP (replaces old one)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        // Send email notification with the OTP
        $user->notify(new ForgotPasswordCodeNotification($otp));

        return response()->json([
            'message' => 'A new reset code has been sent to your email.'
        ], 201);
    }
    public function resetPassword(ResetPasswordRequest $request)
    {
        $request->validated();

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 400);
        }

        // verify reset_token against stored hashed token
        if (!Hash::check($request->reset_token, $record->token)) {
            return response()->json(['message' => 'Invalid reset token.'], 403);
        }

        // check expiry (15 minutes from when reset_token was created)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return response()->json(['message' => 'Reset token expired.'], 403);
        }

        // update the user's password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // delete the token record to prevent reuse
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successfully.'], 200);
    }
}
