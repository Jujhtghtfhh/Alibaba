<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * Rules for creating (POST) and updating (PUT / PATCH) a category.
     *
     * PUT replaces the whole category, so required fields must be sent.
     * PATCH is a partial update, so a field is only validated when it is sent.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $presence = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'name' => [$presence, 'string', 'max:100', Rule::unique('categories')->ignore($this->route('category'))],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
