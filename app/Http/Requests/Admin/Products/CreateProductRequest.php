<?php

namespace App\Http\Requests\Admin\Products;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sku' => ['nullable', 'string', 'max:100'],
            'label' => ['nullable', 'string', 'max:255'],
            'batch_type' => ['nullable', 'in:bag,box,stand,in_sale,other'],
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0', 'required_without:store_stocks'],
            'store_stocks' => ['nullable', 'array'],
            'store_stocks.*' => ['integer', 'min:0'],
            'stores_scope' => ['required', 'in:single,multiple,all'],
            'store_ids' => ['nullable', 'array', 'required_if:stores_scope,single,multiple'],
            'store_ids.*' => ['uuid', 'exists:stores,id'],
        ];
    }
}
