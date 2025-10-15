<?php

namespace App\Http\Controllers\Docs\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Endpoint for retrieving dashboard data."
 * )
 */

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Endpoints related to the dashboard overview and sensor readings."
 * )
 */

/**
 * @OA\Get(
 *     path="/api/v1/dashboard",
 *     tags={"Dashboard"},
 *     summary="Get latest pH level and user greeting",
 *     description="Retrieves the most recent pH sensor reading and its status for the authenticated user.",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Latest pH reading fetched successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="user", type="string", example="Hello, Marianne!"),
 *             @OA\Property(
 *                 property="ph_level",
 *                 type="object",
 *                 @OA\Property(property="value", type="number", format="float", example=6.8),
 *                 @OA\Property(property="unit", type="string", example="pH"),
 *                 @OA\Property(property="time", type="string", format="date-time", example="2025-10-16T14:30:00Z"),
 *                 @OA\Property(property="status", type="string", example="Optimal")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="No pH sensor found"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized (missing or invalid token)"
 *     )
 * )
 */


class DashboardDocsController extends Controller
{
    //
}
