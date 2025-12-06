<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'phone_number' => ['nullable', 'string', 'regex:/^(\+62|62|0)[0-9]{8,13}$/', 'max:25'],
            'address' => ['nullable', 'required_with:province_name,city_name,postal_code', 'string', 'max:255'],
            'province_name' => ['nullable', 'required_with:address,city_name,postal_code', 'string', 'max:255'],
            'city_name' => ['nullable', 'required_with:address,province_name,postal_code', 'string', 'max:255'],
            'postal_code' => ['nullable', 'required_with:address,province_name,city_name', 'string', 'regex:/^[0-9]{5}$/', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Format nomor telepon tidak valid. Gunakan format Indonesia (contoh: 0812XXXXXXXX, +62812XXXXXXXX).',
            'postal_code.regex' => 'Kode pos harus berupa 5 digit angka.',
        ];
    }
}
