<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseAuthService;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;

class FirebaseController extends Controller
{

   public function signInWithGoogleAuth(Request $request, FirebaseAuthService $firebaseAuthService)
{
    $firebaseToken = $request->input('token');
    $user = $firebaseAuthService->getUserFromFirebaseToken($firebaseToken);

    $sanctumToken = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'token' => $sanctumToken,
        'user' => $user
    ]);
}
}