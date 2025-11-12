<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Resources\V1\Admin\OrderResource;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['customer', 'items.product', 'items.variant', 'shippingAddress', 'billingAddress'])->latest()->get();

        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'items.product', 'items.variant', 'shippingAddress', 'billingAddress', 'invitationDetail', 'payments']);

        return new OrderResource($order);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:processing,packing,shipped,completed,cancelled',
        ]);

        $order->order_status = $request->input('status');
        $order->save();

        return new OrderResource($order);
    }
}
