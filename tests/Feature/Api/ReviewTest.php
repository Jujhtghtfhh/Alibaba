<?php

use App\Models\Item;
use App\Models\Review;
use App\Models\User;

// Creating needs a logged-in user; updating and deleting needs the author (see the ->for($this->user) below).
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'api');
});

describe('GET /api/reviews', function () {
    test('lists the reviews of one item with at least the given rating, newest first', function () {
        $item = Item::factory()->create();
        $olderReview = Review::factory()->for($item)->create(['rating' => 5, 'created_at' => now()->subDays(2)]);
        $newerReview = Review::factory()->for($item)->create(['rating' => 4, 'created_at' => now()->subDay()]);
        Review::factory()->for($item)->create(['rating' => 2]);
        Review::factory()->create(['rating' => 5]);

        $response = $this->getJson("/api/reviews?item_id={$item->id}&min_rating=4");

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newerReview->id)
            ->assertJsonPath('data.1.id', $olderReview->id)
            ->assertJsonPath('data.0.item_name', $item->name);
    });
});

describe('POST /api/reviews', function () {
    test('creates a review and returns 201 with its location', function () {
        $item = Item::factory()->create();

        $response = $this->postJson('/api/reviews', [
            'item_id' => $item->id,
            'author_name' => 'Tomas K.',
            'rating' => 5,
            'comment' => 'Arrived in 9 days, works great.',
        ]);

        $review = Review::firstWhere('author_name', 'Tomas K.');

        $response
            ->assertCreated()
            ->assertHeader('Location', route('api.reviews.show', $review))
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data._links.item.href', route('api.items.show', $item));
        expect($review->item_id)->toBe($item->id);
    });

    test('returns 422 when the rating is outside 1 to 5', function () {
        $item = Item::factory()->create();

        $response = $this->postJson('/api/reviews', [
            'item_id' => $item->id,
            'author_name' => 'Tomas K.',
            'rating' => 6,
            'comment' => 'Too good.',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating' => 'The rating field must be between 1 and 5.']);
        $this->assertDatabaseCount('reviews', 0);
    });
});

describe('GET /api/reviews/{review}', function () {
    test('shows a review', function () {
        $review = Review::factory()->create();

        $response = $this->getJson("/api/reviews/{$review->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $review->id)
            ->assertJsonPath('data.comment', $review->comment);
    });

    test('returns 404 when the review does not exist', function () {
        $response = $this->getJson('/api/reviews/999');

        $response
            ->assertNotFound()
            ->assertExactJson(['message' => 'Review not found.']);
    });
});

describe('PUT /api/reviews/{review}', function () {
    test('updates a review', function () {
        $review = Review::factory()->for($this->user)->create(['rating' => 2]);

        $response = $this->putJson("/api/reviews/{$review->id}", [
            'item_id' => $review->item_id,
            'author_name' => $review->author_name,
            'rating' => 4,
            'comment' => 'The seller sent a replacement, now it works.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.rating', 4);
        expect($review->fresh()->comment)->toBe('The seller sent a replacement, now it works.');
    });
});

describe('DELETE /api/reviews/{review}', function () {
    test('deletes a review and returns 204', function () {
        $review = Review::factory()->for($this->user)->create();

        $response = $this->deleteJson("/api/reviews/{$review->id}");

        $response->assertNoContent();
        $this->assertModelMissing($review);
    });
});
