<?php

namespace App\Services;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Class ShippingCalculationService
 *
 * Handles shipping cost calculation logic.
 */
class ShippingCalculationService
{
    /**
     * ShippingCalculationService constructor.
     */
    public function __construct(
        protected RajaOngkirService $rajaOngkirService,
        protected CartService $cartService
    ) {}

    /**
     * Calculate shipping cost for cart items.
     *
     * @throws \Exception
     */
    public function calculateShippingCost(Request $request): array
    {
        $validated = Validator::make($request->all(), [
            'postal_code' => ['required', 'string'],
            'courier' => ['required', 'string'],
        ])->validate();

        $cart = $this->cartService->getOrCreateCart($request);
        $cart->loadMissing('items.product.addOns', 'items.variant');

        if (! $cart || $cart->items->isEmpty()) {
            throw new \Exception('Keranjang belanja Anda kosong.');
        }

        // Check if all items are digital products
        $allDigital = $cart->items->every(function (CartItem $item) {
            return $item->product->isDigital();
        });

        // If all products are digital, return zero shipping cost
        if ($allDigital) {
            return [
                'message' => 'Produk digital tidak memerlukan pengiriman',
                'total_weight' => 0,
                'results' => [],
                'is_digital_only' => true,
            ];
        }

        $totalWeight = $this->calculateTotalWeight($cart->items);

        $originCityId = config('rajaongkir.origin_city_id');
        if (! $originCityId) {
            throw new \Exception('Origin city is not configured.');
        }

        $courier = $validated['courier'];

        $response = $this->rajaOngkirService->getCost(
            $originCityId,
            $validated['postal_code'],
            max($totalWeight, 1),
            $courier,
            'postal_code'
        );

        if (! is_array($response)) {
            return $response;
        }

        $response['total_weight'] = $totalWeight;
        $response['is_digital_only'] = false;

        return $response;
    }

    /**
     * Calculate total weight from cart items (excluding digital products).
     */
    public function calculateTotalWeight(iterable $cartItems): int
    {
        return (int) collect($cartItems)->sum(function (CartItem $item) {
            // Skip digital products - they have no weight
            if ($item->product->isDigital()) {
                return 0;
            }

            $variantWeight = $item->variant?->weight;
            $productWeight = (int) ($item->product->weight ?? 0);
            $baseWeight = $variantWeight !== null ? (int) $variantWeight : $productWeight;
            $addOnWeight = $item->addOns->sum(fn ($addOn) => (int) ($addOn->pivot->weight ?? $addOn->weight ?? 0));

            return ($baseWeight + $addOnWeight) * $item->quantity;
        });
    }
}
