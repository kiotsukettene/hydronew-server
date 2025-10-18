<?php

namespace App\Http\Controllers\Docs\Devices;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Devices",
 *     description="Endpoint for managing user devices."
 * )
 */

/**
     * @OA\Get(
     *     path="/api/devices",
     *     summary="List all associated devices for the authenticated user",
     *     description="Retrieves a list of devices owned by the authenticated user.",
     *     tags={"Devices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Devices retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="devices",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="HydroNew Device A-1"),
     *                     @OA\Property(property="serial_number", type="string", example="MFC-1204328HD0B45"),
     *                     @OA\Property(property="status", type="string", example="connected"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="No devices found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     * @OA\Post(
     *     path="/api/devices/connect",
     *     summary="Connect a device to the authenticated user",
     *     description="Connects a device using its serial number to the logged-in user if it's not already connected.",
     *     tags={"Devices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"serial_number"},
     *             @OA\Property(property="serial_number", type="string", example="MFC-1204328HD0B45")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device connected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Device connected successfully."),
     *             @OA\Property(property="device", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="serial_number", type="string", example="MFC-1204328HD0B45"),
     *                 @OA\Property(property="status", type="string", example="connected")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Device not found"),
     *     @OA\Response(response=409, description="Device already connected to another user"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )

     * @OA\Post(
     *     path="/api/devices/{device}/disconnect",
     *     summary="Disconnect a device from the authenticated user",
     *     description="Sets the device status to 'not connected'. Only the device owner can disconnect it.",
     *     tags={"Devices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="device",
     *         in="path",
     *         required=true,
     *         description="Device ID or serial number to disconnect",
     *         @OA\Schema(type="string", example="MFC-1204328HD0B45")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device disconnected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Device disconnected successfully."),
     *             @OA\Property(property="device", type="object",
     *                 @OA\Property(property="serial_number", type="string", example="MFC-1204328HD0B45"),
     *                 @OA\Property(property="status", type="string", example="not connected")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="You are not authorized to disconnect this device"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */

class DeviceDocsController extends Controller
{
    //
}
