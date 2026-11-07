<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * POST /api/register
     *
     * Sesuai ketentuan "seluruh endpoint selain login hanya dapat diakses
     * dengan token", pembuatan akun baru dilakukan oleh pengguna yang sudah
     * terautentikasi (bukan endpoint publik).
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create($validated);

        Log::info('User registered', [
            'event' => 'auth.user_registered',
            'actor_id' => $request->user()?->id,
            'target_user_id' => $user->id,
            'action' => 'register user',
            'reason' => 'authenticated user created a new account',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /** POST /api/login */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            Log::warning('Login failed', [
                'event' => 'auth.login_failed',
                'actor_id' => $user?->id,
                'action' => 'login',
                'reason' => 'invalid credentials',
            ]);

            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        Log::info('Login succeeded', [
            'event' => 'auth.login_succeeded',
            'actor_id' => $user->id,
            'action' => 'login',
            'reason' => 'valid credentials',
        ]);

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /** GET /api/me */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    /** POST /api/logout */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        Log::info('Logout succeeded', [
            'event' => 'auth.logout_succeeded',
            'actor_id' => $request->user()->id,
            'action' => 'logout',
            'reason' => 'user requested token revocation',
        ]);

        return response()->json(['message' => 'Logout berhasil.']);
    }
}
