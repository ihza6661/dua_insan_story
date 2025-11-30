<?php

namespace App\Http\Requests\Api\V1\Admin\Order;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateStatusRequest
 *
 * Form request for updating order status.
 */
class UpdateStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                'in:pending,paid,processing,packing,shipped,completed,cancelled',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status pesanan harus diisi.',
            'status.in' => 'Status pesanan tidak valid.',
        ];
    }
}
