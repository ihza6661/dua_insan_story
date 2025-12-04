<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Admin controller for managing promo codes
 */
class PromoCodeController extends Controller
{
    /**
     * Display a listing of promo codes.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PromoCode::query();

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by code
        if ($request->has('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        $promoCodes = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'message' => 'Promo codes retrieved successfully',
            'data' => $promoCodes,
        ]);
    }

    /**
     * Store a newly created promo code.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:50', 'unique:promo_codes,code', 'alpha_dash'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_purchase' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after:valid_from'],
            'is_active' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Additional validation: percentage should not exceed 100
        if ($request->discount_type === 'percentage' && $request->discount_value > 100) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['discount_value' => ['Percentage discount cannot exceed 100%']],
            ], 422);
        }

        $promoCode = PromoCode::create([
            'code' => strtoupper($request->code),
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'min_purchase' => $request->min_purchase,
            'max_discount' => $request->max_discount,
            'usage_limit' => $request->usage_limit,
            'times_used' => 0,
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
            'is_active' => $request->get('is_active', true),
        ]);

        return response()->json([
            'message' => 'Promo code created successfully',
            'data' => $promoCode,
        ], 201);
    }

    /**
     * Display the specified promo code.
     */
    public function show(PromoCode $promoCode): JsonResponse
    {
        $promoCode->load('usages.user');

        return response()->json([
            'message' => 'Promo code retrieved successfully',
            'data' => $promoCode,
        ]);
    }

    /**
     * Update the specified promo code.
     */
    public function update(Request $request, PromoCode $promoCode): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('promo_codes')->ignore($promoCode->id)],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_purchase' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after:valid_from'],
            'is_active' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Additional validation: percentage should not exceed 100
        if ($request->discount_type === 'percentage' && $request->discount_value > 100) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['discount_value' => ['Percentage discount cannot exceed 100%']],
            ], 422);
        }

        $promoCode->update([
            'code' => strtoupper($request->code),
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'min_purchase' => $request->min_purchase,
            'max_discount' => $request->max_discount,
            'usage_limit' => $request->usage_limit,
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
            'is_active' => $request->get('is_active', $promoCode->is_active),
        ]);

        return response()->json([
            'message' => 'Promo code updated successfully',
            'data' => $promoCode->fresh(),
        ]);
    }

    /**
     * Remove the specified promo code.
     */
    public function destroy(PromoCode $promoCode): JsonResponse
    {
        // Check if promo code has been used
        if ($promoCode->times_used > 0) {
            return response()->json([
                'message' => 'Cannot delete promo code that has been used. Consider deactivating it instead.',
            ], 422);
        }

        $promoCode->delete();

        return response()->json([
            'message' => 'Promo code deleted successfully',
        ]);
    }

    /**
     * Get usage statistics for promo codes.
     */
    public function statistics(): JsonResponse
    {
        $totalPromoCodes = PromoCode::count();
        $activePromoCodes = PromoCode::active()->count();
        $totalUsages = PromoCode::sum('times_used');
        
        $mostUsedPromoCodes = PromoCode::where('times_used', '>', 0)
            ->orderBy('times_used', 'desc')
            ->limit(10)
            ->get(['code', 'discount_type', 'discount_value', 'times_used', 'usage_limit']);

        return response()->json([
            'message' => 'Statistics retrieved successfully',
            'data' => [
                'total_promo_codes' => $totalPromoCodes,
                'active_promo_codes' => $activePromoCodes,
                'total_usages' => $totalUsages,
                'most_used_promo_codes' => $mostUsedPromoCodes,
            ],
        ]);
    }

    /**
     * Toggle promo code active status.
     */
    public function toggleStatus(PromoCode $promoCode): JsonResponse
    {
        $promoCode->update([
            'is_active' => !$promoCode->is_active,
        ]);

        return response()->json([
            'message' => 'Promo code status updated successfully',
            'data' => $promoCode->fresh(),
        ]);
    }
}
