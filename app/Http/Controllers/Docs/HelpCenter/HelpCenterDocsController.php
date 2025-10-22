<?php

namespace App\Http\Controllers\Docs\HelpCenter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


/**
 * @OA\Tag(
 *     name="Help Center",
 *     description="Endpoint for frequently ask questions."
 * )
 */

/**
     * @OA\Get(
     *     path="/api/help-center",
     *     summary="Get paginated Help Center FAQs",
     *     description="Retrieve a list of Help Center questions and answers. Supports optional search filtering and pagination.",
     *     operationId="getHelpCenterList",
     *     tags={"Help Center"},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term to filter questions or answers",
     *         required=false,
     *         @OA\Schema(type="string", example="lettuce")
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of Help Center items",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="question", type="string", example="What types of plants can I grow in this system?"),
     *                     @OA\Property(property="answer", type="string", example="This system is designed exclusively for growing lettuce...")
     *                 )
     *             ),
     *             @OA\Property(property="filters", type="object",
     *                 @OA\Property(property="search", type="string", example="lettuce")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request or bad parameters"
     *     ),
     * )
     */

class HelpCenterDocsController extends Controller
{
    //
}
