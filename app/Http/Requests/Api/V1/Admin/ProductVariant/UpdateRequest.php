<?php

namespace App\Http\Requests\Api\V1\Admin\ProductVariant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'price' => ['sometimes', 'required', 'integer', 'min:0'],
            'stock' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'weight' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => 'Harga varian wajib diisi.',
            'price.integer' => 'Harga varian harus berupa angka.',
            'stock.integer' => 'Stok harus berupa angka.',
        ];
    }
}
