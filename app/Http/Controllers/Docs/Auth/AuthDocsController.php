<?php

namespace App\Http\Controllers\Docs\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints for user registration, login, OTP verification, and logout."
 * )
 */

/**
 * @OA\Post(
 *     path="/api/v1/register",
 *     tags={"Authentication"},
 *     summary="Register a new user",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"first_name","last_name","email","password"},
 *             @OA\Property(property="first_name", type="string", example="Momo"),
 *             @OA\Property(property="last_name", type="string", example="Revillame"),
 *             @OA\Property(property="email", type="string", example="momo@example.com"),
 *             @OA\Property(property="password", type="string", example="Password@123"),
 *            @OA\Property(property="password_confirmation", type="string", example="Password@123")
 *         )
 *     ),
 *     @OA\Response(response=201, description="User registered successfully."),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/login",
 *     tags={"Authentication"},
 *     summary="Login user",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", example="momo@example.com"),
 *             @OA\Property(property="password", type="string", example="Password@123")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Login successful"),
 *     @OA\Response(response=422, description="Invalid credentials")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/verify-otp",
 *     tags={"Authentication"},
 *     security={{"bearerAuth":{}}},
 *     summary="Verify OTP",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="otp", type="string", example="123456")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Email verified successfully"),
 *     @OA\Response(response=400, description="Verification code expired or invalid"),
 *     @OA\Response(response=403, description="Invalid token for verification")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/resend-otp",
 *     tags={"Authentication"},
 *     security={{"bearerAuth":{}}},
 *     summary="Resend OTP to user email",
 *     @OA\Response(response=201, description="OTP resent successfully"),
 *     @OA\Response(response=403, description="Invalid token"),
 *     @OA\Response(response=429, description="Too many requests")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/logout",
 *     tags={"Authentication"},
 *     security={{"bearerAuth":{}}},
 *     summary="Logout user",
 *     @OA\Response(response=200, description="Logout successful"),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 */

class AuthDocsController extends Controller
{
    //
}
