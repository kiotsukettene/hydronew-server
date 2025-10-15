<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Hydronew API Documentation",
 *     version="1.0.0"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     in="header",
 *     name="Authorization",
 *     description="Use the token obtained from the login endpoint. Example: 'Bearer {your_token}'"
 * )
 */

abstract class Controller
{
    //
}
