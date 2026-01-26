<?php

namespace App\Http\Requests\Admin\Products;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'type' => 'required|in:product,service,variant',
            'parent_id' => 'nullable|exists:products,id',
            'track_inventory' => 'boolean',
            'min_stock' => 'integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'cost' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|url',
        ];
    }
}
