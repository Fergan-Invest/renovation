<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = (string) env('ADMIN_EMAIL', 'invest@tutash.local');
        $password = (string) env('ADMIN_PASSWORD', 'Password123!');

        if (
            ! hash_equals($email, (string) $credentials['email']) ||
            ! hash_equals($password, (string) $credentials['password'])
        ) {
            return response()->json([
                'message' => 'Login yoki parol noto‘g‘ri.',
            ], 422);
        }

        return response()->json([
            'token' => self::tokenFor($email),
            'user' => [
                'email' => $email,
            ],
        ]);
    }

    public static function tokenFor(string $email, ?string $date = null): string
    {
        return hash_hmac(
            'sha256',
            $email.'|'.($date ?: now()->toDateString()),
            (string) config('app.key')
        );
    }
}
