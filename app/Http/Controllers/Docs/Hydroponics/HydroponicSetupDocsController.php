<?php

namespace App\Http\Controllers\Docs\Hydroponics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Hydroponic Setup",
 *     description="Operations related to hydroponic setups and automatic yield creation"
 * )
 */


/**
 * @OA\Get(
 *     path="/api/v1/hydroponic-setups",
 *     summary="Get all hydroponic setups for the authenticated user",
 *     description="Fetches a paginated list of hydroponic setups owned by the logged-in user.",
 *     operationId="getHydroponicSetups",
 *     tags={"Hydroponic Setup"},
 *     security={{"bearerAuth": {}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="List of hydroponic setups",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="data", type="array",
 *                     @OA\Items(
 *                         @OA\Property(property="id", type="integer", example=3),
 *                         @OA\Property(property="crop_name", type="string", example="Lettuce"),
 *                         @OA\Property(property="number_of_crops", type="integer", example=50),
 *                         @OA\Property(property="bed_size", type="string", example="medium"),
 *                         @OA\Property(property="nutrient_solution", type="string", example="AB Mix"),
 *                         @OA\Property(property="target_ph_min", type="number", format="float", example=5.5),
 *                         @OA\Property(property="target_ph_max", type="number", format="float", example=6.5),
 *                         @OA\Property(property="target_tds_min", type="integer", example=600),
 *                         @OA\Property(property="target_tds_max", type="integer", example=800),
 *                         @OA\Property(property="water_amount", type="string", example="100L"),
 *                         @OA\Property(property="setup_date", type="string", format="date-time", example="2025-10-21T04:00:00Z"),
 *                         @OA\Property(property="status", type="string", example="active")
 *                     )
 *                 ),
 *                 @OA\Property(property="total", type="integer", example=2)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Post(
 *     path="/api/v1/hydroponic-setups",
 *     summary="Create a new hydroponic setup",
 *     description="Creates a new hydroponic setup for the authenticated user and automatically generates an initial yield record.",
 *     operationId="createHydroponicSetup",
 *     tags={"Hydroponic Setup"},
 *     security={{"bearerAuth": {}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"crop_name", "number_of_crops", "bed_size", "nutrient_solution", "target_ph_min", "target_ph_max", "target_tds_min", "target_tds_max", "water_amount"},
 *             @OA\Property(property="crop_name", type="string", example="Lettuce"),
 *             @OA\Property(property="number_of_crops", type="integer", example=50),
 *             @OA\Property(property="bed_size", type="string", enum={"small","medium","large"}, example="medium"),
 *             @OA\Property(property="pump_config", type="object", example={"pumpA": true, "pumpB": false}),
 *             @OA\Property(property="nutrient_solution", type="string", example="AB Mix"),
 *             @OA\Property(property="target_ph_min", type="number", format="float", example=5.5),
 *             @OA\Property(property="target_ph_max", type="number", format="float", example=6.5),
 *             @OA\Property(property="target_tds_min", type="integer", example=600),
 *             @OA\Property(property="target_tds_max", type="integer", example=800),
 *             @OA\Property(property="water_amount", type="string", example="100L")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Hydroponic setup created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Hydroponic setup created successfully."),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=10),
 *                 @OA\Property(property="crop_name", type="string", example="Lettuce"),
 *                 @OA\Property(property="bed_size", type="string", example="medium"),
 *                 @OA\Property(property="status", type="string", example="active"),
 *                 @OA\Property(property="setup_date", type="string", format="date-time", example="2025-10-21T04:00:00Z")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=400, description="Validation error"),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

class HydroponicSetupDocsController extends Controller
{
    //
}
