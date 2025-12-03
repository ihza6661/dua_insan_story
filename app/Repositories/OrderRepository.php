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

    /**
     * {@inheritDoc}
     */
    public function getPaginatedWithFilters(array $filters = [], array $relations = [], int $perPage = 20)
    {
        $query = $this->model->with($relations);

        // Search by order number or customer name (optimized with join instead of whereHas)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('orders.id', 'like', "%{$search}%")
                    ->orWhere('orders.order_number', 'like', "%{$search}%")
                    ->orWhere(function ($q) use ($search) {
                        $q->join('users', 'orders.user_id', '=', 'users.id')
                            ->where('users.full_name', 'like', "%{$search}%")
                            ->orWhere('users.email', 'like', "%{$search}%");
                    });
            });
            // Select distinct to avoid duplicates from join
            $query->select('orders.*')->distinct();
        }

        // Filter by order status
        if (! empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $query->where('order_status', $filters['order_status']);
        }

        // Filter by payment status
        if (! empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Filter by date range
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getForExport(array $filters = [], array $relations = []): Collection
    {
        $query = $this->model->with($relations);

        // Search by order number or customer name (optimized with join instead of whereHas)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('orders.id', 'like', "%{$search}%")
                    ->orWhere('orders.order_number', 'like', "%{$search}%")
                    ->orWhere(function ($q) use ($search) {
                        $q->join('users', 'orders.user_id', '=', 'users.id')
                            ->where('users.full_name', 'like', "%{$search}%")
                            ->orWhere('users.email', 'like', "%{$search}%");
                    });
            });
            // Select distinct to avoid duplicates from join
            $query->select('orders.*')->distinct();
        }

        // Filter by order status
        if (! empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $query->where('order_status', $filters['order_status']);
        }

        // Filter by payment status
        if (! empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Filter by date range
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->get();
    }
}
