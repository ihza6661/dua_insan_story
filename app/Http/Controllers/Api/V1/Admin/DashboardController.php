<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::where('role', 'customer')->count();
        $totalOrders = Order::count();
        $pendingOrders = Order::whereIn('order_status', [
            'Pending Payment', 'pending_payment', 'pending payment',
            'Partially Paid', 'partially_paid', 'partially paid',
        ])->count();
        $totalRevenue = Order::whereIn('order_status', ['Completed', 'completed'])->sum('total_amount');
        $weeklyRevenue = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_amount) as revenue')
        )
            ->where('created_at', ' >=', now()->subWeek())
            ->groupBy('date')
            ->get();

        return response()->json([
            'stats' => [
                'total_customers' => $totalUsers,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'total_revenue' => $totalRevenue,
            ],
            'weekly_revenue' => $weeklyRevenue,
        ]);
    }
}
