<?php

namespace Tests\Unit\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class ProductPolicyTest
 *
 * Unit tests for ProductPolicy.
 */
class ProductPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ProductPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ProductPolicy;
    }

    #[Test]
    public function anyone_can_view_any_products(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->viewAny(null)); // Guest
    }

    #[Test]
    public function admin_can_view_inactive_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['is_active' => false]);

        $result = $this->policy->view($admin, $product);

        $this->assertTrue($result);
    }

    #[Test]
    public function anyone_can_view_active_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);

        $this->assertTrue($this->policy->view($user, $product));
        $this->assertTrue($this->policy->view(null, $product)); // Guest
    }

    #[Test]
    public function guest_cannot_view_inactive_product(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $result = $this->policy->view(null, $product);

        $this->assertFalse($result);
    }

    #[Test]
    public function customer_cannot_view_inactive_product(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['is_active' => false]);

        $result = $this->policy->view($customer, $product);

        $this->assertFalse($result);
    }

    #[Test]
    public function only_admin_can_create_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($customer));
    }

    #[Test]
    public function only_admin_can_update_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create();

        $this->assertTrue($this->policy->update($admin, $product));
        $this->assertFalse($this->policy->update($customer, $product));
    }

    #[Test]
    public function only_admin_can_delete_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $product));
        $this->assertFalse($this->policy->delete($customer, $product));
    }
}
