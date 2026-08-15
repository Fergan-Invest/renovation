<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\AuthController;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SimpleTokenAuth
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $token = (string) $request->bearerToken();
        $email = (string) env('ADMIN_EMAIL', 'invest@tutash.local');
        $validTokens = [
            AuthController::tokenFor($email),
            AuthController::tokenFor($email, now()->subDay()->toDateString()),
        ];

        foreach ($validTokens as $validToken) {
            if (hash_equals($validToken, $token)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Avval tizimga kiring.',
        ], 401);
    }
}
