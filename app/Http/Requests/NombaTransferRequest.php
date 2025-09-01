<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NombaTransferRequest extends FormRequest
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
            'amount' => 'required|numeric|min:1',
            'account_number' => 'required|string',
            "account_name" => "required",
            'bank_code' => 'required|string',
            'narration' => 'nullable|string',
            'transaction_pin' => 'required',
            "image" => "nullable",
        ];
    }
}
