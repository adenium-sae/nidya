<?php

namespace App\Http\Requests\Management\Organization\Branches;

use Illuminate\Foundation\Http\FormRequest;

class CreateBranchRequest extends FormRequest
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
            'store_ids' => ['required', 'array', 'min:1'],
            'store_ids.*' => ['required', 'string', 'exists:stores,id'],
            'address_id' => ['nullable', 'string', 'exists:addresses,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'allow_sales' => ['sometimes', 'boolean'],
            'allow_inventory' => ['sometimes', 'boolean'],
        ];
    }
}
