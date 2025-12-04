<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ReviewImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReviewImage>
 */
class ReviewImageFactory extends Factory
{
    protected $model = ReviewImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'image_path' => 'reviews/'.uniqid().'.jpg',
            'alt_text' => $this->faker->sentence(),
            'display_order' => $this->faker->numberBetween(1, 5),
        ];
    }
}
