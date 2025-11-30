<?php

namespace App\Repositories\Contracts;

use App\Models\Cart;
use App\Models\User;

/**
 * Interface CartRepositoryInterface
 *
 * Defines the contract for Cart repository operations.
 */
interface CartRepositoryInterface
{
    /**
     * Find cart by user.
     */
    public function findByUser(User $user, array $relations = []): ?Cart;

    /**
     * Find cart by session ID.
     */
    public function findBySessionId(string $sessionId, array $relations = []): ?Cart;

    /**
     * Create a new cart.
     */
    public function create(array $data): Cart;

    /**
     * Delete all items from cart.
     */
    public function clearItems(Cart $cart): bool;

    /**
     * Delete cart and all its items.
     */
    public function delete(Cart $cart): bool;
}
