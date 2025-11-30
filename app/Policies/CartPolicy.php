<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;

/**
 * Class CartPolicy
 *
 * Authorization policy for Cart operations.
 */
class CartPolicy
{
    /**
     * Determine whether the user can view the cart.
     */
    public function view(?User $user, Cart $cart): bool
    {
        // If authenticated, cart must belong to user
        if ($user) {
            return $cart->user_id === $user->id;
        }

        // For guests, cart must have session_id
        return $cart->session_id !== null;
    }

    /**
     * Determine whether the user can update the cart.
     */
    public function update(?User $user, Cart $cart): bool
    {
        return $this->view($user, $cart);
    }

    /**
     * Determine whether the user can delete the cart.
     */
    public function delete(?User $user, Cart $cart): bool
    {
        return $this->view($user, $cart);
    }

    /**
     * Determine whether the user can add items to the cart.
     */
    public function addItem(?User $user, Cart $cart): bool
    {
        return $this->view($user, $cart);
    }
}
