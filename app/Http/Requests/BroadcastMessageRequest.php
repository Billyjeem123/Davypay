<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class BroadcastMessageRequest extends FormRequest
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
            'announcement_title' => 'required|string|max:255',
            'announcement_message' => 'required|string|max:500',
            'recipient_type' => 'required|in:all,specific',
            'specific_users' => 'required_if:recipient_type,specific|array|min:1',
            'specific_users.*' => 'exists:users,id',
            'priority' => 'required|in:normal,high,urgent',
            'push_notification' => 'required|boolean',
        ];
    }


    public function messages(): array
    {
        return [
            'announcement_title.required' => 'Announcement title is required.',
            'announcement_title.max' => 'Announcement title cannot exceed 255 characters.',
            'announcement_message.required' => 'Announcement message is required.',
            'announcement_message.max' => 'Announcement message cannot exceed 500 characters.',
            'recipient_type.required' => 'Please select recipient type.',
            'recipient_type.in' => 'Invalid recipient type selected.',
            'specific_users.required_if' => 'Please select at least one user when sending to specific users.',
            'specific_users.array' => 'Invalid user selection format.',
            'specific_users.min' => 'Please select at least one user.',
            'specific_users.*.exists' => 'One or more selected users do not exist.',
            'priority.required' => 'Please select priority level.',
            'priority.in' => 'Invalid priority level selected.',
            'push_notification.required' => 'Push notification option is required.',
        ];
    }

//    protected function failedValidation(Validator|\Illuminate\Contracts\Validation\Validator $validator)
//    {
//        parent::failedValidation($validator);
//    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'push_notification' => $this->has('push_notification') ? true : false,
        ]);
    }
}
