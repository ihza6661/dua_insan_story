<?php

namespace App\Http\Requests\Api\V1\Admin\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
            'status' => ['required', 'string', Rule::in(Order::getValidStatuses())],
            'tracking_number' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'order_ids.required' => 'Pilih minimal satu pesanan.',
            'order_ids.array' => 'Format order_ids tidak valid.',
            'order_ids.min' => 'Pilih minimal satu pesanan.',
            'order_ids.*.exists' => 'Salah satu pesanan tidak ditemukan.',
            'status.required' => 'Status pesanan wajib diisi.',
            'status.in' => 'Status pesanan tidak valid.',
        ];
    }
}
