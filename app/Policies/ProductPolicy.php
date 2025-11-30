<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Class ProductPolicy
 *
 * Authorization policy for Product operations.
 */
class ProductPolicy
{
    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone (including guests) can view products
        return true;
    }

    /**
     * Determine whether the user can view the product.
     */
    public function view(?User $user, Product $product): bool
    {
        // Admin can view all products, others can only view active products
        if ($user && $user->role === 'admin') {
            return true;
        }

        return $product->is_active;
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->role === 'admin';
    }
}
