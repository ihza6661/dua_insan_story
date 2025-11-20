<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalOrders = Order::count();
    $pendingOrders = Order::whereIn('order_status', ['Pending Payment', 'Partially Paid'])->count();
    $totalRevenue = Order::where('order_status', 'Completed')->sum('total_amount');
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
