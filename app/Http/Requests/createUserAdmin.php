<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class createUserAdmin extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins',
            'role' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'The first name field is required.',
            'first_name.string'   => 'The first name must be a valid string.',
            'first_name.max'      => 'The first name may not be greater than 255 characters.',

            'email.required'      => 'The email address is required.',
            'email.string'        => 'The email address must be a valid string.',
            'email.email'         => 'Please enter a valid email address.',
            'email.max'           => 'The email may not be greater than 255 characters.',
            'email.unique'        => 'This email has already been taken.',

            'role.required'       => 'The role field is required.',
            'role.string'         => 'The role must be a valid string.',
        ];
    }

    protected function failedValidation(Validator|\Illuminate\Contracts\Validation\Validator $validator)
    {
        parent::failedValidation($validator);
    }
}
