<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PromoCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromoCodeController extends Controller
{
    public function __construct(
        protected PromoCodeService $promoCodeService
    ) {}

    /**
     * Validate a promo code for checkout
     * POST /api/v1/promo-codes/validate
     */
    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $code = $request->input('code');
        $subtotal = (float) $request->input('subtotal');
        $user = $request->user();

        $result = $this->promoCodeService->validatePromoCode($code, $user, $subtotal);

        if (! $result['valid']) {
            return response()->json([
                'message' => $result['message'],
                'data' => [
                    'valid' => false,
                    'discount_amount' => 0,
                ],
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => [
                'valid' => true,
                'discount_amount' => $result['discount'],
                'code_details' => $result['code_details'],
            ],
        ]);
    }

    /**
     * Get active promo codes (public)
     * GET /api/v1/promo-codes/active
     */
    public function active(): JsonResponse
    {
        $promoCodes = $this->promoCodeService->getActivePromoCodes();

        return response()->json([
            'message' => 'Promo code aktif berhasil diambil.',
            'data' => $promoCodes,
        ]);
    }
}
