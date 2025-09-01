<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Get the tier ID from the route parameter
        $tierId = $this->route('tier')->id;

        return [
            'name' => [
                'required',
                'string',
            ],
            'daily_limit' => [
                'required',
            ],
            'wallet_balance' => [
                'required',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['active', 'inactive'])
            ]
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */


    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tier name',
            'daily_limit' => 'daily limit',
            'wallet_balance' => 'wallet balance limit',
            'status' => 'status',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => strtolower($this->name),
            'daily_limit' => (float) $this->daily_limit,
            'wallet_balance' => (float) $this->wallet_balance,
        ]);
    }
}
