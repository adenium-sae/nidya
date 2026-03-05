<?php

namespace App\Http\Requests\Management\Inventory\Stock;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'storage_location_id' => 'nullable|exists:storage_locations,id',
            'type' => 'required|in:increase,decrease,recount',
            'reason' => 'nullable|in:damaged,lost,found,expired,recount,other',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.reason' => 'required|in:damaged,lost,found,expired,recount,other',
            'items.*.quantity_after' => 'required|integer|min:0',
        ];
    }
}
