<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class FirebaseAuthService
{
    private Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function getUserFromFirebaseToken(string $firebaseIdToken, $request)
    {
        try {
            $leeway = 10;
            $verifiedIdToken = $this->auth->verifyIdToken($firebaseIdToken, $checkIfRevoked = true, $leeway);

            $firebaseUserId = $verifiedIdToken->claims()->get('sub');
            $firebaseUser = $this->auth->getUser($firebaseUserId);

            if (!$firebaseUser->email) {
                throw new BadRequestException("The token does not contain a valid email");
            }

            return User::firstOrCreate(
                ['email' => $firebaseUser->email],
                [
                    'first_name' => $request->first_name ?? ($firebaseUser->displayName ? explode(' ', $firebaseUser->displayName)[0] : null),
                    'last_name' => $request->last_name ?? ($firebaseUser->displayName ? explode(' ', $firebaseUser->displayName)[1] ?? '' : null),
                    'email_verified_at' => now(),
                ]
            );

        } catch (FailedToVerifyToken $e) {
            Log::error($e->getMessage());
            throw new BadRequestException("The token is invalid");
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new BadRequestException("An error occurred while verifying the token");
        }
    }
}
