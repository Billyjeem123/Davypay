<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookFlightRequest extends FormRequest
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
            'PassengerDetails' => ['required', 'array', 'min:1'],
            'PassengerDetails.*.PassengerType' => ['required', 'string'],
            'PassengerDetails.*.FirstName' => ['required', 'string'],
            'PassengerDetails.*.LastName' => ['required', 'string'],
            'PassengerDetails.*.DateOfBirth' => ['required', 'date'],
            'PassengerDetails.*.PhoneNumber' => ['required', 'string'],
            'PassengerDetails.*.PassportNumber' => ['nullable', 'string'],
            'PassengerDetails.*.ExpiryDate' => ['nullable', 'date'],
            'PassengerDetails.*.PassportIssuingAuthority' => ['nullable', 'string'],
            'PassengerDetails.*.Email' => ['required', 'email'],
            'PassengerDetails.*.Gender' => ['required', 'string'],
            'PassengerDetails.*.Title' => ['required', 'string'],
            'PassengerDetails.*.City' => ['nullable', 'string'],
            'PassengerDetails.*.Country' => ['nullable', 'string'],
            'PassengerDetails.*.CountryCode' => ['nullable', 'string'],
            'PassengerDetails.*.PostalCode' => ['nullable', 'string'],

            'BookingItemModels' => ['required', 'array', 'min:1'],
            'BookingItemModels.*.ProductType' => ['required', 'string'],
            'BookingItemModels.*.BookingData' => ['required', 'string'],
            'BookingItemModels.*.BookingId' => ['required', 'string'],
            'BookingItemModels.*.TargetCurrency' => ['required', 'string'],

            'BookingId' => ['required', 'string'],
        ];
    }

}
