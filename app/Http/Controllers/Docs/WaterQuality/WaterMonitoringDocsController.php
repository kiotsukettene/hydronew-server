<?php

namespace App\Http\Controllers\Docs\WaterQuality;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
     * @OA\Get(
     *     path="/api/water-quality",
     *     summary="Get latest water quality readings",
     *     description="Fetches the latest readings for pH, TDS, turbidity, water level, and EC sensors, then evaluates if the water is safe for plants.",
     *     operationId="getWaterQuality",
     *     tags={"Water Quality"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with latest readings and plant safety evaluation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 example={
     *                     "ph": {"id": 1, "reading_value": 7.2, "reading_time": "2025-10-16T10:00:00Z", "sensor": {"type": "ph", "unit": "pH"}},
     *                     "TDS": {"id": 2, "reading_value": 900, "reading_time": "2025-10-16T10:00:00Z", "sensor": {"type": "TDS", "unit": "ppm"}},
     *                     "turbidity": {"id": 3, "reading_value": 3, "reading_time": "2025-10-16T10:00:00Z", "sensor": {"type": "turbidity", "unit": "NTU"}}
     *                 }
     *             ),
     *             @OA\Property(property="quality", type="string", example="Safe for plants")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Bearer token missing or invalid"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */

class WaterMonitoringDocsController extends Controller
{
    //
}
