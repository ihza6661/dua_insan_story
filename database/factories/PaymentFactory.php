<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transaction_id' => 'trans-'.Str::random(10),
            'payment_gateway' => 'midtrans',
            'amount' => $this->faker->numberBetween(10000, 100000),
            'status' => 'pending',
            'payment_type' => 'full',
        ];
    }

    /**
     * Indicate that the payment is successful.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Indicate that the payment is for down payment.
     */
    public function downPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'dp',
        ]);
    }

    /**
     * Indicate that the payment is the final payment.
     */
    public function finalPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'final',
        ]);
    }
}
