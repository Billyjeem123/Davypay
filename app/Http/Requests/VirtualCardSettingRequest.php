<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VirtualCardSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow admin users
        return true;
    }

    /**
     * Get the validatiIon rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'setting_type' => [
                'required',
                'string',
                'in:virtual_card_topup_fee,virtual_card_creation_fee,virtual_card_account_fee,dollar_conversion_rate'
            ],
            'setting_value' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99' // Adjust based on your needs
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'setting_type.required' => 'Please select a setting type.',
            'setting_type.in' => 'Invalid setting type selected.',
            'setting_value.required' => 'Please enter a value.',
            'setting_value.numeric' => 'The value must be a valid number.',
            'setting_value.min' => 'The value cannot be negative.',
            'setting_value.max' => 'The value is too large.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'setting_type' => 'setting type',
            'setting_value' => 'value',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException(
            $validator,
            redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please correct the errors in the form.')
        );
    }
}
