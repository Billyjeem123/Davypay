<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateNairaCardRequest extends FormRequest
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
            'type'   => 'required|string|in:virtual,physical',
            'brand'  => 'required|string|in:Verve,AfriGo',
            'number' => 'nullable|string',

            'address.state'            => 'required_if:type,physical|string|max:100',
            'address.city'             => 'required_if:type,physical|string|max:100',
            'address.address'          => 'required_if:type,physical|string|max:255',
            'address.house_no'         => 'nullable|string|max:100',
            'address.nearest_bus_stop' => 'nullable|string|max:100',
            'address.postcode'         => 'nullable|string|max:100',
            'address.phone'            => 'required_if:type,physical|string|max:20',
        ];
    }

    /**
     * Custom messages (optional)
     */
    public function messages(): array
    {
        return [
            'address.state.required_if' => 'State is required for physical cards.',
            'address.city.required_if'  => 'City is required for physical cards.',
            'address.address.required_if' => 'Delivery address is required for physical cards.',
        ];
    }
}
