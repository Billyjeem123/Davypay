<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionDepositFee extends FormRequest
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
            'deposit_min.*' => 'nullable|numeric|min:0',
            'deposit_max.*' => 'nullable|numeric|min:0',
            'deposit_platform_fee.*' => 'nullable|numeric|min:0|max:100',
            "provider"   => 'nullable',
            'type' => 'nullable'
        ];
    }
}
