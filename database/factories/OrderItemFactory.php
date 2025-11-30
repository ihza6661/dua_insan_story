<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->numberBetween(50000, 500000);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'sub_total' => $quantity * $unitPrice,
        ];
    }

    public function withVariant(): static
    {
        return $this->state(fn () => [
            'product_variant_id' => ProductVariant::factory(),
        ]);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn () => [
            'order_id' => $order->id,
        ]);
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn () => [
            'product_id' => $product->id,
        ]);
    }
}
