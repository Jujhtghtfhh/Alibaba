<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ItemResource;
use App\Http\Resources\ReviewResource;
use App\Models\Category;
use App\Models\Item;
use App\Models\Review;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * DASHBOARD: GET /api/dashboard
     *
     * A shop overview built from all three entities: categories, items and reviews.
     */
    public function __invoke(): JsonResponse
    {
        $categories = Category::query()
            ->withCount('items')
            ->orderBy('name')
            ->get();

        $topRatedItems = Item::query()
            ->with('category')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->has('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->limit(5)
            ->get();

        $latestReviews = Review::query()
            ->with('item')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'totals' => [
                    'categories' => Category::count(),
                    'items' => Item::count(),
                    'items_out_of_stock' => Item::where('stock', 0)->count(),
                    'reviews' => Review::count(),
                    'average_rating' => round((float) Review::avg('rating'), 1),
                ],
                'categories' => CategoryResource::collection($categories),
                'top_rated_items' => ItemResource::collection($topRatedItems),
                'latest_reviews' => ReviewResource::collection($latestReviews),
            ],
            '_links' => [
                'self' => ['href' => route('api.dashboard'), 'method' => 'GET'],
                'categories' => ['href' => route('api.categories.index'), 'method' => 'GET'],
                'items' => ['href' => route('api.items.index'), 'method' => 'GET'],
                'reviews' => ['href' => route('api.reviews.index'), 'method' => 'GET'],
            ],
        ]);
    }
}
