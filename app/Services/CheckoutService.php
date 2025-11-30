<?php

namespace App\Services;

use App\DataTransferObjects\CheckoutData;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class CheckoutService
 *
 * Orchestrates the checkout process using specialized services.
 */
class CheckoutService
{
    /**
     * CheckoutService constructor.
     */
    public function __construct(
        protected CartService $cartService,
        protected OrderCreationService $orderCreationService,
        protected PaymentInitiationService $paymentService,
        protected ShippingCalculationService $shippingService
    ) {}

    /**
     * Process checkout and create order.
     *
     * @throws \Exception
     */
    public function processCheckout(Request $request): Order
    {
        $cart = $this->cartService->getOrCreateCart($request);
        $cart->loadMissing('items.product.addOns', 'items.variant');
        $user = $request->user();

        if (! $cart || $cart->items->isEmpty()) {
            throw new \Exception('Keranjang belanja Anda kosong.');
        }

        return DB::transaction(function () use ($request, $user, $cart) {
            $validated = $request->validated();
            $checkoutData = CheckoutData::fromArray($validated);

            // Handle prewedding photo upload
            $photoPath = null;
            if ($request->hasFile('prewedding_photo')) {
                $photoPath = $request->file('prewedding_photo')->store('prewedding-photos', 'public');
            }

            // Create the order
            $order = $this->orderCreationService->createOrderFromCart(
                customerId: $user->id,
                cart: $cart,
                shippingCost: $checkoutData->shippingCost,
                shippingAddress: $checkoutData->shippingAddress,
                shippingMethod: $checkoutData->shippingMethod,
                shippingService: $checkoutData->shippingService,
                courier: $checkoutData->courier
            );

            // Create invitation details
            $invitationData = $checkoutData->toInvitationDetailArray();
            $invitationData['prewedding_photo_path'] = $photoPath;
            $order->invitationDetail()->create($invitationData);

            // Create order items from cart
            $this->orderCreationService->createOrderItemsFromCart($order, $cart);

            // Initiate payment
            $this->paymentService->initiatePayment($order, $checkoutData->paymentOption);

            // Clear cart
            $this->clearCart($user, $cart);

            return $order;
        });
    }

    /**
     * Initiate final payment for partially paid order.
     *
     * @throws \Exception
     */
    public function initiateFinalPayment(Order $order): string
    {
        return $this->paymentService->initiateFinalPayment($order);
    }

    /**
     * Calculate shipping cost.
     *
     * @throws \Exception
     */
    public function calculateShippingCost(Request $request): array
    {
        return $this->shippingService->calculateShippingCost($request);
    }

    /**
     * Clear cart after checkout.
     *
     * @param  \App\Models\User|null  $user
     * @param  \App\Models\Cart  $cart
     */
    protected function clearCart($user, $cart): void
    {
        if ($user) {
            $cart->items()->delete();
        } else {
            session()->forget('cart');
        }
    }
}
