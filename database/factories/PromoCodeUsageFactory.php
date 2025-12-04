<?php

namespace Database\Factories;

use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PromoCodeUsage>
 */
class PromoCodeUsageFactory extends Factory
{
    protected $model = PromoCodeUsage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promo_code_id' => PromoCode::factory(),
            'user_id' => User::factory(),
            'order_id' => fake()->numberBetween(1, 1000),
            'discount_amount' => fake()->randomElement([10000, 20000, 50000, 100000]),
        ];
    }
}
