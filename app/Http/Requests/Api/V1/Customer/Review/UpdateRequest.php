<?php

namespace App\Http\Requests\Api\V1\Customer\Review;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'customer';
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'min:10', 'max:1000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB max per image
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Rating wajib diisi.',
            'rating.integer' => 'Rating harus berupa angka.',
            'rating.min' => 'Rating minimal adalah 1 bintang.',
            'rating.max' => 'Rating maksimal adalah 5 bintang.',
            'comment.min' => 'Komentar minimal harus 10 karakter agar lebih bermakna.',
            'comment.max' => 'Komentar tidak boleh lebih dari 1000 karakter.',
            'images.max' => 'Maksimal 5 gambar dapat diunggah.',
            'images.*.image' => 'File harus berupa gambar.',
            'images.*.mimes' => 'Gambar harus berformat: jpg, jpeg, png, atau webp.',
            'images.*.max' => 'Ukuran gambar tidak boleh lebih dari 5MB.',
        ];
    }
}
