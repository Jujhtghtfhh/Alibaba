<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ItemController extends Controller
{
    /**
     * LIST: GET /api/items?category_id=&search=&min_price=&max_price=&per_page=&page=
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $items = Item::query()
            ->with('category')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->when(isset($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(isset($filters['search']), fn ($query) => $query->where('name', 'like', "%{$filters['search']}%"))
            ->when(isset($filters['min_price']), fn ($query) => $query->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn ($query) => $query->where('price', '<=', $filters['max_price']))
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return ItemResource::collection($items);
    }

    /**
     * CREATE: POST /api/items
     */
    public function store(ItemRequest $request): JsonResponse
    {
        // The logged-in user (from the token) becomes the item's author.
        $item = $request->user()->items()->create($request->validated());

        return ItemResource::make($item->load('category')->loadCount('reviews')->loadAvg('reviews', 'rating'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED)
            ->header('Location', route('api.items.show', $item));
    }

    /**
     * READ: GET /api/items/{item}
     */
    public function show(Item $item): ItemResource
    {
        return ItemResource::make($item->load('category')->loadCount('reviews')->loadAvg('reviews', 'rating'));
    }

    /**
     * UPDATE: PUT or PATCH /api/items/{item}
     *
     * Only the author or an admin (see ItemPolicy).
     */
    public function update(ItemRequest $request, Item $item): ItemResource
    {
        Gate::authorize('update', $item);

        $item->update($request->validated());

        return ItemResource::make($item->load('category')->loadCount('reviews')->loadAvg('reviews', 'rating'));
    }

    /**
     * DELETE: DELETE /api/items/{item}
     *
     * Only the author or an admin (see ItemPolicy).
     *
     * The item's reviews are deleted too (ON DELETE CASCADE).
     */
    public function destroy(Item $item): Response
    {
        Gate::authorize('delete', $item);

        $item->delete();

        return response()->noContent();
    }
}
