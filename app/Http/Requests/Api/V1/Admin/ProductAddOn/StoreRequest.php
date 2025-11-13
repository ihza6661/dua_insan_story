<?php

namespace App\Http\Requests\Api\V1\Admin\ProductAddOn;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'add_on_id' => [
                'required',
                'integer',
                'exists:add_ons,id',
                Rule::unique('product_add_ons')->where('product_id', $this->route('product')->id)
            ],
            'weight' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'add_on_id.required' => 'Item tambahan wajib dipilih.',
            'add_on_id.exists' => 'Item tambahan tidak valid.',
            'add_on_id.unique' => 'Item ini sudah ditambahkan ke produk.',
        ];
    }
}
