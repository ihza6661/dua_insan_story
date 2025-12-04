<?php

namespace Database\Factories;

use App\Models\PromoCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PromoCode>
 */
class PromoCodeFactory extends Factory
{
    protected $model = PromoCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('????')).fake()->numberBetween(10, 99),
            'description' => fake()->sentence(),
            'discount_type' => fake()->randomElement(['percentage', 'fixed']),
            'discount_value' => fake()->randomElement([10, 15, 20, 25, 50000, 100000]),
            'min_purchase_amount' => fake()->randomElement([0, 100000, 200000, 500000]),
            'max_discount_amount' => null,
            'total_usage_limit' => fake()->randomElement([null, 50, 100, 200]),
            'usage_limit_per_user' => fake()->randomElement([1, 2, 3]),
            'used_count' => 0,
            'is_active' => true,
            'valid_from' => now()->subDays(7),
            'valid_until' => now()->addDays(30),
        ];
    }

    /**
     * Indicate that the promo code is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the promo code is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->subDays(30),
            'valid_until' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the promo code is percentage type.
     */
    public function percentage(int $value = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'percentage',
            'discount_value' => $value,
        ]);
    }

    /**
     * Indicate that the promo code is fixed type.
     */
    public function fixed(int $value = 50000): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'fixed',
            'discount_value' => $value,
        ]);
    }
}
