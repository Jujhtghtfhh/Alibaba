<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\User;

// Creating, updating and deleting categories needs an admin.
beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(), 'api');
});

describe('GET /api/categories', function () {
    test('lists categories page by page with hypermedia links', function () {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories?per_page=2');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('links.next', route('api.categories.index', ['per_page' => 2, 'page' => 2]))
            ->assertJsonStructure([
                'data' => [['id', 'name', 'description', 'items_count', 'created_at', 'updated_at', '_links' => ['self', 'update', 'delete', 'items']]],
            ]);
    });

    test('filters categories by name', function () {
        Category::factory()->create(['name' => 'Electronics']);
        Category::factory()->create(['name' => 'Garden']);

        $response = $this->getJson('/api/categories?search=electro');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Electronics');
    });
});

describe('POST /api/categories', function () {
    test('creates a category and returns 201 with its location', function () {
        $response = $this->postJson('/api/categories', [
            'name' => 'Garden',
            'description' => 'Tools and decor for gardens.',
        ]);

        $category = Category::firstWhere('name', 'Garden');

        $response
            ->assertCreated()
            ->assertHeader('Location', route('api.categories.show', $category))
            ->assertJsonPath('data.name', 'Garden')
            ->assertJsonPath('data.items_count', 0)
            ->assertJsonPath('data._links.self.href', route('api.categories.show', $category));
        expect($category->description)->toBe('Tools and decor for gardens.');
    });

    test('returns 422 when the name is missing', function () {
        $response = $this->postJson('/api/categories', ['description' => 'No name']);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'The name field is required.']);
        $this->assertDatabaseCount('categories', 0);
    });

    test('returns 422 when the name is already taken', function () {
        Category::factory()->create(['name' => 'Garden']);

        $response = $this->postJson('/api/categories', ['name' => 'Garden']);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'The name has already been taken.']);
    });
});

describe('GET /api/categories/{category}', function () {
    test('shows a category with its item count', function () {
        $category = Category::factory()->has(Item::factory()->count(2))->create();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.items_count', 2)
            ->assertJsonPath('data._links.items.href', route('api.items.index', ['category_id' => $category->id]));
    });

    test('returns 404 when the category does not exist', function () {
        $response = $this->getJson('/api/categories/999');

        $response
            ->assertNotFound()
            ->assertExactJson(['message' => 'Category not found.']);
    });
});

describe('PUT /api/categories/{category}', function () {
    test('updates a category', function () {
        $category = Category::factory()->create(['name' => 'Garden']);

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Garden & Patio',
            'description' => 'Everything for outdoor living.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Garden & Patio');
        expect($category->fresh()->description)->toBe('Everything for outdoor living.');
    });

    test('keeps its own name when the name is sent unchanged', function () {
        $category = Category::factory()->create(['name' => 'Garden']);

        $response = $this->putJson("/api/categories/{$category->id}", ['name' => 'Garden']);

        $response->assertOk();
    });
});

describe('DELETE /api/categories/{category}', function () {
    test('deletes a category together with its items and returns 204', function () {
        $category = Category::factory()->create();
        $item = Item::factory()->for($category)->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertNoContent();
        $this->assertModelMissing($category);
        $this->assertModelMissing($item);
    });
});
