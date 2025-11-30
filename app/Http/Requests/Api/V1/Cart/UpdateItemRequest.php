<?php

namespace App\Http\Requests\Api\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cartItem = $this->route('cartItem');
        $user = $this->user();

        if ($user) {
            return $cartItem->cart->user_id === $user->id;
        }

        $sessionId = $this->header('X-Session-ID');

        return $cartItem->cart->session_id === $sessionId;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Jumlah pesanan wajib diisi.',
            'quantity.integer' => 'Jumlah pesanan harus berupa angka.',
            'quantity.min' => 'Jumlah pesanan minimal 1 buah.',
        ];
    }
}
