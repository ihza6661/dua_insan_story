<?php

namespace App\Http\Requests\Api\Customer;

use Illuminate\Foundation\Http\FormRequest;

class RequestRevisionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled in the controller to check order ownership
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'feedback' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'feedback.required' => 'Feedback is required when requesting a revision',
            'feedback.max' => 'Feedback must not exceed 1000 characters',
        ];
    }
}
