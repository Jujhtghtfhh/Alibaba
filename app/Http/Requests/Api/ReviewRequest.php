<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    /**
     * Rules for creating (POST) and updating (PUT / PATCH) a review.
     *
     * PUT replaces the whole review, so required fields must be sent.
     * PATCH is a partial update, so a field is only validated when it is sent.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $presence = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'item_id' => [$presence, 'integer', 'exists:items,id'],
            'author_name' => [$presence, 'string', 'max:100'],
            'rating' => [$presence, 'integer', 'between:1,5'],
            'comment' => [$presence, 'string', 'max:2000'],
        ];
    }
}
