<?php

namespace Tests\Unit\Policies;

use App\Models\Order;
use App\Models\User;
use App\Policies\OrderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class OrderPolicyTest
 *
 * Unit tests for OrderPolicy.
 */
class OrderPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected OrderPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new OrderPolicy;
    }

    #[Test]
    public function admin_can_view_any_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $result = $this->policy->viewAny($admin);

        $this->assertTrue($result);
    }

    #[Test]
    public function customer_cannot_view_any_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $result = $this->policy->viewAny($customer);

        $this->assertFalse($result);
    }

    #[Test]
    public function admin_can_view_any_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create();

        $result = $this->policy->view($admin, $order);

        $this->assertTrue($result);
    }

    #[Test]
    public function customer_can_view_own_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $result = $this->policy->view($customer, $order);

        $this->assertTrue($result);
    }

    #[Test]
    public function customer_cannot_view_other_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['customer_id' => $otherCustomer->id]);

        $result = $this->policy->view($customer, $order);

        $this->assertFalse($result);
    }

    #[Test]
    public function any_authenticated_user_can_create_order(): void
    {
        $user = User::factory()->create();

        $result = $this->policy->create($user);

        $this->assertTrue($result);
    }

    #[Test]
    public function only_admin_can_update_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create();

        $this->assertTrue($this->policy->update($admin, $order));
        $this->assertFalse($this->policy->update($customer, $order));
    }

    #[Test]
    public function only_admin_can_delete_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $order));
        $this->assertFalse($this->policy->delete($customer, $order));
    }

    #[Test]
    public function only_order_owner_can_pay(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $this->assertTrue($this->policy->pay($customer, $order));
        $this->assertFalse($this->policy->pay($otherCustomer, $order));
    }

    #[Test]
    public function only_admin_can_update_order_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create();

        $this->assertTrue($this->policy->updateStatus($admin, $order));
        $this->assertFalse($this->policy->updateStatus($customer, $order));
    }
}
