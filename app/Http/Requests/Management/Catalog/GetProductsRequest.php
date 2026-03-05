<?php

namespace App\Http\Requests\Management\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class GetProductsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:product,service'],
            'store_id' => ['nullable', 'string', 'exists:stores,id'],
            'warehouse_id' => ['nullable', 'string', 'exists:warehouses,id'],
            'is_active' => ['nullable', 'boolean'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'sort_by' => ['nullable', 'in:name,sku,created_at,updated_at'],
            'sort_order' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'columns' => ['nullable', 'string'],
        ];
    }
}
