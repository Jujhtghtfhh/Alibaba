<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    /**
     * LIST: GET /api/reviews?item_id=&min_rating=&per_page=&page=
     *
     * Newest reviews come first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'item_id' => ['nullable', 'integer'],
            'min_rating' => ['nullable', 'integer', 'between:1,5'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $reviews = Review::query()
            ->with('item')
            ->when(isset($filters['item_id']), fn ($query) => $query->where('item_id', $filters['item_id']))
            ->when(isset($filters['min_rating']), fn ($query) => $query->where('rating', '>=', $filters['min_rating']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return ReviewResource::collection($reviews);
    }

    /**
     * CREATE: POST /api/reviews
     */
    public function store(ReviewRequest $request): JsonResponse
    {
        // The logged-in user (from the token) becomes the review's author.
        $review = $request->user()->reviews()->create($request->validated());

        return ReviewResource::make($review->load('item'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED)
            ->header('Location', route('api.reviews.show', $review));
    }

    /**
     * READ: GET /api/reviews/{review}
     */
    public function show(Review $review): ReviewResource
    {
        return ReviewResource::make($review->load('item'));
    }

    /**
     * UPDATE: PUT or PATCH /api/reviews/{review}
     *
     * Only the author or an admin (see ReviewPolicy).
     */
    public function update(ReviewRequest $request, Review $review): ReviewResource
    {
        Gate::authorize('update', $review);

        $review->update($request->validated());

        return ReviewResource::make($review->load('item'));
    }

    /**
     * DELETE: DELETE /api/reviews/{review}
     *
     * Only the author or an admin (see ReviewPolicy).
     */
    public function destroy(Review $review): Response
    {
        Gate::authorize('delete', $review);

        $review->delete();

        return response()->noContent();
    }
}
