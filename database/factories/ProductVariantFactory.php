<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::factory(),
            'sku' => 'SKU-'.strtoupper($this->faker->bothify('??##??##')),
            'stock' => $this->faker->numberBetween(0, 100),
            'price' => $this->faker->numberBetween(50000, 500000),
            'weight' => $this->faker->numberBetween(100, 1000),
        ];
    }
}
