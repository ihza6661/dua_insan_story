<?php

namespace App\Http\Requests\Api\V1\Customer\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'customer';
    }

    public function rules(): array
    {
        return [
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB max per image
        ];
    }

    public function messages(): array
    {
        return [
            'order_item_id.required' => 'Item pesanan wajib diisi.',
            'order_item_id.exists' => 'Item pesanan tidak valid.',
            'product_id.required' => 'Produk wajib diisi.',
            'product_id.exists' => 'Produk tidak valid.',
            'rating.required' => 'Rating wajib diisi.',
            'rating.integer' => 'Rating harus berupa angka.',
            'rating.min' => 'Rating minimal adalah 1 bintang.',
            'rating.max' => 'Rating maksimal adalah 5 bintang.',
            'comment.max' => 'Komentar tidak boleh lebih dari 1000 karakter.',
            'images.max' => 'Maksimal 5 gambar dapat diunggah.',
            'images.*.image' => 'File harus berupa gambar.',
            'images.*.mimes' => 'Gambar harus berformat: jpg, jpeg, png, atau webp.',
            'images.*.max' => 'Ukuran gambar tidak boleh lebih dari 5MB.',
        ];
    }
}
