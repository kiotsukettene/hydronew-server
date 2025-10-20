<?php

namespace App\Http\Controllers\Docs\AccountSettings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Account Settings",
 *     description="Endpoints for managing user account settings, password, and login history."
 * )
 */

/**
 * @OA\Get(
 *     path="/api/v1/manage-account",
 *     summary="Retrieve account information",
 *     description="Fetch the authenticated user's account details, including full name, email, and owned device count.",
 *     tags={"Account Settings"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Account details retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="full_name", type="string", example="Momo Revillame"),
 *                 @OA\Property(property="email", type="string", example="momo@example.com"),
 *                 @OA\Property(property="owned_devices_count", type="integer", example=5)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 *
 * @OA\Put(
 *     path="/api/v1/manage-account/{user}",
 *     summary="Update account information",
 *     description="Allows the authenticated user to update their personal information and profile picture.",
 *     tags={"Account Settings"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="user",
 *         in="path",
 *         required=true,
 *         description="User ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"first_name", "last_name"},
 *                 @OA\Property(property="first_name", type="string", example="Momo"),
 *                 @OA\Property(property="last_name", type="string", example="Revillame"),
 *                 @OA\Property(property="address", type="string", example="Caloocan City, Philippines"),
 *                 @OA\Property(property="profile_picture", type="string", format="binary", nullable=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Account updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Account updated successfully."),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="first_name", type="string", example="Momo"),
 *                 @OA\Property(property="last_name", type="string", example="Revillame"),
 *                 @OA\Property(property="email", type="string", example="momo@example.com"),
 *                 @OA\Property(property="address", type="string", example="Caloocan City, Philippines"),
 *                 @OA\Property(property="profile_picture", type="string", example="storage/profile_pictures/user1.png")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=400, description="Validation error"),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=404, description="User not found")
 * )
 *
 * @OA\Put(
 *     path="/api/v1/manage-account/{user}/update-password",
 *     summary="Update account password",
 *     description="Allows the authenticated user to change their password by providing the current password and a new one.",
 *     tags={"Account Settings"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="user",
 *         in="path",
 *         required=true,
 *         description="User ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"current_password","new_password","new_password_confirmation"},
 *             @OA\Property(property="current_password", type="string", example="OldPass@123"),
 *             @OA\Property(property="new_password", type="string", example="NewPass@456"),
 *             @OA\Property(property="new_password_confirmation", type="string", example="NewPass@456")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Password updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Password updated successfully.")
 *         )
 *     ),
 *     @OA\Response(response=400, description="Current password incorrect or validation failed"),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/manage-account/login-history",
 *     summary="Retrieve login history",
 *     description="Fetch paginated login history for the authenticated user.",
 *     tags={"Account Settings"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number for pagination",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login history retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="data", type="array",
 *                     @OA\Items(
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="ip_address", type="string", example="192.168.1.12"),
 *                         @OA\Property(property="user_agent", type="string", example="Mozilla/5.0 (Windows NT 10.0; Win64; x64)"),
 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-21T15:23:01Z")
 *                     )
 *                 ),
 *                 @OA\Property(property="per_page", type="integer", example=10),
 *                 @OA\Property(property="total", type="integer", example=45)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 */

class AccountDocsController extends Controller {}
