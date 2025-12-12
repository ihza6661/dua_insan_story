<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class OrderService
 *
 * Business logic layer for Order operations.
 */
class OrderService
{
    /**
     * OrderService constructor.
     */
    public function __construct(
        protected OrderRepositoryInterface $orderRepository
    ) {}

    /**
     * Get all orders for a specific user with payment totals.
     *
     * @return Collection<int, Order>
     */
    public function getOrdersByUser(User $user): Collection
    {
        return $this->orderRepository->getOrdersByUser($user, [
            'items.product.variants.images',
            'items.product.variants',
            'items.product.template',
            'items.variant.options',
            'items.review',
            'invitationDetail',
            'payments',
        ]);
    }

    /**
     * Get a specific order by ID for a specific user with payment totals.
     */
    public function getOrderByIdForUser(User $user, int $orderId): ?Order
    {
        return $this->orderRepository->findOrderByIdForUser($user, $orderId, [
            'items.product.variants.images',
            'items.product.variants',
            'items.product.template',
            'items.variant.options',
            'items.review',
            'invitationDetail',
            'payments',
        ]);
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Order $order, string $status): Order
    {
        return $this->orderRepository->updateStatus($order, $status);
    }

    /**
     * Get all orders with relationships (for admin).
     */
    public function getAllOrders(): Collection
    {
        return $this->orderRepository->all([
            'customer',
            'items.product',
            'items.variant',
            'shippingAddress',
            'billingAddress',
        ]);
    }

    /**
     * Get order by ID with full details.
     */
    public function getOrderById(int $orderId): Order
    {
        return $this->orderRepository->findByIdOrFail($orderId, [
            'customer',
            'items.product',
            'items.variant',
            'shippingAddress',
            'billingAddress',
            'invitationDetail',
            'payments',
        ]);
    }
}
