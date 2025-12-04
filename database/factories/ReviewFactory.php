<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'customer_id' => User::factory()->create(['role' => 'customer'])->id,
            'product_id' => Product::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->paragraph(),
            'is_verified' => $this->faker->boolean(80), // 80% verified
            'is_approved' => true, // Approved by default (matches migration default)
            'is_featured' => false,
            'admin_response' => null,
            'admin_responded_at' => null,
            'admin_responder_id' => null,
            'helpful_count' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the review is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
        ]);
    }

    /**
     * Indicate that the review is pending (waiting for approval).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }

    /**
     * Indicate that the review is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }

    /**
     * Indicate that the review is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'is_approved' => true, // Featured reviews must be approved
        ]);
    }

    /**
     * Indicate that the review has an admin response.
     */
    public function withAdminResponse(): static
    {
        return $this->state(fn (array $attributes) => [
            'admin_response' => $this->faker->paragraph(),
            'admin_responded_at' => now(),
            'admin_responder_id' => User::factory()->create(['role' => 'admin'])->id,
        ]);
    }

    /**
     * Indicate that the review is verified (from actual purchase).
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Indicate that the review is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
        ]);
    }

    /**
     * Set a specific rating for the review.
     */
    public function rating(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $rating,
        ]);
    }
}
