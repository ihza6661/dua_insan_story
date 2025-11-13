<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CartService
{
    public function getOrCreateCart(Request $request): Cart
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');
        $guestCart = $sessionId ? Cart::where('session_id', $sessionId)->first() : null;

        if ($user) {
            $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

            if ($guestCart && $guestCart->user_id === null) {
                foreach ($guestCart->items as $item) {
                    $item->update(['cart_id' => $userCart->id]);
                }
                $guestCart->delete();
            }
            return $userCart;
        }

        if ($guestCart) {
            return $guestCart;
        }

        return Cart::create(['session_id' => Str::uuid()->toString()]);
    }

    public function addItem(Request $request): Cart
    {
        $validated = $request->validated();
        $variant = ProductVariant::with(['product.addOns', 'options.attribute'])->findOrFail($validated['variant_id']);
        $product = $variant->product;
        $cart = $this->getOrCreateCart($request);

        $unitPrice = $variant->price;
        $customizationDetails = [
            'options' => $variant->options->map(fn($opt) => [
                'name' => $opt->attribute->name,
                'value' => $opt->value,
            ]),
            'add_ons' => [],
        ];

        if (!empty($validated['add_ons'])) {
            foreach ($validated['add_ons'] as $addOnId) {
                $addOn = $product->addOns->find($addOnId);
                if (!$addOn) {
                    throw new InvalidArgumentException("Item tambahan dengan ID {$addOnId} tidak valid untuk produk ini.");
                }
                $unitPrice += $addOn->price;
                $customizationDetails['add_ons'][] = [
                    'id' => $addOn->id,
                    'name' => $addOn->name,
                    'price' => $addOn->price,
                    'weight' => $addOn->pivot->weight ?? null,
                ];
            }

            $customizationDetails['add_ons'] = collect($customizationDetails['add_ons'])
                ->sortBy('id')
                ->values()
                ->all();
        }

        $existingItem = $cart->items()
            ->where('product_variant_id', $variant->id)
            ->whereJsonContains('customization_details->add_ons', $customizationDetails['add_ons'])
            ->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $validated['quantity']);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $validated['quantity'],
                'unit_price' => $unitPrice,
                'customization_details' => $customizationDetails,
            ]);
        }

        $cart->load('items.product.category', 'items.variant.images');
        return $cart;
    }

    public function getCartContents(Request $request): Cart
    {
        $cart = $this->getOrCreateCart($request);
        $cart->load('items.product.category', 'items.product.addOns', 'items.variant.images');
        return $cart;
    }

    public function updateItemQuantity(int $cartItemId, int $quantity): Cart
    {
        $item = CartItem::findOrFail($cartItemId);
        $item->update(['quantity' => $quantity]);

        return $this->getCartContents(request());
    }

    public function removeItem(int $cartItemId): Cart
    {
        $item = CartItem::findOrFail($cartItemId);
        $cart = $item->cart;
        $item->delete();

        $cart->load('items.product.category', 'items.product.addOns', 'items.variant.images');
        return $cart;
    }

    public function clearCart(Request $request): Cart
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();
        $cart->load('items');
        return $cart;
    }
}
