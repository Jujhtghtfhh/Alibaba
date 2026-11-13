<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    /**
     * LIST: GET /api/categories?search=&per_page=&page=
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $categories = Category::query()
            ->withCount('items')
            ->when(isset($filters['search']), fn ($query) => $query->where('name', 'like', "%{$filters['search']}%"))
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return CategoryResource::collection($categories);
    }

    /**
     * CREATE: POST /api/categories
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return CategoryResource::make($category->loadCount('items'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED)
            ->header('Location', route('api.categories.show', $category));
    }

    /**
     * READ: GET /api/categories/{category}
     */
    public function show(Category $category): CategoryResource
    {
        return CategoryResource::make($category->loadCount('items'));
    }

    /**
     * UPDATE: PUT or PATCH /api/categories/{category}
     */
    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return CategoryResource::make($category->loadCount('items'));
    }

    /**
     * DELETE: DELETE /api/categories/{category}
     *
     * The category's items and their reviews are deleted too (ON DELETE CASCADE).
     */
    public function destroy(Category $category): Response
    {
        $category->delete();

        return response()->noContent();
    }
}
