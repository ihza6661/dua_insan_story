<?php

namespace App\Http\Requests\Api\V1\Admin\ProductVariant;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'integer', 'min:0'],
            'options' => ['required', 'array', 'min:1'],
            'options.*' => ['required', 'integer', 'exists:attribute_values,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => 'Harga varian wajib diisi.',
            'options.required' => 'Minimal satu opsi (nilai atribut) wajib dipilih.',
            'options.*.exists' => 'Salah satu opsi yang dipilih tidak valid.',
        ];
    }
}
