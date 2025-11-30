<?php

namespace App\Http\Requests\Api\V1\Admin\GalleryItem;

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
            'product_id' => ['sometimes', 'nullable', 'integer', 'exists:products,id'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'file' => [
                'sometimes',
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,mp4,mov,avi',
                'max:20480', // 20MB Max
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File gambar atau video wajib diunggah.',
            'file.mimes' => 'Format file harus berupa gambar (jpg, png, webp) atau video (mp4, mov, avi).',
            'file.max' => 'Ukuran file tidak boleh lebih dari 20MB.',
            'product_id.exists' => 'Produk yang dipilih tidak valid.',
        ];
    }
}
