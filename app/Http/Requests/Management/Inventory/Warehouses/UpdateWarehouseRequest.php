<?php

namespace App\Http\Requests\Management\Inventory\Warehouses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'branch_id' => ['sometimes', 'string', 'exists:branches,id'],
            'store_ids' => ['sometimes', 'array'],
            'store_ids.*' => ['required', 'string', 'exists:stores,id'],
        ];
    }
}
