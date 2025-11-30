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
        ];
    }
}
