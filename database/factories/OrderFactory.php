<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'order_number' => 'order-'.Str::random(10),
            'total_amount' => $this->faker->numberBetween(10000, 100000),
            'shipping_address' => $this->faker->address,
            'order_status' => 'Pending Payment',
            'payment_status' => 'pending',
            'shipping_cost' => $this->faker->randomFloat(2, 10000, 50000),
            'shipping_service' => $this->faker->randomElement(['REG', 'YES', 'OKE']),
            'courier' => $this->faker->randomElement(['jne', 'tiki', 'pos']),
        ];
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'Paid',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Indicate that the order is partially paid.
     */
    public function partiallyPaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'Partially Paid',
            'payment_status' => 'partially_paid',
        ]);
    }

    /**
     * Indicate that the order payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'Failed',
            'payment_status' => 'failed',
        ]);
    }
}
