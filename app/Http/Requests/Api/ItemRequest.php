<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ItemRequest extends FormRequest
{
    /**
     * Rules for creating (POST) and updating (PUT / PATCH) an item.
     *
     * PUT replaces the whole item, so required fields must be sent.
     * PATCH is a partial update, so a field is only validated when it is sent.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $presence = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'category_id' => [$presence, 'integer', 'exists:categories,id'],
            'name' => [$presence, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => [$presence, 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999.99'],
            'stock' => [$presence, 'integer', 'min:0', 'max:1000000'],
        ];
    }
}
