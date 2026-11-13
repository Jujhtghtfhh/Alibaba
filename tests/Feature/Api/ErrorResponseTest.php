<?php

use App\Models\User;

// The category requests below are sent by an admin.
beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(), 'api');
});

test('returns 400 when the request body is not valid JSON', function () {
    $response = $this->call(
        'POST',
        '/api/categories',
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: '{"name": "Broken",',
    );

    $response
        ->assertBadRequest()
        ->assertExactJson(['message' => 'Malformed JSON in the request body: Syntax error.']);
    $this->assertDatabaseCount('categories', 0);
});

test('returns 404 as JSON for an unknown API route even without an Accept header', function () {
    $response = $this->get('/api/unknown');

    $response
        ->assertNotFound()
        ->assertExactJson(['message' => 'Resource not found.']);
});

test('returns 422 as JSON even without an Accept header', function () {
    $response = $this->post('/api/categories', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
