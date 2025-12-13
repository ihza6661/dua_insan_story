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

        // Search by order number or customer name
        // Fixed: Use leftJoin outside where clause for proper PostgreSQL compatibility
        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']); // Normalize search term
            
            // Join users table once for customer search
            $query->leftJoin('users', 'orders.customer_id', '=', 'users.id');
            
            // Database-agnostic case-insensitive search using LOWER()
            // Use CONCAT for MySQL or CAST for PostgreSQL to convert id to string
            $driver = config('database.default');
            $idCast = $driver === 'pgsql' ? 'CAST(orders.id AS TEXT)' : 'CAST(orders.id AS CHAR)';
            
            $query->where(function ($q) use ($search, $idCast) {
                $q->whereRaw("LOWER({$idCast}) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw('LOWER(orders.order_number) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.full_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.email) LIKE ?', ["%{$search}%"]);
            });
            
            // Select distinct orders to avoid duplicates from join
            $query->select('orders.*')->distinct();
        }

        // Filter by order status
        if (! empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $query->where('orders.order_status', $filters['order_status']);
        }

        // Filter by payment status
        if (! empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('orders.payment_status', $filters['payment_status']);
        }

        // Filter by date range
        if (! empty($filters['date_from'])) {
            $query->whereDate('orders.created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('orders.created_at', '<=', $filters['date_to']);
        }

        return $query->latest('orders.created_at')->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getForExport(array $filters = [], array $relations = []): Collection
    {
        $query = $this->model->with($relations);

        // Search by order number or customer name
        // Fixed: Use leftJoin outside where clause for proper PostgreSQL compatibility
        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']); // Normalize search term
            
            // Join users table once for customer search
            $query->leftJoin('users', 'orders.customer_id', '=', 'users.id');
            
            // Database-agnostic case-insensitive search using LOWER()
            // Use CONCAT for MySQL or CAST for PostgreSQL to convert id to string
            $driver = config('database.default');
            $idCast = $driver === 'pgsql' ? 'CAST(orders.id AS TEXT)' : 'CAST(orders.id AS CHAR)';
            
            $query->where(function ($q) use ($search, $idCast) {
                $q->whereRaw("LOWER({$idCast}) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw('LOWER(orders.order_number) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.full_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(users.email) LIKE ?', ["%{$search}%"]);
            });
            
            // Select distinct orders to avoid duplicates from join
            $query->select('orders.*')->distinct();
        }

        // Filter by order status
        if (! empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $query->where('orders.order_status', $filters['order_status']);
        }

        // Filter by payment status
        if (! empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('orders.payment_status', $filters['payment_status']);
        }

        // Filter by date range
        if (! empty($filters['date_from'])) {
            $query->whereDate('orders.created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('orders.created_at', '<=', $filters['date_to']);
        }

        return $query->latest('orders.created_at')->get();
    }
}
