<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNetworkProviderRequest extends FormRequest
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
        $providerId = $this->route('id'); // get the ID from the route

        return [
            'network_name' => [
                'required',
                'string',
                'in:MTN,GLO,AIRTEL,9MOBILE',
                Rule::unique('network_providers', 'network_name')->ignore($providerId)
            ],
            'admin_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100'
            ],
            'transfer_number' => [
                'required',
                'string',
                'regex:/^(\+?234|0)?[789][01]\d{8}$/'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'network_name.unique' => 'This network provider already exists.',
            'admin_rate.min' => 'Admin rate cannot be negative.',
            'admin_rate.max' => 'Admin rate cannot exceed 100%.',
            'transfer_number.regex' => 'Please enter a valid Nigerian phone number.',
        ];
    }
}
