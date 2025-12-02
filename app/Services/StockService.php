<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing product stock/inventory.
 */
class StockService
{
    /**
     * Restore stock for all items in an order.
     *
     * @param  Order  $order  The order whose stock should be restored
     * @return bool True if stock was successfully restored, false otherwise
     *
     * @throws \Exception
     */
    public function restoreStockForOrder(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $restoredCount = 0;

            foreach ($order->items as $orderItem) {
                if ($this->restoreStockForOrderItem($orderItem)) {
                    $restoredCount++;
                }
            }

            Log::info('Stock restored for order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'items_restored' => $restoredCount,
                'total_items' => $order->items->count(),
            ]);

            return $restoredCount === $order->items->count();
        });
    }

    /**
     * Restore stock for a single order item.
     *
     * @param  OrderItem  $orderItem  The order item whose stock should be restored
     * @return bool True if stock was successfully restored, false otherwise
     */
    public function restoreStockForOrderItem(OrderItem $orderItem): bool
    {
        if (! $orderItem->product_variant_id) {
            Log::warning('OrderItem has no product variant, skipping stock restoration', [
                'order_item_id' => $orderItem->id,
            ]);

            return false;
        }

        $variant = ProductVariant::find($orderItem->product_variant_id);

        if (! $variant) {
            Log::warning('ProductVariant not found, skipping stock restoration', [
                'order_item_id' => $orderItem->id,
                'product_variant_id' => $orderItem->product_variant_id,
            ]);

            return false;
        }

        // Restore the stock by adding back the quantity
        $variant->increment('stock', $orderItem->quantity);

        Log::info('Stock restored for order item', [
            'order_item_id' => $orderItem->id,
            'product_variant_id' => $variant->id,
            'quantity_restored' => $orderItem->quantity,
            'new_stock' => $variant->stock,
        ]);

        return true;
    }

    /**
     * Deduct stock for all items in an order.
     * This is useful when processing an order.
     *
     * @param  Order  $order  The order whose stock should be deducted
     * @return bool True if stock was successfully deducted, false otherwise
     *
     * @throws \Exception
     */
    public function deductStockForOrder(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $deductedCount = 0;

            foreach ($order->items as $orderItem) {
                if ($this->deductStockForOrderItem($orderItem)) {
                    $deductedCount++;
                }
            }

            Log::info('Stock deducted for order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'items_deducted' => $deductedCount,
                'total_items' => $order->items->count(),
            ]);

            return $deductedCount === $order->items->count();
        });
    }

    /**
     * Deduct stock for a single order item.
     *
     * @param  OrderItem  $orderItem  The order item whose stock should be deducted
     * @return bool True if stock was successfully deducted, false otherwise
     */
    public function deductStockForOrderItem(OrderItem $orderItem): bool
    {
        if (! $orderItem->product_variant_id) {
            Log::warning('OrderItem has no product variant, skipping stock deduction', [
                'order_item_id' => $orderItem->id,
            ]);

            return false;
        }

        $variant = ProductVariant::find($orderItem->product_variant_id);

        if (! $variant) {
            Log::warning('ProductVariant not found, skipping stock deduction', [
                'order_item_id' => $orderItem->id,
                'product_variant_id' => $orderItem->product_variant_id,
            ]);

            return false;
        }

        // Check if sufficient stock is available
        if ($variant->stock < $orderItem->quantity) {
            Log::warning('Insufficient stock for order item', [
                'order_item_id' => $orderItem->id,
                'product_variant_id' => $variant->id,
                'available_stock' => $variant->stock,
                'required_quantity' => $orderItem->quantity,
            ]);

            return false;
        }

        // Deduct the stock
        $variant->decrement('stock', $orderItem->quantity);

        Log::info('Stock deducted for order item', [
            'order_item_id' => $orderItem->id,
            'product_variant_id' => $variant->id,
            'quantity_deducted' => $orderItem->quantity,
            'remaining_stock' => $variant->stock,
        ]);

        return true;
    }

    /**
     * Check if sufficient stock is available for an order.
     *
     * @param  Order  $order  The order to check stock availability for
     * @return bool True if sufficient stock is available, false otherwise
     */
    public function checkStockAvailability(Order $order): bool
    {
        foreach ($order->items as $orderItem) {
            if (! $this->checkStockAvailabilityForOrderItem($orderItem)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if sufficient stock is available for an order item.
     *
     * @param  OrderItem  $orderItem  The order item to check stock availability for
     * @return bool True if sufficient stock is available, false otherwise
     */
    public function checkStockAvailabilityForOrderItem(OrderItem $orderItem): bool
    {
        if (! $orderItem->product_variant_id) {
            return false;
        }

        $variant = ProductVariant::find($orderItem->product_variant_id);

        if (! $variant) {
            return false;
        }

        return $variant->stock >= $orderItem->quantity;
    }
}
