<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * Get all addresses for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->latest('is_default')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Daftar alamat berhasil diambil.',
            'data' => $addresses,
        ]);
    }

    /**
     * Store a new address
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['nullable', 'string', 'max:100'],
            'recipient_phone' => ['nullable', 'string', 'max:20'],
            'street' => ['required', 'string', 'max:255'],
            'subdistrict' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['country'] = $validated['country'] ?? 'Indonesia';

        // Check if this is the first address for the user
        $hasNoAddresses = $request->user()->addresses()->count() === 0;
        if ($hasNoAddresses) {
            $validated['is_default'] = true;
        }

        $address = Address::create($validated);

        // If marked as default, unset other defaults
        if ($address->is_default) {
            $address->setAsDefault();
        }

        return response()->json([
            'message' => 'Alamat berhasil ditambahkan.',
            'data' => $address->fresh(),
        ], 201);
    }

    /**
     * Display the specified address
     */
    public function show(Request $request, Address $address): JsonResponse
    {
        // Ensure address belongs to authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        return response()->json([
            'message' => 'Detail alamat berhasil diambil.',
            'data' => $address,
        ]);
    }

    /**
     * Update the specified address
     */
    public function update(Request $request, Address $address): JsonResponse
    {
        // Ensure address belongs to authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:50'],
            'recipient_name' => ['nullable', 'string', 'max:100'],
            'recipient_phone' => ['nullable', 'string', 'max:20'],
            'street' => ['sometimes', 'string', 'max:255'],
            'subdistrict' => ['nullable', 'string', 'max:100'],
            'city' => ['sometimes', 'string', 'max:100'],
            'state' => ['sometimes', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'string', 'max:10'],
            'country' => ['sometimes', 'string', 'max:100'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $address->update($validated);

        // If marked as default, unset other defaults
        if (isset($validated['is_default']) && $validated['is_default']) {
            $address->setAsDefault();
        }

        return response()->json([
            'message' => 'Alamat berhasil diperbarui.',
            'data' => $address->fresh(),
        ]);
    }

    /**
     * Set address as default
     */
    public function setDefault(Request $request, Address $address): JsonResponse
    {
        // Ensure address belongs to authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $address->setAsDefault();

        return response()->json([
            'message' => 'Alamat berhasil diatur sebagai alamat utama.',
            'data' => $address->fresh(),
        ]);
    }

    /**
     * Remove the specified address
     */
    public function destroy(Request $request, Address $address): JsonResponse
    {
        // Ensure address belongs to authenticated user
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $wasDefault = $address->is_default;
        $userId = $address->user_id;

        $address->delete();

        // If we deleted the default address, set another as default
        if ($wasDefault) {
            $newDefault = Address::where('user_id', $userId)->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return response()->json([
            'message' => 'Alamat berhasil dihapus.',
        ]);
    }
}
