<?php

namespace App\Http\Requests\Api\V1\Admin\AddOn;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $addOnId = $this->route('add_on')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('add_ons')->ignore($addOnId)],
            'price' => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama item tambahan wajib diisi.',
            'name.unique' => 'Nama item ini sudah ada.',
            'price.required' => 'Harga wajib diisi.',
            'price.integer' => 'Harga harus berupa angka.',
        ];
    }
}
