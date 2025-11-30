<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;

/**
 * Class CartRepository
 *
 * Repository for handling Cart data operations.
 */
class CartRepository implements CartRepositoryInterface
{
    protected Cart $model;

    /**
     * CartRepository constructor.
     */
    public function __construct(Cart $cart)
    {
        $this->model = $cart;
    }

    /**
     * {@inheritDoc}
     */
    public function findByUser(User $user, array $relations = []): ?Cart
    {
        return $this->model->with($relations)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findBySessionId(string $sessionId, array $relations = []): ?Cart
    {
        return $this->model->with($relations)
            ->where('session_id', $sessionId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Cart
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function clearItems(Cart $cart): bool
    {
        $cart->items()->delete();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Cart $cart): bool
    {
        return $cart->delete();
    }
}
