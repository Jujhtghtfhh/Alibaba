<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Creates and checks the tokens used by the API.
 *
 * - Access token: a JWT signed with HS256. It holds the user's ID ("sub"), role ("role"),
 *   the ID of the login it belongs to ("sid") and its expiry time ("exp"). Lives 15 minutes.
 * - Refresh token: a random string. Only its SHA-256 hash is stored in the refresh_tokens table.
 *   Lives 7 days and is used to get a new pair of tokens without logging in again.
 */
class JwtService
{
    /**
     * Start a new login for the user and return both tokens.
     *
     * @return array{token_type: string, access_token: string, expires_in: int, refresh_token: string}
     */
    public function issueTokens(User $user): array
    {
        $plainRefreshToken = Str::random(64);

        $refreshToken = $user->refreshTokens()->create([
            'token' => hash('sha256', $plainRefreshToken),
            'expires_at' => now()->addMinutes(config('jwt.refresh_ttl')),
        ]);

        $now = now()->getTimestamp();
        $accessTtlSeconds = config('jwt.access_ttl') * 60;

        $accessToken = $this->encode([
            'sub' => $user->id,
            'role' => $user->role,
            'sid' => $refreshToken->id,
            'iat' => $now,
            'exp' => $now + $accessTtlSeconds,
        ]);

        return [
            'token_type' => 'Bearer',
            'access_token' => $accessToken,
            'expires_in' => $accessTtlSeconds,
            'refresh_token' => $plainRefreshToken,
        ];
    }

    /**
     * Find the stored (not expired) refresh token that matches the plain token the client sent.
     */
    public function findRefreshToken(string $plainRefreshToken): ?RefreshToken
    {
        return RefreshToken::query()
            ->where('token', hash('sha256', $plainRefreshToken))
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Return the user of a valid access token, or null when the token is missing, broken,
     * expired or its login was ended with logout.
     *
     * The user's ID and role are taken from the token.
     */
    public function userFromAccessToken(?string $accessToken): ?User
    {
        $claims = $this->decode((string) $accessToken);

        if ($claims === null) {
            return null;
        }

        $loginStillActive = RefreshToken::query()
            ->whereKey($claims['sid'] ?? null)
            ->where('user_id', $claims['sub'] ?? null)
            ->exists();

        if (! $loginStillActive) {
            return null;
        }

        $user = User::find($claims['sub']);
        $user?->setAttribute('role', $claims['role']);

        return $user;
    }

    /**
     * Build a signed JWT: base64url(header).base64url(payload).base64url(signature)
     *
     * @param  array<string, mixed>  $claims
     */
    public function encode(array $claims): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode($claims));

        return "{$header}.{$payload}.{$this->sign("{$header}.{$payload}")}";
    }

    /**
     * Return the claims of a JWT, or null when the signature is wrong or the token has expired.
     *
     * @return array<string, mixed>|null
     */
    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        if (! hash_equals($this->sign("{$header}.{$payload}"), $signature)) {
            return null;
        }

        $claims = json_decode($this->base64UrlDecode($payload), true);

        if (! is_array($claims) || ! isset($claims['exp']) || $claims['exp'] < now()->getTimestamp()) {
            return null;
        }

        return $claims;
    }

    private function sign(string $data): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $data, config('jwt.secret'), true));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
