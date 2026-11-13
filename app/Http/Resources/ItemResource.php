<?php

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Item
 */
class ItemResource extends JsonResource
{
    /**
     * Transform the item into its JSON representation, including hypermedia links.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'user_id' => $this->user_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category->name),
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'stock' => $this->stock,
            'reviews_count' => $this->whenCounted('reviews'),
            'average_rating' => $this->whenAggregated('reviews', 'rating', 'avg', fn ($average) => round((float) $average, 1)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            '_links' => [
                'self' => ['href' => route('api.items.show', $this->id), 'method' => 'GET'],
                'update' => ['href' => route('api.items.update', $this->id), 'method' => 'PUT'],
                'delete' => ['href' => route('api.items.destroy', $this->id), 'method' => 'DELETE'],
                'category' => ['href' => route('api.categories.show', $this->category_id), 'method' => 'GET'],
                'reviews' => ['href' => route('api.reviews.index', ['item_id' => $this->id]), 'method' => 'GET'],
            ],
        ];
    }
}
