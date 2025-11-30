<?php

namespace Tests\Unit\Services;

use App\Models\DesignProof;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\DesignProofService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DesignProofServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DesignProofService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DesignProofService;
        Storage::fake('public');
    }

    #[Test]
    public function it_can_upload_design_proof_with_image()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');

        $designProof = $this->service->uploadDesignProof(
            $orderItem,
            $file,
            $admin,
            'Test admin notes'
        );

        $this->assertInstanceOf(DesignProof::class, $designProof);
        $this->assertEquals($orderItem->id, $designProof->order_item_id);
        $this->assertEquals($admin->id, $designProof->uploaded_by);
        $this->assertEquals(1, $designProof->version);
        $this->assertEquals('design.jpg', $designProof->file_name);
        $this->assertEquals('Test admin notes', $designProof->admin_notes);
        $this->assertEquals(DesignProof::STATUS_PENDING, $designProof->status);
        $this->assertNotNull($designProof->file_url);
        $this->assertNotNull($designProof->thumbnail_url);
        $this->assertTrue(Storage::disk('public')->exists($designProof->file_url));
    }

    #[Test]
    public function it_can_upload_design_proof_with_pdf()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->create('design.pdf', 1024, 'application/pdf');

        $designProof = $this->service->uploadDesignProof(
            $orderItem,
            $file,
            $admin
        );

        $this->assertInstanceOf(DesignProof::class, $designProof);
        $this->assertEquals('design.pdf', $designProof->file_name);
        $this->assertEquals('application/pdf', $designProof->file_type);
        $this->assertNull($designProof->thumbnail_url); // PDFs don't get thumbnails
        $this->assertTrue(Storage::disk('public')->exists($designProof->file_url));
    }

    #[Test]
    public function it_increments_version_on_new_upload()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        // Upload first version
        $file1 = UploadedFile::fake()->image('design-v1.jpg');
        $proof1 = $this->service->uploadDesignProof($orderItem, $file1, $admin);

        // Upload second version
        $file2 = UploadedFile::fake()->image('design-v2.jpg');
        $proof2 = $this->service->uploadDesignProof($orderItem, $file2, $admin);

        // Upload third version
        $file3 = UploadedFile::fake()->image('design-v3.jpg');
        $proof3 = $this->service->uploadDesignProof($orderItem, $file3, $admin);

        $this->assertEquals(1, $proof1->version);
        $this->assertEquals(2, $proof2->version);
        $this->assertEquals(3, $proof3->version);
    }

    #[Test]
    public function it_can_approve_design_proof()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');
        $designProof = $this->service->uploadDesignProof($orderItem, $file, $admin);

        $this->assertEquals(DesignProof::STATUS_PENDING, $designProof->status);

        $approvedProof = $this->service->approveDesignProof($designProof, $customer);

        $this->assertEquals(DesignProof::STATUS_APPROVED, $approvedProof->status);
        $this->assertEquals($customer->id, $approvedProof->reviewed_by);
        $this->assertNotNull($approvedProof->reviewed_at);
    }

    #[Test]
    public function it_can_request_revision()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');
        $designProof = $this->service->uploadDesignProof($orderItem, $file, $admin);

        $feedback = 'Please change the color to blue';
        $revisedProof = $this->service->requestRevision($designProof, $customer, $feedback);

        $this->assertEquals(DesignProof::STATUS_REVISION_REQUESTED, $revisedProof->status);
        $this->assertEquals($customer->id, $revisedProof->reviewed_by);
        $this->assertEquals($feedback, $revisedProof->customer_feedback);
        $this->assertNotNull($revisedProof->reviewed_at);
    }

    #[Test]
    public function it_can_reject_design_proof()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');
        $designProof = $this->service->uploadDesignProof($orderItem, $file, $admin);

        $reason = 'This design does not match our requirements';
        $rejectedProof = $this->service->rejectDesignProof($designProof, $customer, $reason);

        $this->assertEquals(DesignProof::STATUS_REJECTED, $rejectedProof->status);
        $this->assertEquals($customer->id, $rejectedProof->reviewed_by);
        $this->assertEquals($reason, $rejectedProof->customer_feedback);
        $this->assertNotNull($rejectedProof->reviewed_at);
    }

    #[Test]
    public function it_can_mark_customer_as_notified()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');
        $designProof = $this->service->uploadDesignProof($orderItem, $file, $admin);

        // After upload, customer should already be notified (email sent automatically)
        $this->assertTrue($designProof->customer_notified);
        $this->assertNotNull($designProof->customer_notified_at);
    }

    #[Test]
    public function it_can_get_design_proofs_for_order()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order1 = Order::factory()->create();
        $order2 = Order::factory()->create();

        $orderItem1 = OrderItem::factory()->create([
            'order_id' => $order1->id,
            'product_id' => $product->id,
        ]);
        $orderItem2 = OrderItem::factory()->create([
            'order_id' => $order2->id,
            'product_id' => $product->id,
        ]);

        // Create proofs for order 1
        $file1 = UploadedFile::fake()->image('design1.jpg');
        $this->service->uploadDesignProof($orderItem1, $file1, $admin);

        $file2 = UploadedFile::fake()->image('design2.jpg');
        $this->service->uploadDesignProof($orderItem1, $file2, $admin);

        // Create proof for order 2
        $file3 = UploadedFile::fake()->image('design3.jpg');
        $this->service->uploadDesignProof($orderItem2, $file3, $admin);

        $order1Proofs = $this->service->getDesignProofsForOrder($order1->id);
        $order2Proofs = $this->service->getDesignProofsForOrder($order2->id);

        $this->assertCount(2, $order1Proofs);
        $this->assertCount(1, $order2Proofs);
    }

    #[Test]
    public function it_can_get_latest_design_proof()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        // Upload multiple versions
        $file1 = UploadedFile::fake()->image('v1.jpg');
        $proof1 = $this->service->uploadDesignProof($orderItem, $file1, $admin);

        $file2 = UploadedFile::fake()->image('v2.jpg');
        $proof2 = $this->service->uploadDesignProof($orderItem, $file2, $admin);

        $file3 = UploadedFile::fake()->image('v3.jpg');
        $proof3 = $this->service->uploadDesignProof($orderItem, $file3, $admin);

        $latestProof = $this->service->getLatestDesignProof($orderItem);

        $this->assertNotNull($latestProof);
        $this->assertEquals($proof3->id, $latestProof->id);
        $this->assertEquals(3, $latestProof->version);
    }

    #[Test]
    public function it_can_delete_design_proof_and_files()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');
        $designProof = $this->service->uploadDesignProof($orderItem, $file, $admin);

        $fileUrl = $designProof->file_url;
        $thumbnailUrl = $designProof->thumbnail_url;

        $this->assertTrue(Storage::disk('public')->exists($fileUrl));
        $this->assertTrue(Storage::disk('public')->exists($thumbnailUrl));

        $result = $this->service->deleteDesignProof($designProof);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('design_proofs', ['id' => $designProof->id]);
        $this->assertFalse(Storage::disk('public')->exists($fileUrl));
        $this->assertFalse(Storage::disk('public')->exists($thumbnailUrl));
    }

    #[Test]
    public function it_stores_file_metadata_correctly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('test-design.jpg')->size(512); // 512 KB

        $designProof = $this->service->uploadDesignProof($orderItem, $file, $admin);

        $this->assertEquals('test-design.jpg', $designProof->file_name);
        $this->assertEquals('image/jpeg', $designProof->file_type);
        $this->assertGreaterThan(0, $designProof->file_size);
    }
}
