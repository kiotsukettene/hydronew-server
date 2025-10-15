<?php

namespace App\Http\Controllers\Docs\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Password Reset",
 *     description="Endpoints for forgot password, verifying reset codes, resending codes, and resetting passwords."
 * )
 */

/**
 * @OA\Post(
 *     path="/api/v1/forgot-password",
 *     tags={"Password Reset"},
 *     summary="Send password reset code to email",
 *     description="Sends a 6-digit verification code to the user's email for password reset.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email"},
 *             @OA\Property(property="email", type="string", format="email", example="momo@example.com")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Password reset code sent to email"),
 *     @OA\Response(response=422, description="Validation error"),
 *     @OA\Response(response=429, description="Too many requests (wait before requesting again)")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/verify-reset-code",
 *     tags={"Password Reset"},
 *     summary="Verify reset code",
 *     description="Verifies the 6-digit reset code sent to the user's email and returns a temporary reset token.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","code"},
 *             @OA\Property(property="email", type="string", format="email", example="momo@example.com"),
 *             @OA\Property(property="code", type="string", example="123456")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Code verified successfully, reset token returned"),
 *     @OA\Response(response=400, description="Invalid or expired reset code")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/resend-reset-code",
 *     tags={"Password Reset"},
 *     summary="Resend password reset code",
 *     description="Resends a new 6-digit password reset code to the user's email. Can only be used once every 60 seconds.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email"},
 *             @OA\Property(property="email", type="string", format="email", example="momo@example.com")
 *         )
 *     ),
 *     @OA\Response(response=201, description="New reset code sent successfully"),
 *     @OA\Response(response=429, description="Please wait before requesting another code"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Post(
 *     path="/api/v1/reset-password",
 *     tags={"Password Reset"},
 *     summary="Reset password",
 *     description="Sets a new password for the user using a valid reset token obtained from verify-reset-code endpoint.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","reset_token","password"},
 *             @OA\Property(property="email", type="string", format="email", example="momo@example.com"),
 *             @OA\Property(property="reset_token", type="string", example="Xyz12345ResetTokenExample..."),
 *             @OA\Property(property="password", type="string", format="password", example="NewPassword@123")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Password reset successfully"),
 *     @OA\Response(response=400, description="Invalid or expired reset token"),
 *     @OA\Response(response=403, description="Reset token expired or invalid")
 * )
 */

class PasswordResetDocsController extends Controller
{
    //
}
