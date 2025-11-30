<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class OrderRepository
 *
 * Repository for handling Order data operations.
 */
class OrderRepository implements OrderRepositoryInterface
{
    protected Order $model;

    /**
     * OrderRepository constructor.
     */
    public function __construct(Order $order)
    {
        $this->model = $order;
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $relations = []): Collection
    {
        return $this->model->with($relations)->latest()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id, array $relations = []): ?Order
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByIdOrFail(int $id, array $relations = []): Order
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Order
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrdersByUser(User $user, array $relations = []): Collection
    {
        return $user->orders()
            ->with($relations)
            ->latest()
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findOrderByIdForUser(User $user, int $orderId, array $relations = []): ?Order
    {
        return $user->orders()
            ->with($relations)
            ->where('id', $orderId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getLatestOrders(int $limit = 10, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $order->order_status = $status;
        $order->save();

        return $order->fresh();
    }
}
