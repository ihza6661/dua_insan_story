<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * The user must be the owner of the order.
     */
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $this->user() && $order && $this->user()->id === $order->customer_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan pembatalan harus diisi.',
            'reason.min' => 'Alasan pembatalan minimal 10 karakter.',
            'reason.max' => 'Alasan pembatalan maksimal 500 karakter.',
        ];
    }
}
