<?php

namespace App\Http\Requests\Api\V1\Admin\Review;

use Illuminate\Foundation\Http\FormRequest;

class AdminResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'admin_response' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_response.required' => 'Respon admin wajib diisi.',
            'admin_response.min' => 'Respon admin minimal harus 10 karakter.',
            'admin_response.max' => 'Respon admin tidak boleh lebih dari 500 karakter.',
        ];
    }
}
