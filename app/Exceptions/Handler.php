<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'error' => 'Validation error',
                    'details' => $e->errors(),
                ], 422);
            }

            // 401 Unauthorized
            if ($e instanceof UnauthorizedHttpException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 401);
            }

            // 404 Not Found
            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Resource not found',
                ], 404);
            }

            // 405 Method Not Allowed
            if ($e instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Method not allowed',
                ], 405);
            }

            // Generic server error
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
                'debug' => env('APP_DEBUG') ? $e->getMessage() : null,
            ], 500);
        }

        return parent::render($request, $e);
    }
}
