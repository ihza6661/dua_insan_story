<?php

namespace App\Http\Requests\Api\V1\Admin\ProductCategory;

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
        $categoryId = $this->route('product_category')->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('product_categories', 'name')->ignore($categoryId),
            ],
            'description' => 'nullable|string',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.string' => 'Nama kategori harus berupa teks.',
            'name.max' => 'Nama kategori tidak boleh lebih dari :max karakter.',
            'name.unique' => 'Nama kategori ini sudah terdaftar.',

            'description.string' => 'Deskripsi harus berupa teks.',

            'image.image' => 'File yang diunggah harus berupa gambar.',
            'image.mimes' => 'Gambar harus berupa file dengan tipe: :values.',
            'image.max' => 'Ukuran gambar tidak boleh lebih dari 5MB.',
        ];
    }
}
