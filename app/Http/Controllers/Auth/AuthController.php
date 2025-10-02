<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOTPRequest;
use App\Http\Requests\Auth\VerifyResetCodeRequest;
use App\Models\User;
use App\Notifications\ForgotPasswordCodeNotification;
use App\Notifications\VerificationCodeNotification;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    protected function generateOtp(): string
    {
        return (string) random_int(100000, 999999); // 6 digit secure random code
    }

    protected function storeOtpOnUser(User $user, string $otp)
    {
        $user->verification_code = Hash::make($otp);
        $user->verification_expires_at = now()->addMinutes(10);
        $user->last_otp_sent_at = now();
        $user->save();
    }

    public function register(RegisterRequest $request)
    {
        // Handle user registration
        // Validate requests
        $fields = $request->validated();

        // Create user
        $user = User::create([
            'first_name' => $fields['first_name'],
            'last_name' => $fields['last_name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password'])
        ]);

        $otp = $this->generateOtp();
        $this->storeOtpOnUser($user, $otp);

        $user->notify(new VerificationCodeNotification($otp));

        // Handle access tokens
        $token = $user->createToken($request->first_name)->plainTextToken;

        // Return the user and the token as response along with plain text token
        return response()->json([
            'message' => 'User registered successfully. Please check your email for the verification code.',
            'user' => $user,
            'token' => $token,
            'needs_verification' => true
        ], 201);

    }

    public function login(LoginRequest $request)
    {
        // Handle user login
        // Validate the requests
        $request->validated();

        // Check the email first of the user
        $user = User::where('email', $request->email)->first();

        // If the user does not exist or the password does not match
        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                'message' => 'The provided credentials are incorrect'
            ];
        }

        if (!$user->email_verified_at) {
            // regenerate OTP
            $otp = $this->generateOtp();
            $this->storeOtpOnUser($user, $otp);
            $user->notify(new VerificationCodeNotification($otp));

            $user->tokens()->where('name', '!=', '')->delete();
            $verificationToken = $user->createToken($request->email)->plainTextToken;

            return response()->json([
                'message' => 'Your email is not verified. Please check your email for the verification code.',
                'token' => $verificationToken,
                'needs_verification' => true
            ], 200);
        }

        // If matched, create a token for the user
        $token = $user->createToken($user->first_name);

        return [
            'message' => "Login successfully!",
            'user' => $user,
            'token' => $token->plainTextToken
        ];

    }

    public function verifyOtp(VerifyOTPRequest $request)
    {
        // This must be called with the verification token (auth:sanctum)
        $request->validated();

        // ensure token has ability 'verify'
        if (!$request->user()->tokenCan('verify')) {
            return response()->json(['message' => 'Invalid token for verification.'], 403);
        }

        $user = $request->user();

        if (!$user->verification_expires_at || $user->verification_expires_at->isPast()) {
            return response()->json(['message' => 'Verification code expired.'], 400);
        }

        if (!Hash::check($request->otp, $user->verification_code)) {
            return response()->json(['message' => 'Invalid verification code.'], 400);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->verification_code = null;
        $user->verification_expires_at = null;
        $user->last_otp_sent_at = null;
        $user->save();

        // Delete verification tokens and issue a full auth token
        $user->tokens()->delete();
        $fullToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully.',
            'token' => $fullToken,
        ]);
    }

    public function resendOtp(Request $request)
    {
        // Protect by auth:sanctum and ensure tokenCan('verify')
        if (!$request->user()->tokenCan('verify')) {
            return response()->json(['message' => 'Invalid token.'], 403);
        }

        $user = $request->user();

        // Rate limit: ensure at least 60 seconds between resends (customize as needed)
        if ($user->last_otp_sent_at && $user->last_otp_sent_at->diffInSeconds(now()) < 60) {
            return response()->json(['message' => 'Please wait before requesting another code.'], 429);
        }

        $otp = $this->generateOtp();
        $this->storeOtpOnUser($user, $otp);
        $user->notify(new VerificationCodeNotification($otp));

        return response()->json(['message' => 'A new verification code has been sent.'], 201);
    }

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
        $otp = rand(100000, 999999);

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
            return response()->json(['message' => 'Invalid reset token.'], 400);
        }

        // check expiry (15 minutes from when reset_token was created)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return response()->json(['message' => 'Reset token expired.'], 400);
        }

        // update the user's password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // delete the token record to prevent reuse
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successfully.'], 200);
    }

    public function logout(Request $request)
    {
        // Handle user logout
        $request->user()->tokens()->delete();
        $request->user()->first_time_login = 0;
        $request->user()->save();
        return [
            'message' => 'You are logged out'
        ];
    }
}
