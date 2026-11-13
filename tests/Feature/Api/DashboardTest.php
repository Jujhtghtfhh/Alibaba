<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\Review;

test('dashboard combines categories, items and reviews into one overview', function () {
    $category = Category::factory()->create(['name' => 'Electronics']);
    $bestItem = Item::factory()->for($category)->create(['stock' => 0]);
    $otherItem = Item::factory()->for($category)->create(['stock' => 10]);
    Review::factory()->for($bestItem)->create(['rating' => 5, 'created_at' => now()->subDays(3)]);
    Review::factory()->for($otherItem)->create(['rating' => 3, 'created_at' => now()->subDays(2)]);
    $latestReview = Review::factory()->for($otherItem)->create(['rating' => 4, 'created_at' => now()->subDay()]);

    $response = $this->getJson('/api/dashboard');

    $response
        ->assertOk()
        ->assertJsonPath('data.totals', [
            'categories' => 1,
            'items' => 2,
            'items_out_of_stock' => 1,
            'reviews' => 3,
            'average_rating' => 4,
        ])
        ->assertJsonPath('data.categories.0.name', 'Electronics')
        ->assertJsonPath('data.categories.0.items_count', 2)
        ->assertJsonPath('data.top_rated_items.0.id', $bestItem->id)
        ->assertJsonPath('data.top_rated_items.0.category_name', 'Electronics')
        ->assertJsonPath('data.latest_reviews.0.id', $latestReview->id)
        ->assertJsonPath('data.latest_reviews.0.item_name', $otherItem->name)
        ->assertJsonPath('_links.items.href', route('api.items.index'));
});
