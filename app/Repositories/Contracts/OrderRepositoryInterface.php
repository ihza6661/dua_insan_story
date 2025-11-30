<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface OrderRepositoryInterface
 *
 * Defines the contract for Order repository operations.
 */
interface OrderRepositoryInterface
{
    /**
     * Get all orders with optional relationships.
     */
    public function all(array $relations = []): Collection;

    /**
     * Find an order by ID with optional relationships.
     */
    public function findById(int $id, array $relations = []): ?Order;

    /**
     * Find an order by ID or fail.
     */
    public function findByIdOrFail(int $id, array $relations = []): Order;

    /**
     * Create a new order.
     */
    public function create(array $data): Order;

    /**
     * Update an existing order.
     */
    public function update(Order $order, array $data): Order;

    /**
     * Get all orders for a specific user.
     */
    public function getOrdersByUser(User $user, array $relations = []): Collection;

    /**
     * Find order by ID for a specific user.
     */
    public function findOrderByIdForUser(User $user, int $orderId, array $relations = []): ?Order;

    /**
     * Get latest orders with optional limit.
     */
    public function getLatestOrders(int $limit = 10, array $relations = []): Collection;

    /**
     * Update order status.
     */
    public function updateStatus(Order $order, string $status): Order;
}
