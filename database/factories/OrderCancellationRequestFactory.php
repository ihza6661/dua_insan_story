<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderCancellationRequest>
 */
class OrderCancellationRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Order::factory(),
            'requested_by' => \App\Models\User::factory()->create(['role' => 'customer'])->id,
            'cancellation_reason' => $this->faker->sentence(10),
            'status' => 'pending',
            'refund_initiated' => false,
            'stock_restored' => false,
        ];
    }
}
