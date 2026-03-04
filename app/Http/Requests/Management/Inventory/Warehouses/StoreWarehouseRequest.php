<?php

namespace App\Http\Requests\Management\Inventory\Warehouses;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'max:255'],
            'store_ids' => ['required', 'array'],
            'store_ids.*' => ['required', 'string', 'exists:stores,id'],
            'branch_id' => ['nullable', 'string', 'exists:branches,id'],
            'address_id' => ['nullable', 'string', 'exists:addresses,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
