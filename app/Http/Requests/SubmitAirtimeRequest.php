<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAirtimeRequest extends FormRequest
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
            'message' => 'nullable|string|max:500',
            'file' => 'nullable',
            'network_provider_id' => 'required',
            'amount' => 'required|numeric|min:100',
            'phone_number' => 'required|string',
            "transaction_pin" => "required"
        ];
    }
    public function messages(): array
    {
        return [
            'network_provider.in' => 'The selected network provider is invalid.',
            'amount.min' => 'Minimum amount is ₦100.',
            'phone_number.regex' => 'Please enter a valid number.'
        ];
    }
}
