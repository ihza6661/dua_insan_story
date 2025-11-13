<?php

namespace App\Http\Requests\Api\V1\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_address' => ['required', 'string', 'max:1000'],

            'bride_full_name' => ['required', 'string', 'max:255'],
            'groom_full_name' => ['required', 'string', 'max:255'],
            'bride_nickname' => ['required', 'string', 'max:100'],
            'groom_nickname' => ['required', 'string', 'max:100'],
            'bride_parents' => ['required', 'string', 'max:255'],
            'groom_parents' => ['required', 'string', 'max:255'],

            'akad_date' => ['required', 'date'],
            'akad_time' => ['required', 'string', 'max:100'],
            'akad_location' => ['required', 'string', 'max:255'],
            'reception_date' => ['required', 'date'],
            'reception_time' => ['required', 'string', 'max:100'],
            'reception_location' => ['required', 'string', 'max:255'],

            'gmaps_link' => ['nullable', 'url', 'max:1000'],
            'prewedding_photo' => ['nullable', 'mimes:jpeg,png,bmp,gif,svg,webp', 'max:5120'],

            'postal_code' => ['required', 'string', 'digits:5'],
            'shipping_cost' => ['required', 'numeric'],
            'shipping_service' => ['required', 'string', 'max:255'],
            'courier' => ['required', 'string', 'max:255'],
            'payment_option' => ['required', 'string', 'in:full,dp'],
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_address.required' => 'Alamat pengiriman wajib diisi.',
            'shipping_address.string' => 'Alamat pengiriman harus berupa teks.',
            'shipping_address.max' => 'Alamat pengiriman tidak boleh lebih dari 1000 karakter.',

            'bride_full_name.required' => 'Nama lengkap mempelai wanita wajib diisi.',
            'bride_full_name.string' => 'Nama lengkap mempelai wanita harus berupa teks.',
            'bride_full_name.max' => 'Nama lengkap mempelai wanita tidak boleh lebih dari 255 karakter.',

            'groom_full_name.required' => 'Nama lengkap mempelai pria wajib diisi.',
            'groom_full_name.string' => 'Nama lengkap mempelai pria harus berupa teks.',
            'groom_full_name.max' => 'Nama lengkap mempelai pria tidak boleh lebih dari 255 karakter.',

            'bride_nickname.required' => 'Nama panggilan mempelai wanita wajib diisi.',
            'bride_nickname.string' => 'Nama panggilan mempelai wanita harus berupa teks.',
            'bride_nickname.max' => 'Nama panggilan mempelai wanita tidak boleh lebih dari 100 karakter.',

            'groom_nickname.required' => 'Nama panggilan mempelai pria wajib diisi.',
            'groom_nickname.string' => 'Nama panggilan mempelai pria harus berupa teks.',
            'groom_nickname.max' => 'Nama panggilan mempelai pria tidak boleh lebih dari 100 karakter.',

            'bride_parents.required' => 'Nama orang tua mempelai wanita wajib diisi.',
            'bride_parents.string' => 'Nama orang tua mempelai wanita harus berupa teks.',
            'bride_parents.max' => 'Nama orang tua mempelai wanita tidak boleh lebih dari 255 karakter.',

            'groom_parents.required' => 'Nama orang tua mempelai pria wajib diisi.',
            'groom_parents.string' => 'Nama orang tua mempelai pria harus berupa teks.',
            'groom_parents.max' => 'Nama orang tua mempelai pria tidak boleh lebih dari 255 karakter.',

            'akad_date.required' => 'Tanggal akad wajib diisi.',
            'akad_date.date' => 'Format tanggal akad tidak valid.',
            'akad_time.required' => 'Waktu akad wajib diisi.',
            'akad_location.required' => 'Lokasi akad wajib diisi.',

            'reception_date.required' => 'Tanggal resepsi wajib diisi.',
            'reception_date.date' => 'Format tanggal resepsi tidak valid.',
            'reception_time.required' => 'Waktu resepsi wajib diisi.',
            'reception_location.required' => 'Lokasi resepsi wajib diisi.',

            'gmaps_link.url' => 'Link Google Maps harus berupa URL yang valid.',
            'prewedding_photo.mimes' => 'File foto pre-wedding harus berupa gambar (jpg, jpeg, png, bmp, gif, svg, webp).',
            'prewedding_photo.max' => 'Ukuran foto pre-wedding tidak boleh lebih dari 5MB.',
        ];
    }
}
