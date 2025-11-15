<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
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
           'name' => 'required|string|max:255',
           'lastname' => 'required|string|max:255',
           'email' => 'required|email|unique:users',
           'phone' => 'required|string|max:50',
           'password' => 'required|string|max:255|min:6',
        ];
    }
}
