<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;

/**
 * Log in through the API and return the token pair.
 *
 * @return array{token_type: string, access_token: string, expires_in: int, refresh_token: string}
 */
function loginAs(User $user): array
{
    return test()->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->json();
}

/**
 * Read the claims (payload) of a JWT without checking it.
 *
 * @return array<string, mixed>
 */
function jwtClaims(string $token): array
{
    return json_decode(base64_decode(strtr(explode('.', $token)[1], '-_', '+/')), true);
}

describe('register, login, refresh, logout', function () {
    test('register creates an ordinary user and returns tokens', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Rūta',
            'email' => 'ruta@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.role', 'user')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['access_token', 'refresh_token', 'expires_in']);
        expect(User::firstWhere('email', 'ruta@example.com')->role)->toBe('user');
    });

    test('login returns a short-lived access token with the user ID and role inside', function () {
        $admin = User::factory()->admin()->create();

        $tokens = loginAs($admin);
        $claims = jwtClaims($tokens['access_token']);

        expect($claims['sub'])->toBe($admin->id)
            ->and($claims['role'])->toBe('admin')
            ->and($claims['exp'] - $claims['iat'])->toBe(15 * 60)
            ->and($tokens['expires_in'])->toBe(15 * 60);
    });

    test('login fails with a wrong password', function () {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Invalid email or password.']);
    });

    test('refresh returns new tokens and the old refresh token stops working', function () {
        $tokens = loginAs(User::factory()->create());

        $newTokens = $this->postJson('/api/auth/refresh', ['refresh_token' => $tokens['refresh_token']])
            ->assertOk()
            ->json();

        expect($newTokens['refresh_token'])->not->toBe($tokens['refresh_token']);
        $this->postJson('/api/auth/refresh', ['refresh_token' => $tokens['refresh_token']])->assertUnauthorized();
    });

    test('an expired refresh token is rejected', function () {
        $tokens = loginAs(User::factory()->create());

        $this->travel(8)->days();

        $this->postJson('/api/auth/refresh', ['refresh_token' => $tokens['refresh_token']])->assertUnauthorized();
    });

    test('an expired access token is rejected', function () {
        $tokens = loginAs(User::factory()->create());

        $this->travel(16)->minutes();

        $this->postJson('/api/reviews', [], ['Authorization' => "Bearer {$tokens['access_token']}"])->assertUnauthorized();
    });

    test('a token with a changed payload is rejected', function () {
        $tokens = loginAs(User::factory()->create());
        [$header, $payload, $signature] = explode('.', $tokens['access_token']);
        $claims = jwtClaims($tokens['access_token']);
        $fakePayload = rtrim(strtr(base64_encode(json_encode([...$claims, 'role' => 'admin'])), '+/', '-_'), '=');

        $this->postJson('/api/categories', ['name' => 'Hacked'], ['Authorization' => "Bearer {$header}.{$fakePayload}.{$signature}"])
            ->assertUnauthorized();
    });

    test('logout makes both the access token and the refresh token invalid', function () {
        $tokens = loginAs(User::factory()->create());
        $headers = ['Authorization' => "Bearer {$tokens['access_token']}"];

        $this->postJson('/api/auth/logout', [], $headers)->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/reviews', [], $headers)->assertUnauthorized();
        $this->postJson('/api/auth/refresh', ['refresh_token' => $tokens['refresh_token']])->assertUnauthorized();
    });
});

describe('authorization by role and ID', function () {
    test('a guest can read but cannot create', function () {
        $this->getJson('/api/items')->assertOk();
        $this->postJson('/api/items', [])->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
    });

    test('only an admin can create a category', function () {
        $userTokens = loginAs(User::factory()->create());
        $adminTokens = loginAs(User::factory()->admin()->create());

        $this->postJson('/api/categories', ['name' => 'Garden'], ['Authorization' => "Bearer {$userTokens['access_token']}"])
            ->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/categories', ['name' => 'Garden'], ['Authorization' => "Bearer {$adminTokens['access_token']}"])
            ->assertCreated();
        expect(Category::count())->toBe(1);
    });

    test('a user cannot update or delete another user\'s review, but an admin can', function () {
        $review = Review::factory()->create();
        $otherUserTokens = loginAs(User::factory()->create());
        $adminTokens = loginAs(User::factory()->admin()->create());

        $this->patchJson("/api/reviews/{$review->id}", ['rating' => 1], ['Authorization' => "Bearer {$otherUserTokens['access_token']}"])
            ->assertForbidden();
        $this->deleteJson("/api/reviews/{$review->id}", [], ['Authorization' => "Bearer {$otherUserTokens['access_token']}"])
            ->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->deleteJson("/api/reviews/{$review->id}", [], ['Authorization' => "Bearer {$adminTokens['access_token']}"])
            ->assertNoContent();
    });

    test('a new review belongs to the logged-in user, who can edit it', function () {
        $user = User::factory()->create();
        $tokens = loginAs($user);
        $headers = ['Authorization' => "Bearer {$tokens['access_token']}"];
        $item = Item::factory()->create();

        $reviewId = $this->postJson('/api/reviews', ['item_id' => $item->id, 'author_name' => 'Me', 'rating' => 4, 'comment' => 'Good.'], $headers)
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->json('data.id');

        $this->patchJson("/api/reviews/{$reviewId}", ['rating' => 5], $headers)
            ->assertOk()
            ->assertJsonPath('data.rating', 5);
    });
});
