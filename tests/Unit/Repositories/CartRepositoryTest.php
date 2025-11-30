<?php

namespace Tests\Unit\Repositories;

use App\Models\Cart;
use App\Models\User;
use App\Repositories\CartRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class CartRepositoryTest
 *
 * Unit tests for CartRepository.
 */
class CartRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected CartRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CartRepository(new Cart);
    }

    #[Test]
    public function it_can_find_cart_by_user(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);

        $found = $this->repository->findByUser($user);

        $this->assertNotNull($found);
        $this->assertEquals($cart->id, $found->id);
    }

    #[Test]
    public function it_returns_null_when_user_has_no_cart(): void
    {
        $user = User::factory()->create();

        $found = $this->repository->findByUser($user);

        $this->assertNull($found);
    }

    #[Test]
    public function it_can_find_cart_by_session_id(): void
    {
        $sessionId = 'test-session-123';
        $cart = Cart::factory()->create(['session_id' => $sessionId]);

        $found = $this->repository->findBySessionId($sessionId);

        $this->assertNotNull($found);
        $this->assertEquals($cart->id, $found->id);
    }

    #[Test]
    public function it_can_create_cart(): void
    {
        $user = User::factory()->create();

        $data = [
            'user_id' => $user->id,
        ];

        $cart = $this->repository->create($data);

        $this->assertInstanceOf(Cart::class, $cart);
        $this->assertEquals($user->id, $cart->user_id);
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
    }

    #[Test]
    public function it_can_clear_cart_items(): void
    {
        $cart = Cart::factory()->hasItems(3)->create();

        $this->assertCount(3, $cart->items);

        $result = $this->repository->clearItems($cart);

        $this->assertTrue($result);
        $this->assertCount(0, $cart->fresh()->items);
    }

    #[Test]
    public function it_can_delete_cart(): void
    {
        $cart = Cart::factory()->create();

        $result = $this->repository->delete($cart);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
    }

    #[Test]
    public function it_loads_relationships_when_finding_cart(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->hasItems(2)->create(['user_id' => $user->id]);

        $found = $this->repository->findByUser($user, ['items']);

        $this->assertTrue($found->relationLoaded('items'));
        $this->assertCount(2, $found->items);
    }
}
