<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Tampilkan data profil pengguna yang sedang login.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('address');

        return response()->json([
            'message' => 'User data retrieved successfully.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Perbarui profil pengguna yang sedang login.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update($request->only(['full_name', 'email', 'phone_number']));

        $requestAddressData = $request->only(['address', 'province_name', 'city_name', 'postal_code']);

        if (! empty(array_filter($requestAddressData))) {
            Log::info('Updating address with data:', $requestAddressData);
            $addressData = [
                'street' => $request->address,
                'city' => $request->city_name,
                'state' => $request->province_name,
                'postal_code' => $request->postal_code,
                'country' => 'Indonesia',
            ];
            Log::info('Prepared address data:', $addressData);
            DB::enableQueryLog();
            if ($user->address) {
                $user->address->update($addressData);
            } else {
                $user->address()->create($addressData);
            }
            Log::info('DB Queries:', DB::getQueryLog());
        } else {
            if ($user->address) {
                $user->address()->delete();
            }
        }

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => new UserResource($user->load('address')),
        ]);
    }

    /**
     * Ubah password pengguna yang sedang login.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak cocok.'],
            ]);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }
}
