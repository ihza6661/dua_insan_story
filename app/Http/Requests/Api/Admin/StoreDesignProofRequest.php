<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDesignProofRequest extends FormRequest
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
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'], // Max 10MB
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'order_item_id.required' => 'Order item ID is required',
            'order_item_id.exists' => 'The specified order item does not exist',
            'file.required' => 'Design proof file is required',
            'file.mimes' => 'File must be a JPG, JPEG, PNG, or PDF',
            'file.max' => 'File size must not exceed 10MB',
            'admin_notes.max' => 'Admin notes must not exceed 1000 characters',
        ];
    }
}
