<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get date range from request (default to last 30 days)
        $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // Cache key based on date range
        $cacheKey = "dashboard_stats_{$dateFrom}_{$dateTo}";

        // Cache basic stats for 10 minutes
        $stats = Cache::remember($cacheKey, 600, function () use ($dateFrom, $dateTo) {
            $ordersQuery = Order::whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);

            $totalCustomers = User::where('role', 'customer')->count();
            $totalOrders = (clone $ordersQuery)->count();
            $pendingOrders = Order::whereIn('order_status', [
                Order::STATUS_PENDING_PAYMENT,
                Order::STATUS_PARTIALLY_PAID,
            ])->count();
            $completedOrders = (clone $ordersQuery)->where('order_status', Order::STATUS_COMPLETED)->count();
            $totalRevenue = Order::where('order_status', Order::STATUS_COMPLETED)->sum('total_amount');

            $avgOrderValue = $completedOrders > 0
                ? (clone $ordersQuery)->where('order_status', Order::STATUS_COMPLETED)->avg('total_amount')
                : 0;

            $cancelledOrders = (clone $ordersQuery)->where('order_status', Order::STATUS_CANCELLED)->count();
            $cancellationRate = $totalOrders > 0 ? ($cancelledOrders / $totalOrders) * 100 : 0;

            return [
                'total_customers' => $totalCustomers,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'completed_orders' => $completedOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_revenue' => $totalRevenue,
                'avg_order_value' => $avgOrderValue,
                'cancellation_rate' => number_format($cancellationRate, 2),
            ];
        });

        // Extract individual stats from cache
        $totalCustomers = $stats['total_customers'];
        $totalOrders = $stats['total_orders'];
        $pendingOrders = $stats['pending_orders'];
        $completedOrders = $stats['completed_orders'];
        $cancelledOrders = $stats['cancelled_orders'];
        $totalRevenue = $stats['total_revenue'];
        $avgOrderValue = $stats['avg_order_value'];
        $cancellationRate = $stats['cancellation_rate'];

        // Revenue trend (daily breakdown) - cached for 10 minutes
        $revenueTrendCacheKey = "dashboard_revenue_trend_{$dateFrom}_{$dateTo}";
        $revenueTrend = Cache::remember($revenueTrendCacheKey, 600, function () use ($dateFrom, $dateTo) {
            return Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as order_count')
            )
                ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                ->where('order_status', Order::STATUS_COMPLETED)
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();
        });

        // Order status breakdown - cached for 10 minutes
        $statusBreakdownCacheKey = "dashboard_status_breakdown_{$dateFrom}_{$dateTo}";
        $statusBreakdown = Cache::remember($statusBreakdownCacheKey, 600, function () use ($dateFrom, $dateTo) {
            return Order::select('order_status', DB::raw('COUNT(*) as count'))
                ->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                ->groupBy('order_status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->order_status => $item->count];
                });
        });

        // Recent orders (last 10)
        $recentOrders = Order::with(['customer', 'items'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer ? $order->customer->full_name : 'N/A',
                    'total_amount' => $order->total_amount,
                    'order_status' => $order->order_status,
                    'payment_status' => $order->payment_status,
                    'created_at' => $order->created_at->toISOString(),
                ];
            });

        // Top selling products (cached for 1 hour)
        $topProductsCacheKey = "dashboard_top_products_{$dateFrom}_{$dateTo}";
        $topProducts = Cache::remember($topProductsCacheKey, 3600, function () use ($dateFrom, $dateTo) {
            return DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.sub_total) as total_revenue')
                )
                ->whereBetween('orders.created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                ->whereIn('orders.order_status', [Order::STATUS_COMPLETED, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED])
                ->groupBy('products.id', 'products.name')
                ->orderBy('total_quantity', 'desc')
                ->take(5)
                ->get();
        });

        // Low stock products (stock < 10) - cached for 30 minutes
        $lowStockCacheKey = 'dashboard_low_stock_products';
        $lowStockProducts = Cache::remember($lowStockCacheKey, 1800, function () {
            return DB::table('product_variants')
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->select(
                    'products.id',
                    'products.name',
                    'product_variants.id as variant_id',
                    'product_variants.stock',
                    'product_variants.price'
                )
                ->where('product_variants.stock', '<', 10)
                ->where('product_variants.stock', '>', 0)
                ->where('products.is_active', true)
                ->orderBy('product_variants.stock', 'asc')
                ->take(10)
                ->get();
        });

        return response()->json([
            'stats' => [
                'total_customers' => $totalCustomers,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'completed_orders' => $completedOrders,
                'total_revenue' => $totalRevenue,
                'avg_order_value' => round($avgOrderValue, 2),
                'cancellation_rate' => round($cancellationRate, 2),
                'cancelled_orders' => $cancelledOrders,
            ],
            'revenue_trend' => $revenueTrend,
            'status_breakdown' => $statusBreakdown,
            'recent_orders' => $recentOrders,
            'top_products' => $topProducts,
            'low_stock_products' => $lowStockProducts,
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }
}
