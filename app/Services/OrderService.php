<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class OrderService
{
    /**
     * Get all orders for a specific user.
     *
     * @param User $user
     * @return Collection<int, Order>
     */
    public function getOrdersByUser(User $user): Collection
    {
        return $user->orders()->with([
            'items.product.variants.images',
            'items.product.variants',
            'items.variant.options',
            'invitationDetail',
            'payments',
        ])->latest()->get();
    }

    /**
     * Get a specific order by ID for a specific user.
     *
     * @param User $user
     * @param int $orderId
     * @return Order|null
     */
    public function getOrderByIdForUser(User $user, int $orderId): ?Order
    {
        return $user->orders()->with([
            'items.product.variants.images',
            'items.product.variants',
            'items.variant.options',
            'invitationDetail',
            'payments',
        ])->where('id', $orderId)->first();
    }
}
