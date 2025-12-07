<?php

namespace Database\Factories;

use App\Models\InvitationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationTemplate>
 */
class InvitationTemplateFactory extends Factory
{
    protected $model = InvitationTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug,
            'description' => fake()->sentence,
            'thumbnail_image' => fake()->imageUrl(640, 480, 'wedding', true),
            'price' => 150000,
            'template_component' => 'TemplateComponent',
            'is_active' => true,
            'usage_count' => 0,
        ];
    }
}
