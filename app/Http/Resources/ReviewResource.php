<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    /**
     * Transform the review into its JSON representation, including hypermedia links.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'user_id' => $this->user_id,
            'item_name' => $this->whenLoaded('item', fn () => $this->item->name),
            'author_name' => $this->author_name,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            '_links' => [
                'self' => ['href' => route('api.reviews.show', $this->id), 'method' => 'GET'],
                'update' => ['href' => route('api.reviews.update', $this->id), 'method' => 'PUT'],
                'delete' => ['href' => route('api.reviews.destroy', $this->id), 'method' => 'DELETE'],
                'item' => ['href' => route('api.items.show', $this->item_id), 'method' => 'GET'],
            ],
        ];
    }
}
