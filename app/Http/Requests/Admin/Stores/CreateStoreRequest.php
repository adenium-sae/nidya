<?php

namespace App\Http\Requests\Admin\Stores;

use Illuminate\Foundation\Http\FormRequest;

class CreateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:stores,slug',
            'description' => 'nullable|string',
            'logo_url' => 'nullable|url',
            'primary_color' => 'nullable|string|max:7',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
