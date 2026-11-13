<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * Transform the category into its JSON representation, including hypermedia links.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'items_count' => $this->whenCounted('items'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            '_links' => [
                'self' => ['href' => route('api.categories.show', $this->id), 'method' => 'GET'],
                'update' => ['href' => route('api.categories.update', $this->id), 'method' => 'PUT'],
                'delete' => ['href' => route('api.categories.destroy', $this->id), 'method' => 'DELETE'],
                'items' => ['href' => route('api.items.index', ['category_id' => $this->id]), 'method' => 'GET'],
            ],
        ];
    }
}
