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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sku' => ['required', 'string', 'unique:products,sku', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'in:product,service'],
            'cost' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'], // Precio de venta base para las tiendas
            'image_url' => ['nullable', 'url'],
            'category_id' => ['required', 'exists:categories,id'],
            
            // Asignación de Tiendas
            'target_stores' => ['required', 'in:single,multiple,all'],
            'store_id' => ['required_if:target_stores,single', 'nullable', 'exists:stores,id'],
            'store_ids' => ['required_if:target_stores,multiple', 'nullable', 'array'],
            'store_ids.*' => ['exists:stores,id'],

            // Inventario Inicial (Solo para tipo producto y una sola tienda)
            'initial_stock' => ['nullable', 'integer', 'min:0'],
            'warehouse_id' => ['required_with:initial_stock', 'nullable', 'exists:warehouses,id'],
            
            // Atributos adicionales
            'track_inventory' => ['boolean'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
        ];
    }
    
    public function after(): array
    {
        return [
            function ($validator) {
                if ($this->input('type') === 'service' && $this->input('initial_stock')) {
                    $validator->errors()->add('initial_stock', 'Services cannot have initial stock.');
                }
                if ($this->input('initial_stock') > 0 && $this->input('target_stores') !== 'single') {
                    $validator->errors()->add('initial_stock', 'Initial stock can only be set when creating product for a single store. For multiple stores, please adjust stock after creation.');
                }
            }
        ];
    }

    public function attributes(): array
    {
        return [
            'store_id' => 'store',
            'warehouse_id' => 'warehouse',
            'category_id' => 'category',
        ];
    }
}
