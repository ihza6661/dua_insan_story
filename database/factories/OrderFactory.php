<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_number' => 'order-' . Str::random(10),
            'total_amount' => $this->faker->numberBetween(10000, 100000),
            'shipping_address' => $this->faker->address,
            'order_status' => 'Pending Payment',
        ];
    }
}
