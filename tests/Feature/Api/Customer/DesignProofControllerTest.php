<?php

namespace Tests\Feature\Api\Customer;

use App\Models\DesignProof;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DesignProofControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected User $otherCustomer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->otherCustomer = User::factory()->create(['role' => 'customer']);
    }

    protected function createDesignProofForCustomer(User $customer): DesignProof
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file,
            ]);

        $responseData = $response->json('data');

        return DesignProof::find($responseData['id']);
    }

    #[Test]
    public function customer_can_view_design_proofs_for_own_order()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $this->customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file,
            ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/orders/'.$order->id.'/design-proofs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'order_item_id',
                        'version',
                        'file_name',
                        'status',
                    ],
                ],
            ]);
    }

    #[Test]
    public function customer_cannot_view_design_proofs_for_other_customer_order()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $this->otherCustomer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file,
            ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/orders/'.$order->id.'/design-proofs');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized access to this order',
            ]);
    }

    #[Test]
    public function customer_can_view_single_design_proof_for_own_order()
    {
        $designProofData = $this->createDesignProofForCustomer($this->customer);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/design-proofs/'.$designProofData['id']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'order_item_id',
                    'version',
                    'file_name',
                    'status',
                ],
            ]);
    }

    #[Test]
    public function customer_cannot_view_design_proof_for_other_customer_order()
    {
        $designProofData = $this->createDesignProofForCustomer($this->otherCustomer);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/design-proofs/'.$designProofData['id']);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized access to this design proof',
            ]);
    }

    #[Test]
    public function customer_can_approve_design_proof()
    {
        $designProofData = $this->createDesignProofForCustomer($this->customer);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/design-proofs/'.$designProofData['id'].'/approve');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Design proof approved successfully',
                'data' => [
                    'status' => DesignProof::STATUS_APPROVED,
                ],
            ]);

        $this->assertDatabaseHas('design_proofs', [
            'id' => $designProofData['id'],
            'status' => DesignProof::STATUS_APPROVED,
        ]);
    }

    #[Test]
    public function customer_cannot_approve_other_customer_design_proof()
    {
        $designProofData = $this->createDesignProofForCustomer($this->otherCustomer);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/design-proofs/'.$designProofData['id'].'/approve');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized access to this design proof',
            ]);

        $this->assertDatabaseHas('design_proofs', [
            'id' => $designProofData['id'],
            'status' => DesignProof::STATUS_PENDING,
        ]);
    }

    #[Test]
    public function customer_can_request_revision()
    {
        $designProofData = $this->createDesignProofForCustomer($this->customer);

        $feedback = 'Please change the font to Arial and increase the size';

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/design-proofs/'.$designProofData['id'].'/request-revision', [
                'feedback' => $feedback,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Revision requested successfully',
                'data' => [
                    'status' => DesignProof::STATUS_REVISION_REQUESTED,
                    'customer_feedback' => $feedback,
                ],
            ]);

        $this->assertDatabaseHas('design_proofs', [
            'id' => $designProofData['id'],
            'status' => DesignProof::STATUS_REVISION_REQUESTED,
            'customer_feedback' => $feedback,
        ]);
    }

    #[Test]
    public function customer_cannot_request_revision_without_feedback()
    {
        $designProofData = $this->createDesignProofForCustomer($this->customer);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/design-proofs/'.$designProofData['id'].'/request-revision', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['feedback']);
    }

    #[Test]
    public function customer_cannot_request_revision_for_other_customer_proof()
    {
        $designProofData = $this->createDesignProofForCustomer($this->otherCustomer);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/design-proofs/'.$designProofData['id'].'/request-revision', [
                'feedback' => 'Please change the design',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized access to this design proof',
            ]);
    }

    #[Test]
    public function customer_can_reject_design_proof()
    {
        $designProofData = $this->createDesignProofForCustomer($this->customer);

        $reason = 'This design does not match our requirements at all';

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/design-proofs/'.$designProofData['id'].'/reject', [
                'reason' => $reason,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Design proof rejected successfully',
                'data' => [
                    'status' => DesignProof::STATUS_REJECTED,
                    'customer_feedback' => $reason,
                ],
            ]);

        $this->assertDatabaseHas('design_proofs', [
            'id' => $designProofData['id'],
            'status' => DesignProof::STATUS_REJECTED,
            'customer_feedback' => $reason,
        ]);
    }

    #[Test]
    public function customer_cannot_reject_without_reason()
    {
        $designProofData = $this->createDesignProofForCustomer($this->customer);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/design-proofs/'.$designProofData['id'].'/reject', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    #[Test]
    public function customer_cannot_reject_other_customer_proof()
    {
        $designProofData = $this->createDesignProofForCustomer($this->otherCustomer);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/design-proofs/'.$designProofData['id'].'/reject', [
                'reason' => 'Not what we wanted',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized access to this design proof',
            ]);
    }

    #[Test]
    public function customer_can_view_all_their_design_proofs()
    {
        // Create multiple design proofs for customer
        $this->createDesignProofForCustomer($this->customer);
        $this->createDesignProofForCustomer($this->customer);

        // Create design proof for other customer
        $this->createDesignProofForCustomer($this->otherCustomer);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/design-proofs');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function guest_cannot_view_design_proofs()
    {
        $designProofData = $this->createDesignProofForCustomer($this->customer);

        $response = $this->getJson('/api/v1/design-proofs/'.$designProofData['id']);

        $response->assertStatus(403);
    }

    #[Test]
    public function guest_cannot_approve_design_proof()
    {
        $designProofData = $this->createDesignProofForCustomer($this->customer);

        $response = $this->postJson('/api/v1/design-proofs/'.$designProofData['id'].'/approve');

        $response->assertStatus(403);
    }

    #[Test]
    public function guest_cannot_request_revision()
    {
        $designProofData = $this->createDesignProofForCustomer($this->customer);

        $response = $this->postJson('/api/v1/design-proofs/'.$designProofData['id'].'/request-revision', [
            'feedback' => 'Change the design',
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function guest_cannot_reject_design_proof()
    {
        $designProofData = $this->createDesignProofForCustomer($this->customer);

        $response = $this->postJson('/api/v1/design-proofs/'.$designProofData['id'].'/reject', [
            'reason' => 'Not good',
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function customer_can_see_version_history()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $this->customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        // Upload v1
        $file1 = UploadedFile::fake()->image('v1.jpg');
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file1,
            ]);

        // Upload v2
        $file2 = UploadedFile::fake()->image('v2.jpg');
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file2,
            ]);

        // Upload v3
        $file3 = UploadedFile::fake()->image('v3.jpg');
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file3,
            ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/orders/'.$order->id.'/design-proofs');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJson([
                'data' => [
                    ['version' => 3],
                    ['version' => 2],
                    ['version' => 1],
                ],
            ]);
    }
}
