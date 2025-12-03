<?php

namespace Database\Seeders;

use App\Models\InvitationDetail;
use App\Models\Order;
use Illuminate\Database\Seeder;

class InvitationDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all orders from the database
        $orders = Order::all();

        if ($orders->isEmpty()) {
            $this->command->warn('No orders found in database. Please run OrderSeeder first.');

            return;
        }

        $this->command->info("Found {$orders->count()} orders. Creating invitation details...");

        $created = 0;

        foreach ($orders as $order) {
            // Check if invitation detail already exists for this order
            if ($order->invitationDetail) {
                $this->command->warn("Order #{$order->id} already has invitation details. Skipping...");

                continue;
            }

            // Determine if wedding should be in past or future based on order status
            $completedStatuses = ['Completed', 'Delivered', 'Cancelled'];
            $isPastWedding = in_array($order->order_status, $completedStatuses);

            // Use factory state methods for past/future weddings
            if ($isPastWedding) {
                InvitationDetail::factory()->past()->create(['order_id' => $order->id]);
            } else {
                InvitationDetail::factory()->future()->create(['order_id' => $order->id]);
            }

            $created++;
            $this->command->info("✓ Created invitation detail for Order #{$order->id} (Status: {$order->order_status})");
        }

        $this->command->info('');
        $this->command->info('✅ Invitation details seeding completed!');
        $this->command->info("Total invitation details created: {$created}");
    }
}
