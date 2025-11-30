<?php

namespace Tests\Feature;

use App\Mail\DesignProofReviewed;
use App\Mail\DesignProofUploaded;
use App\Models\DesignProof;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\DesignProofService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DesignProofEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected DesignProofService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DesignProofService::class);
        Storage::fake('public');
        Mail::fake();
    }

    public function test_email_sent_when_design_proof_uploaded(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $file = UploadedFile::fake()->image('design.jpg');

        // Act
        $designProof = $this->service->uploadDesignProof($orderItem, $file, $admin, 'Test notes');

        // Assert
        Mail::assertQueued(DesignProofUploaded::class, function ($mail) use ($customer, $designProof) {
            return $mail->hasTo($customer->email) &&
                   $mail->designProof->id === $designProof->id;
        });

        $this->assertTrue($designProof->customer_notified);
        $this->assertNotNull($designProof->customer_notified_at);
    }

    public function test_email_sent_when_design_proof_approved(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        $designProof = DesignProof::create([
            'order_item_id' => $orderItem->id,
            'uploaded_by' => $admin->id,
            'version' => 1,
            'file_url' => 'design-proofs/test.jpg',
            'file_name' => 'test.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 1024,
            'status' => DesignProof::STATUS_PENDING,
            'customer_notified' => false,
        ]);

        // Act
        $result = $this->service->approveDesignProof($designProof, $customer);

        // Assert
        Mail::assertQueued(DesignProofReviewed::class, function ($mail) use ($customer, $result) {
            return $mail->hasTo($customer->email) &&
                   $mail->designProof->id === $result->id &&
                   $mail->designProof->status === DesignProof::STATUS_APPROVED;
        });

        $this->assertEquals(DesignProof::STATUS_APPROVED, $result->status);
    }

    public function test_email_sent_when_revision_requested(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        $designProof = DesignProof::create([
            'order_item_id' => $orderItem->id,
            'uploaded_by' => $admin->id,
            'version' => 1,
            'file_url' => 'design-proofs/test.jpg',
            'file_name' => 'test.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 1024,
            'status' => DesignProof::STATUS_PENDING,
            'customer_notified' => false,
        ]);

        $feedback = 'Please adjust the colors';

        // Act
        $result = $this->service->requestRevision($designProof, $customer, $feedback);

        // Assert
        Mail::assertQueued(DesignProofReviewed::class, function ($mail) use ($admin, $result) {
            return $mail->hasTo($admin->email) &&
                   $mail->designProof->id === $result->id &&
                   $mail->designProof->status === DesignProof::STATUS_REVISION_REQUESTED;
        });

        $this->assertEquals(DesignProof::STATUS_REVISION_REQUESTED, $result->status);
        $this->assertEquals($feedback, $result->customer_feedback);
    }

    public function test_email_sent_when_design_proof_rejected(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        $designProof = DesignProof::create([
            'order_item_id' => $orderItem->id,
            'uploaded_by' => $admin->id,
            'version' => 1,
            'file_url' => 'design-proofs/test.jpg',
            'file_name' => 'test.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 1024,
            'status' => DesignProof::STATUS_PENDING,
            'customer_notified' => false,
        ]);

        $reason = 'Does not meet requirements';

        // Act
        $result = $this->service->rejectDesignProof($designProof, $customer, $reason);

        // Assert
        Mail::assertQueued(DesignProofReviewed::class, function ($mail) use ($admin, $result) {
            return $mail->hasTo($admin->email) &&
                   $mail->designProof->id === $result->id &&
                   $mail->designProof->status === DesignProof::STATUS_REJECTED;
        });

        $this->assertEquals(DesignProof::STATUS_REJECTED, $result->status);
        $this->assertEquals($reason, $result->customer_feedback);
    }

    public function test_email_contains_correct_subject_for_approved(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        $designProof = DesignProof::create([
            'order_item_id' => $orderItem->id,
            'uploaded_by' => $admin->id,
            'version' => 1,
            'file_url' => 'design-proofs/test.jpg',
            'file_name' => 'test.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 1024,
            'status' => DesignProof::STATUS_APPROVED,
            'customer_notified' => false,
        ]);

        // Act
        $mail = new DesignProofReviewed($designProof);
        $envelope = $mail->envelope();

        // Assert
        $this->assertEquals('Design Proof Approved - Ready to Proceed', $envelope->subject);
    }

    public function test_email_contains_correct_subject_for_revision(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        $designProof = DesignProof::create([
            'order_item_id' => $orderItem->id,
            'uploaded_by' => $admin->id,
            'version' => 1,
            'file_url' => 'design-proofs/test.jpg',
            'file_name' => 'test.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 1024,
            'status' => DesignProof::STATUS_REVISION_REQUESTED,
            'customer_notified' => false,
        ]);

        // Act
        $mail = new DesignProofReviewed($designProof);
        $envelope = $mail->envelope();

        // Assert
        $this->assertEquals('Design Revision Requested', $envelope->subject);
    }

    public function test_uploaded_mail_has_correct_subject(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        $designProof = DesignProof::create([
            'order_item_id' => $orderItem->id,
            'uploaded_by' => $admin->id,
            'version' => 1,
            'file_url' => 'design-proofs/test.jpg',
            'file_name' => 'test.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 1024,
            'status' => DesignProof::STATUS_PENDING,
            'customer_notified' => false,
        ]);

        // Act
        $mail = new DesignProofUploaded($designProof);
        $envelope = $mail->envelope();

        // Assert
        $this->assertEquals('Your Design Proof is Ready for Review', $envelope->subject);
    }
}
