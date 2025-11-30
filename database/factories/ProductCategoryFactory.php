<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->slug,
            'description' => $this->faker->paragraph,
            'image' => null,
        ];
    }
}
