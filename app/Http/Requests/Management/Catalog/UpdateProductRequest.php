<?php

namespace App\Http\Requests\Management\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'sku' => 'string|max:255',
            'barcode' => 'nullable|string|max:255',
            'type' => 'in:product,service,variant',
            'track_inventory' => 'boolean',
            'min_stock' => 'integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'cost' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ];
    }
}
