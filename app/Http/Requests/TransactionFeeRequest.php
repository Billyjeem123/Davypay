<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionFeeRequest extends FormRequest
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
            'transfer_min.*' => 'nullable|numeric|min:0',
            'transfer_max.*' => 'nullable|numeric|min:0',
            'transfer_percent.*' => 'nullable|numeric|min:0|max:100',
            "provider"   => 'nullable',
            'type' => 'nullable'
        ];
    }


    /**
     * Add custom validation logic after the default rules run.
     * This ensures that for each transfer/deposit range:
     * the 'max' value is not less than the corresponding 'min' value.
     */


}
