<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Class OrderPolicy
 *
 * Authorization policy for Order operations.
 */
class OrderPolicy
{
    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Admin can view any order, customers can only view their own
        return $user->role === 'admin' || $user->id === $order->customer_id;
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create an order
        return true;
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        // Only admin can update orders
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        // Only admin can delete orders
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can initiate payment for the order.
     */
    public function pay(User $user, Order $order): bool
    {
        // Only the customer who owns the order can pay
        return $user->id === $order->customer_id;
    }

    /**
     * Determine whether the user can update order status.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        // Only admin can update order status
        return $user->role === 'admin';
    }
}
