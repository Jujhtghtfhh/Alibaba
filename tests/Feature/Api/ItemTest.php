<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;

// Creating needs a logged-in user; updating and deleting needs the author (see the ->for($this->user) below).
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'api');
});

describe('GET /api/items', function () {
    test('lists items page by page', function () {
        Item::factory()->count(3)->create();

        $response = $this->getJson('/api/items?per_page=2&page=2');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('links.next', null);
    });

    test('filters items by category, price range and name', function () {
        $category = Category::factory()->create();
        $wantedItem = Item::factory()->for($category)->create(['name' => 'USB-C Charger', 'price' => 18.49]);
        Item::factory()->for($category)->create(['name' => 'USB-C Cable', 'price' => 3.20]);
        Item::factory()->for($category)->create(['name' => 'Smart Watch', 'price' => 29.90]);
        Item::factory()->create(['name' => 'USB-C Hub', 'price' => 19.99]);

        $response = $this->getJson("/api/items?category_id={$category->id}&min_price=10&max_price=25&search=usb");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $wantedItem->id);
    });

    test('applies a max_price of 0 instead of ignoring it', function () {
        Item::factory()->create(['price' => 5]);

        $response = $this->getJson('/api/items?max_price=0');

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    test('returns 422 when a filter value is invalid', function () {
        $response = $this->getJson('/api/items?min_price=cheap&per_page=500');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'min_price' => 'The min price field must be a number.',
                'per_page' => 'The per page field must be between 1 and 100.',
            ]);
    });
});

describe('POST /api/items', function () {
    test('creates an item and returns 201 with its location', function () {
        $category = Category::factory()->create(['name' => 'Electronics']);

        $response = $this->postJson('/api/items', [
            'category_id' => $category->id,
            'name' => 'Wireless Earbuds',
            'description' => 'Bluetooth 5.3 earbuds.',
            'price' => 15.99,
            'stock' => 120,
        ]);

        $item = Item::firstWhere('name', 'Wireless Earbuds');

        $response
            ->assertCreated()
            ->assertHeader('Location', route('api.items.show', $item))
            ->assertJsonPath('data.category_name', 'Electronics')
            ->assertJsonPath('data.price', 15.99)
            ->assertJsonPath('data.reviews_count', 0)
            ->assertJsonPath('data.average_rating', null);
        expect($item->stock)->toBe(120);
    });

    test('returns 422 for an unknown category, a negative price and a fractional stock', function () {
        $response = $this->postJson('/api/items', [
            'category_id' => 999,
            'name' => 'Broken item',
            'price' => -5,
            'stock' => 1.5,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'category_id' => 'The selected category id is invalid.',
                'price' => 'The price field must be at least 0.01.',
                'stock' => 'The stock field must be an integer.',
            ]);
        $this->assertDatabaseCount('items', 0);
    });
});

describe('GET /api/items/{item}', function () {
    test('shows an item with its category, rating summary and links', function () {
        $item = Item::factory()->create();
        Review::factory()->for($item)->count(3)->sequence(['rating' => 5], ['rating' => 4], ['rating' => 4])->create();

        $response = $this->getJson("/api/items/{$item->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.category_name', $item->category->name)
            ->assertJsonPath('data.reviews_count', 3)
            ->assertJsonPath('data.average_rating', 4.3)
            ->assertJsonPath('data._links.category.href', route('api.categories.show', $item->category_id))
            ->assertJsonPath('data._links.reviews.href', route('api.reviews.index', ['item_id' => $item->id]));
    });

    test('returns 404 when the item does not exist', function () {
        $response = $this->getJson('/api/items/999');

        $response
            ->assertNotFound()
            ->assertExactJson(['message' => 'Item not found.']);
    });
});

describe('PUT and PATCH /api/items/{item}', function () {
    test('replaces an item with PUT', function () {
        $item = Item::factory()->for($this->user)->create();
        $newCategory = Category::factory()->create();

        $response = $this->putJson("/api/items/{$item->id}", [
            'category_id' => $newCategory->id,
            'name' => 'Renamed item',
            'price' => 9.99,
            'stock' => 5,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed item')
            ->assertJsonPath('data.category_id', $newCategory->id);
        expect($item->fresh()->price)->toBe('9.99');
    });

    test('returns 422 when PUT does not send the whole item', function () {
        $item = Item::factory()->for($this->user)->create();

        $response = $this->putJson("/api/items/{$item->id}", ['price' => 9.99]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'name', 'stock']);
    });

    test('changes only the sent fields with PATCH', function () {
        $item = Item::factory()->for($this->user)->create(['name' => 'Yoga Mat', 'stock' => 10]);

        $response = $this->patchJson("/api/items/{$item->id}", ['stock' => 0]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Yoga Mat')
            ->assertJsonPath('data.stock', 0);
    });
});

describe('DELETE /api/items/{item}', function () {
    test('deletes an item together with its reviews and returns 204', function () {
        $item = Item::factory()->for($this->user)->create();
        $review = Review::factory()->for($item)->create();

        $response = $this->deleteJson("/api/items/{$item->id}");

        $response->assertNoContent();
        $this->assertModelMissing($item);
        $this->assertModelMissing($review);
    });
});
