<?php

namespace Database\Factories;

use App\Models\DigitalInvitation;
use App\Models\InvitationTemplate;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DigitalInvitation>
 */
class DigitalInvitationFactory extends Factory
{
    protected $model = DigitalInvitation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'template_id' => InvitationTemplate::factory(),
            'slug' => 'inv-'.fake()->unique()->lexify('??????????'),
            'status' => DigitalInvitation::STATUS_DRAFT,
            'view_count' => 0,
            'activated_at' => null,
            'expires_at' => null,
            'last_viewed_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => DigitalInvitation::STATUS_ACTIVE,
            'activated_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => DigitalInvitation::STATUS_EXPIRED,
            'expires_at' => now()->subDays(1),
        ]);
    }
}
