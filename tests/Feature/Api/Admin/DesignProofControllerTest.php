<?php

namespace Tests\Feature\Api\Admin;

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

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    #[Test]
    public function admin_can_upload_design_proof()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file,
                'admin_notes' => 'First draft of the design',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'order_item_id',
                    'uploaded_by',
                    'version',
                    'file_name',
                    'file_type',
                    'file_size',
                    'status',
                    'admin_notes',
                ],
            ])
            ->assertJson([
                'message' => 'Design proof uploaded successfully',
                'data' => [
                    'order_item_id' => $orderItem->id,
                    'version' => 1,
                    'file_name' => 'design.jpg',
                    'status' => DesignProof::STATUS_PENDING,
                    'admin_notes' => 'First draft of the design',
                ],
            ]);

        $this->assertDatabaseHas('design_proofs', [
            'order_item_id' => $orderItem->id,
            'uploaded_by' => $this->admin->id,
            'version' => 1,
        ]);
    }

    #[Test]
    public function admin_can_upload_multiple_versions()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        // Upload v1
        $file1 = UploadedFile::fake()->image('design-v1.jpg');
        $response1 = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file1,
            ]);
        $response1->assertStatus(201);

        // Upload v2
        $file2 = UploadedFile::fake()->image('design-v2.jpg');
        $response2 = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file2,
            ]);
        $response2->assertStatus(201)
            ->assertJson(['data' => ['version' => 2]]);

        // Upload v3
        $file3 = UploadedFile::fake()->image('design-v3.jpg');
        $response3 = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file3,
            ]);
        $response3->assertStatus(201)
            ->assertJson(['data' => ['version' => 3]]);

        $this->assertDatabaseCount('design_proofs', 3);
    }

    #[Test]
    public function admin_upload_requires_valid_order_item()
    {
        $file = UploadedFile::fake()->image('design.jpg');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => 99999,
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_item_id']);
    }

    #[Test]
    public function admin_upload_requires_file()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    #[Test]
    public function admin_upload_validates_file_type()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    #[Test]
    public function admin_can_view_all_design_proofs_for_order()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem1 = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        $orderItem2 = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        // Create proofs for different order items
        $file1 = UploadedFile::fake()->image('design1.jpg');
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem1->id,
                'file' => $file1,
            ]);

        $file2 = UploadedFile::fake()->image('design2.jpg');
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem2->id,
                'file' => $file2,
            ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/design-proofs?order_id='.$order->id);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
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
    public function admin_can_view_single_design_proof()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');
        $uploadResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file,
            ]);

        $designProofId = $uploadResponse->json('data.id');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/design-proofs/'.$designProofId);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'order_item_id',
                    'version',
                    'file_name',
                    'status',
                    'uploaded_by',
                    'order_item',
                ],
            ]);
    }

    #[Test]
    public function admin_can_delete_design_proof()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');
        $uploadResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file,
            ]);

        $designProofId = $uploadResponse->json('data.id');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/v1/admin/design-proofs/'.$designProofId);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Design proof deleted successfully',
            ]);

        $this->assertDatabaseMissing('design_proofs', ['id' => $designProofId]);
    }

    #[Test]
    public function admin_can_get_design_proofs_by_order_item()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        // Upload multiple versions
        $file1 = UploadedFile::fake()->image('v1.jpg');
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file1,
            ]);

        $file2 = UploadedFile::fake()->image('v2.jpg');
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file2,
            ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/order-items/'.$orderItem->id.'/design-proofs');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'data' => [
                    ['version' => 2],
                    ['version' => 1],
                ],
            ]);
    }

    #[Test]
    public function guest_cannot_upload_design_proof()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');

        $response = $this->postJson('/api/v1/admin/design-proofs', [
            'order_item_id' => $orderItem->id,
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function customer_cannot_upload_design_proof()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $this->customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file,
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_index_requires_order_id()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/design-proofs');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    #[Test]
    public function admin_can_upload_pdf_file()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->create('design.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/design-proofs', [
                'order_item_id' => $orderItem->id,
                'file' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'file_name' => 'design.pdf',
                    'file_type' => 'application/pdf',
                ],
            ]);
    }
}
