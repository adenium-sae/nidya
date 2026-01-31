<?php

namespace App\Http\Requests\Management\Access\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SignUpRequest extends FormRequest
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
            "email" => ["required", "email"],
            "password" => ["required", "string", "min:8", "confirmed"],
            "first_name" => ["required", "string", "max:255"],
            "middle_name" => ["nullable", "string", "max:255"],
            "last_name" => ["nullable", "string", "max:255"],
            "second_last_name" => ["nullable", "string", "max:255"],
            "birth_date" => ["nullable", "date"]
        ];
    }
}
