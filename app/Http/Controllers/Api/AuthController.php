<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(private JwtService $jwt) {}

    /**
     * REGISTER: POST /api/auth/register
     *
     * Creates an ordinary user (role "user") and logs them in right away.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create($data);

        return response()->json([
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
            ...$this->jwt->issueTokens($user),
        ], Response::HTTP_CREATED);
    }

    /**
     * LOGIN: POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::firstWhere('email', $credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid email or password.'], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json($this->jwt->issueTokens($user));
    }

    /**
     * REFRESH: POST /api/auth/refresh
     *
     * Swaps a valid refresh token for a new access token and a new refresh token.
     * The old refresh token (and the access tokens issued with it) stop working.
     */
    public function refresh(Request $request): JsonResponse
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $refreshToken = $this->jwt->findRefreshToken($data['refresh_token']);

        if (! $refreshToken) {
            return response()->json(['message' => 'Invalid or expired refresh token.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $refreshToken->user;
        $refreshToken->delete();

        return response()->json($this->jwt->issueTokens($user));
    }

    /**
     * LOGOUT: POST /api/auth/logout
     *
     * Deletes the refresh token of the current login. Because every access token
     * points to its refresh token ("sid"), the access token stops working too.
     */
    public function logout(Request $request): Response
    {
        $claims = $this->jwt->decode((string) $request->bearerToken());

        $request->user()->refreshTokens()->whereKey($claims['sid'])->delete();

        return response()->noContent();
    }
}
