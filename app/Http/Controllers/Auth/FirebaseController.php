<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseAuthService;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;

class FirebaseController extends Controller
{
    public function __construct(private FirebaseAuthService $firebaseAuthService)
    {
    }

    public function signInWithGoogleAuth(Request $request) {
        $token = $request->input("token");

        $user = $this->firebaseAuthService->getUserFromFirebaseToken($token);
        $token = $user->createToken('Access token')->accessToken;

        return response()->json([
            'access_token' => $token,
        ]);
    }
}
