<?php

namespace App\Http\Requests\Management\Organization\Branches;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
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
            'store_ids' => ['sometimes', 'array', 'min:1'],
            'store_ids.*' => ['required', 'string', 'exists:stores,id'],
            'is_active' => ['sometimes', 'boolean'],
            'allow_sales' => ['sometimes', 'boolean'],
            'allow_inventory' => ['sometimes', 'boolean'],
        ];
    }
}
