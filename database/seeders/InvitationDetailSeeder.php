<?php

namespace Database\Seeders;

use App\Models\InvitationDetail;
use App\Models\Order;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class InvitationDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all orders from the database
        $orders = Order::all();

        if ($orders->isEmpty()) {
            $this->command->warn('No orders found in database. Please run OrderSeeder first.');

            return;
        }

        $this->command->info("Found {$orders->count()} orders. Creating invitation details...");

        foreach ($orders as $order) {
            // Check if invitation detail already exists for this order
            if ($order->invitationDetail) {
                $this->command->warn("Order #{$order->id} already has invitation details. Skipping...");

                continue;
            }

            // Determine if wedding should be in past or future based on order status
            $completedStatuses = ['Completed', 'Delivered', 'Cancelled'];
            $isPastWedding = in_array($order->order_status, $completedStatuses);

            // Create invitation detail using factory
            $invitationDetail = InvitationDetail::factory()
                ->create([
                    'order_id' => $order->id,
                    'akad_date' => $isPastWedding
                        ? $faker->dateTimeBetween('-6 months', '-1 month')->format('Y-m-d')
                        : $faker->dateTimeBetween('+1 month', '+6 months')->format('Y-m-d'),
                ]);

            // Update reception date to match logic (same day or next day)
            $akadDate = new \DateTime($invitationDetail->akad_date);
            $receptionDate = clone $akadDate;

            if ($faker->boolean(30)) { // 30% chance reception is next day
                $receptionDate->modify('+1 day');
            }

            $invitationDetail->update([
                'reception_date' => $receptionDate->format('Y-m-d'),
            ]);

            $this->command->info("✓ Created invitation detail for Order #{$order->id} (Status: {$order->order_status})");
        }

        $this->command->info('');
        $this->command->info('✅ Invitation details seeding completed!');
        $this->command->info("Total invitation details created: {$orders->count()}");
    }
}
