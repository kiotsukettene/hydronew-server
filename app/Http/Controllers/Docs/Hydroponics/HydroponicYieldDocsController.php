<?php

namespace App\Http\Controllers\Docs\Hydroponics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Hydroponic Yields",
 *     description="Operations related to hydroponic yields and harvest tracking"
 * )
 */

/**
 * @OA\Get(
 *     path="/api/hydroponics/yields",
 *     summary="List all hydroponic yields for the authenticated user",
 *     description="Retrieves all yield records from hydroponic setups belonging to the authenticated user.",
 *     operationId="getHydroponicYields",
 *     tags={"Hydroponic Yields"},
 *     security={{"sanctum":{}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="List of hydroponic yields retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="hydroponic_setup_id", type="integer", example=5),
 *                     @OA\Property(property="harvest_status", type="string", example="not_harvested"),
 *                     @OA\Property(property="growth_stage", type="string", example="seedling"),
 *                     @OA\Property(property="health_status", type="string", example="good"),
 *                     @OA\Property(property="actual_yield", type="number", example=12.5),
 *                     @OA\Property(property="harvest_date", type="string", format="date-time", example="2025-10-21T14:00:00Z"),
 *                     @OA\Property(property="notes", type="string", example="Healthy and uniform growth."),
 *                     @OA\Property(property="system_generated", type="boolean", example=true)
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated - user not logged in"
 *     )
 * )

 * @OA\Get(
 *     path="/api/hydroponics/yields/{setup}",
 *     summary="Get yield details for a specific hydroponic setup",
 *     description="Retrieves all yield records for a given hydroponic setup.",
 *     operationId="showHydroponicYield",
 *     tags={"Hydroponic Yields"},
 *     security={{"sanctum":{}}},
 *
 *     @OA\Parameter(
 *         name="setup",
 *         in="path",
 *         required=true,
 *         description="The ID of the hydroponic setup",
 *         @OA\Schema(type="integer", example=3)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Hydroponic yield details retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="hydroponic_setup_id", type="integer", example=3),
 *                     @OA\Property(property="growth_stage", type="string", example="vegetative"),
 *                     @OA\Property(property="health_status", type="string", example="excellent"),
 *                     @OA\Property(property="harvest_status", type="string", example="not_harvested"),
 *                     @OA\Property(property="system_generated", type="boolean", example=true)
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=404, description="Setup not found or no yields available"),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )

 * @OA\Put(
 *     path="/api/hydroponics/yields/{yield}/update-actual",
 *     summary="Update the actual yield for a harvest",
 *     description="Records the actual yield and marks it as harvested, including optional notes and automatic harvest date.",
 *     operationId="updateActualYield",
 *     tags={"Hydroponic Yields"},
 *     security={{"sanctum":{}}},
 *
 *     @OA\Parameter(
 *         name="yield",
 *         in="path",
 *         required=true,
 *         description="The ID of the hydroponic yield record to update",
 *         @OA\Schema(type="integer", example=7)
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"actual_yield"},
 *             @OA\Property(property="actual_yield", type="number", example=15.2, description="Actual yield amount"),
 *             @OA\Property(property="notes", type="string", example="Yield recorded after 60 days of growth.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Actual yield updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Actual yield and harvest date recorded successfully."),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=7),
 *                 @OA\Property(property="actual_yield", type="number", example=15.2),
 *                 @OA\Property(property="harvest_status", type="string", example="harvested"),
 *                 @OA\Property(property="harvest_date", type="string", format="date-time", example="2025-10-21T14:00:00Z"),
 *                 @OA\Property(property="notes", type="string", example="Yield recorded after 60 days of growth.")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation failed",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The actual_yield field is required.")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 */

class HydroponicYieldDocsController extends Controller
{
    //
}
