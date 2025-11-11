<?php

namespace App\Services;

use App\Models\InvitationDetail;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

class CheckoutService
{
    protected $rajaOngkirService;
    protected $midtransService;

    public function __construct(RajaOngkirService $rajaOngkirService, MidtransService $midtransService)
    {
        $this->rajaOngkirService = $rajaOngkirService;
        $this->midtransService = $midtransService;
    }

    public function processCheckout(Request $request): Order
    {
        $user = $request->user(); // Can be null for guests
        $cart = $user ? $user->cart : session('cart'); // Adjust to get cart from session for guests

        if (!$cart || $cart->items->isEmpty()) {
            throw new \Exception('Keranjang belanja Anda kosong.');
        }

        return DB::transaction(function () use ($request, $user, $cart) {
            $validated = $request->validated();
            $paymentOption = $validated['payment_option'] ?? 'full';

            $photoPath = null;
            if ($request->hasFile('prewedding_photo')) {
                $photoPath = $request->file('prewedding_photo')->store('prewedding-photos', 'public');
            }

            $cartTotal = $cart->items->sum(fn($item) => $item->quantity * $item->unit_price);

            $shippingCost = $validated['shipping_cost'] ?? 0;
            $shippingService = $validated['shipping_service'] ?? null;
            $courier = $validated['courier'] ?? null;
            $totalAmount = $cartTotal + $shippingCost;

            $order = Order::create([
                'customer_id' => $user->id,
                'order_number' => 'INV-' . Str::uuid(),
                'total_amount' => $totalAmount,
                'shipping_address' => $validated['shipping_address'],
                'order_status' => 'Pending Payment',
                'shipping_cost' => $shippingCost,
                'shipping_service' => $shippingService,
                'courier' => $courier,
                'payment_gateway' => 'midtrans',
            ]);

            $order->invitationDetail()->create([
                'bride_full_name' => $validated['bride_full_name'],
                'groom_full_name' => $validated['groom_full_name'],
                'bride_nickname' => $validated['bride_nickname'],
                'groom_nickname' => $validated['groom_nickname'],
                'bride_parents' => $validated['bride_parents'],
                'groom_parents' => $validated['groom_parents'],
                'akad_date' => $validated['akad_date'],
                'akad_time' => $validated['akad_time'],
                'akad_location' => $validated['akad_location'],
                'reception_date' => $validated['reception_date'],
                'reception_time' => $validated['reception_time'],
                'reception_location' => $validated['reception_location'],
                'gmaps_link' => $validated['gmaps_link'] ?? null,
                'prewedding_photo_path' => $photoPath,
            ]);

            foreach ($cart->items as $cartItem) {
                $orderItem = $order->items()->create([
                    'product_id' => $cartItem->product_id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'sub_total' => $cartItem->quantity * $cartItem->unit_price,
                ]);

                if (!empty($cartItem->customization_details)) {
                    foreach ($cartItem->customization_details['options'] ?? [] as $option) {
                        $orderItem->meta()->create([
                            'meta_key' => $option['name'],
                            'meta_value' => $option['value'],
                        ]);
                    }
                }
            }

            $this->handleInitialPayment($order, $paymentOption);

            if ($user) {
                $cart->items()->delete();
            } else {
                // Clear session cart for guest
                session()->forget('cart');
            }

            return $order;
        });
    }

    /**
     * Handle the initial payment creation (DP or Full Payment).
     *
     * @param Order $order
     * @param string $paymentOption 'dp' or 'full'
     * @return void
     */
    private function handleInitialPayment(Order $order, string $paymentOption): void
    {
        $paymentAmount = 0;
        $paymentType = 'full';
        $downPaymentRate = Config::get('payments.down_payment_rate', 0.5);

        if ($paymentOption === 'dp') {
            $paymentAmount = $order->total_amount * $downPaymentRate;
            $paymentType = 'dp';
        } else {
            $paymentAmount = $order->total_amount;
        }

        // Create the payment record
        $payment = $order->payments()->create([
            'transaction_id' => Str::uuid()->toString(),
            'amount' => $paymentAmount,
            'status' => 'pending',
            'payment_type' => $paymentType,
        ]);

        // Generate Midtrans Snap Token for the specific payment amount
        $snapToken = $this->midtransService->createTransactionToken($order, $payment);

        // Attach Snap Token to the payment record
        $payment->snap_token = $snapToken;
        $payment->save();

        // Also attach to order for easier access if needed
        $order->snap_token = $snapToken;
        $order->save();
    }

    /**
     * Initiates the final payment for an order that was partially paid.
     *
     * @param Order $order
     * @return string The Snap Token for the final payment.
     * @throws \Exception
     */
    public function initiateFinalPayment(Order $order): string
    {
        if ($order->order_status !== 'Partially Paid') {
            throw new \Exception('Order is not awaiting final payment.');
        }

        $paidAmount = $order->payments()->where('status', 'paid')->sum('amount');
        $remainingAmount = $order->total_amount - $paidAmount;

        if ($remainingAmount <= 0) {
            throw new \Exception('No remaining balance to be paid.');
        }

        // Create the final payment record
        $finalPayment = $order->payments()->create([
            'transaction_id' => Str::uuid()->toString(),
            'amount' => $remainingAmount,
            'status' => 'pending',
            'payment_type' => 'final',
        ]);

        // Generate a new Snap Token for the remaining amount
        $snapToken = $this->midtransService->createTransactionToken($order, $finalPayment);

        $finalPayment->snap_token = $snapToken;
        $finalPayment->save();

        return $snapToken;
    }
    public function calculateShippingCost(array $data)
    {
        if (!isset($data['postal_code'])) {
            throw new \Exception('Postal code is required.');
        }

        $destinationCity = $this->rajaOngkirService->getCityByPostalCode($data['postal_code']);

        if (!$destinationCity) {
            throw new \Exception('Could not find city for the given postal code.');
        }

        $originCityId = config('rajaongkir.origin_city_id'); // Assuming you have a default origin city ID in your config
        $destinationCityId = $destinationCity['city_id'];
        $weight = $data['weight'] ?? 1000; // Default to 1kg
        $courier = $data['courier'];

        return $this->rajaOngkirService->getCost($originCityId, $destinationCityId, $weight, $courier);
    }

    public function clearCart(\App\Models\User $user): void
    {
        $cart = $user->cart;
        if ($cart) {
            $cart->items()->delete();
        }
    }
}
