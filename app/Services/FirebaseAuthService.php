<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class FirebaseAuthService
{
    private Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function getUserFromFirebaseToken(string $firebaseIdToken)
    {
        try {
            /** @var UnercryptedToken $verifiedIdToken */
            $verifiedIdToken = $this->auth->verifyIdToken($firebaseIdToken);
            $userFirebaseId = $verifiedIdToken->claims()->get('sub');
            $firebaseUserProfile = $this->auth->getUser($userFirebaseId);

            $email = $firebaseUserProfile->email;

            $user = null;

            if ($email) {
                $user = User::where('email', $email)->first();
            } else {
                throw new BadRequestException("The token does not contain a valid email");
            }

            if (!$user) {
                $user = new User();
                $userData = [
                    'email' => $email,
                    'name' => $firebaseUserProfile->displayName ?? 'Firebase User',
                    'email_verified_at' => now(),
                ];
                $user->fill($userData);
                $user->save();

                return $user;
            }
            return $user;
        } catch (FailedToVerifyToken $exception) {
            Log::error($exception->getMessage());
            throw new BadRequestException("The token is invalid");
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            throw new BadRequestException("An error occurred while verifying the token");
        }
    }
}
