<?php

namespace App\DataTransferObjects;

/**
 * Class OrderData
 *
 * Data Transfer Object for order operations.
 */
class OrderData
{
    /**
     * OrderData constructor.
     */
    public function __construct(
        public readonly int $customerId,
        public readonly string $orderNumber,
        public readonly float $totalAmount,
        public readonly string $shippingAddress,
        public readonly float $shippingCost,
        public readonly string $shippingMethod,
        public readonly string $orderStatus = 'Pending Payment',
        public readonly ?string $shippingService = null,
        public readonly ?string $courier = null,
        public readonly string $paymentGateway = 'midtrans',
        public readonly ?int $promoCodeId = null,
        public readonly float $discountAmount = 0,
        public readonly float $subtotalAmount = 0,
        public readonly ?string $paymentOption = null,
    ) {}

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customerId: (int) $data['customer_id'],
            orderNumber: $data['order_number'],
            totalAmount: (float) $data['total_amount'],
            shippingAddress: $data['shipping_address'],
            shippingCost: (float) $data['shipping_cost'],
            shippingMethod: $data['shipping_method'],
            orderStatus: $data['order_status'] ?? 'Pending Payment',
            shippingService: $data['shipping_service'] ?? null,
            courier: $data['courier'] ?? null,
            paymentGateway: $data['payment_gateway'] ?? 'midtrans',
        );
    }

    /**
     * Convert to array for database operations.
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'order_number' => $this->orderNumber,
            'total_amount' => $this->totalAmount,
            'subtotal_amount' => $this->subtotalAmount,
            'discount_amount' => $this->discountAmount,
            'promo_code_id' => $this->promoCodeId,
            'shipping_address' => $this->shippingAddress,
            'shipping_cost' => $this->shippingCost,
            'shipping_method' => $this->shippingMethod,
            'order_status' => $this->orderStatus,
            'shipping_service' => $this->shippingService,
            'courier' => $this->courier,
            'payment_gateway' => $this->paymentGateway,
            'payment_option' => $this->paymentOption,
        ];
    }
}
