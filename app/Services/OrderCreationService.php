<?php

namespace App\Services;

use App\DataTransferObjects\OrderData;
use App\Models\Cart;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Str;

/**
 * Class OrderCreationService
 *
 * Handles order creation logic.
 */
class OrderCreationService
{
    /**
     * OrderCreationService constructor.
     */
    public function __construct(
        protected OrderRepositoryInterface $orderRepository
    ) {}

    /**
     * Create an order from cart items.
     */
    public function createOrderFromCart(
        int $customerId,
        Cart $cart,
        float $shippingCost,
        string $shippingAddress,
        string $shippingMethod,
        ?string $shippingService = null,
        ?string $courier = null,
        ?int $promoCodeId = null,
        float $discountAmount = 0
    ): Order {
        $cartTotal = $cart->items->sum(fn ($item) => $item->quantity * $item->unit_price);
        $subtotalAmount = $cartTotal;
        $totalAmount = $cartTotal - $discountAmount + $shippingCost;

        $orderData = new OrderData(
            customerId: $customerId,
            orderNumber: $this->generateOrderNumber(),
            totalAmount: $totalAmount,
            subtotalAmount: $subtotalAmount,
            discountAmount: $discountAmount,
            shippingAddress: $shippingAddress,
            shippingCost: $shippingCost,
            shippingMethod: $shippingMethod,
            shippingService: $shippingService,
            courier: $courier,
            promoCodeId: $promoCodeId,
        );

        return $this->orderRepository->create($orderData->toArray());
    }

    /**
     * Create order items from cart items.
     */
    public function createOrderItemsFromCart(Order $order, Cart $cart): void
    {
        foreach ($cart->items as $cartItem) {
            $orderItem = $order->items()->create([
                'product_id' => $cartItem->product_id,
                'product_variant_id' => $cartItem->product_variant_id,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->unit_price,
                'sub_total' => $cartItem->quantity * $cartItem->unit_price,
            ]);

            // Create order item metadata from cart customization
            if (! empty($cartItem->customization_details)) {
                foreach ($cartItem->customization_details['options'] ?? [] as $option) {
                    $orderItem->meta()->create([
                        'meta_key' => $option['name'],
                        'meta_value' => $option['value'],
                    ]);
                }
            }
        }
    }

    /**
     * Generate unique order number.
     */
    protected function generateOrderNumber(): string
    {
        return 'INV-'.Str::uuid();
    }
}
