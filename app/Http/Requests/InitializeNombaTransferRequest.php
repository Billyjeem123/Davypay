<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class InitializeNombaTransferRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
            ],
            'user_id' => [
                'required',
            ],
            "image" => "nullable",

            'pin' => [
                'required',
            ],
            'narration' => [
                'sometimes',
                'string',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Transfer amount is required',
            'amount.numeric' => 'Transfer amount must be a valid number',
            'amount.min' => 'Minimum transfer amount is ₦10',
            'narration.string' => 'Narration must be a valid string',
        ];
    }



    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Custom validation: Check if amount is in valid format (max 2 decimal places)
            if ($this->has('amount')) {
                $amount = $this->input('amount');
                if (is_numeric($amount) && floor($amount * 100) != $amount * 100) {
                    $validator->errors()->add('amount', 'Amount can have maximum 2 decimal places');
                }
            }

            // Custom validation: Sanitize narration
            if ($this->has('narration')) {
                $narration = $this->input('narration');
                if ($narration && preg_match('/[<>"\']/', $narration)) {
                    $validator->errors()->add('narration', 'Narration contains invalid characters');
                }
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
            'error' => $validator->errors()->first(), // First error for simple error display
        ], 422));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean and format the amount
        if ($this->has('amount')) {
            $amount = $this->input('amount');
            // Remove any commas or spaces
            $cleanAmount = preg_replace('/[^\d.]/', '', $amount);
            $this->merge(['amount' => $cleanAmount]);
        }

        if ($this->has('narration')) {
            $this->merge(['narration' => trim($this->input('narration'))]);
        }
    }
}
