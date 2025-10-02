<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

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

    public function register(Request $request)
    {
        // Handle user registration

        // Validate requests
        $fields = $request->validate([
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed'
        ]);

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
        $token = $user->createToken('verification_token', ['verify'])->plainTextToken;

        // Return the user and the token as response along with plain text token
        return response()->json([
            'message' => 'User registered successfully. Please check your email for the verification code.',
            'user' => $user,
            'token' => $token,
            'needs_verification' => true
        ], 201);
    }

    public function login(Request $request)
    {
        // Handle user login

        // Validate the requests
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);

        // Check the email first of the user
        $user = User::where('email', $request->email)->first();

        // If the user does not exist or the password does not match
        if (!$user || ! Hash::check($request->password, $user->password)) {
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
            $verificationToken = $user->createToken('verification_token', ['verify'])->plainTextToken;

            return response()->json([
                'message' => 'Your email is not verified. Please check your email for the verification code.',
                'token' => $verificationToken,
                'needs_verification' => true
            ], 200);
        }

        // If matched, create a token for the user
        $token = $user->createToken($user->first_name);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    public function verifyOtp(Request $request)
    {
        // This must be called with the verification token (auth:sanctum)
        $request->validate(['otp' => 'required|digits:6']);

        // ensure token has ability 'verify'
        if (! $request->user()->tokenCan('verify')) {
            return response()->json(['message' => 'Invalid token for verification.'], 403);
        }

        $user = $request->user();

        if (! $user->verification_expires_at || $user->verification_expires_at->isPast()) {
            return response()->json(['message' => 'Verification code expired.'], 400);
        }

        if (! Hash::check($request->otp, $user->verification_code)) {
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
        if (! $request->user()->tokenCan('verify')) {
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

        return response()->json(['message' => 'A new verification code has been sent.']);
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
