<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithSecretKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Get the key from the request header (case-insensitive)
        $key = $request->header('X-Api-Key');

        // 2. Get the valid key directly from the .env file
        // This is the corrected line.
        $validKey = env('SENSOR_API_SECRET');

        // 3. Compare them
        if ($key === $validKey) {
            return $next($request);
        }

        // 4. Fail if no match
        return response()->json(['message' => 'Unauthorized'], 401);
    }
}

