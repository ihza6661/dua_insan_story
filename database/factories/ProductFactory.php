<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => ProductCategory::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph,
            'base_price' => $this->faker->numberBetween(50000, 500000),
            'weight' => $this->faker->numberBetween(100, 5000),
            'min_order_quantity' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
