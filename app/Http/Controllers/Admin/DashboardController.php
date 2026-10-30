<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\PickupRequest;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\Dashboard\OrderStatisticsService;
use App\Services\Dashboard\RevenueStatisticsService;
use App\Services\Dashboard\TableStatisticsService;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DashboardController extends Controller
{
    public function index(
        OrderStatisticsService $orderStatistics,
        RevenueStatisticsService $revenueStatistics,
        TableStatisticsService $tableStatistics,
    ): View|Response {
        try {
            $orderStats = $orderStatistics->summarize();
        } catch (Throwable) {
            return response()->view('admin.dashboard.unavailable', [], 503);
        }
        try {
            $revenueStats = $revenueStatistics->summarize();
        } catch (Throwable) {
            return response()->view('admin.dashboard.unavailable', [], 503);
        }
        try {
            $tableStats = $tableStatistics->summarize();
        } catch (Throwable) {
            return response()->view('admin.dashboard.unavailable', [], 503);
        }

        return view('admin.dashboard.index', [
            'orderStatistics' => $orderStats,
            'revenueStatistics' => $revenueStats,
            'tableStatistics' => $tableStats,
            'orderStatusLabels' => \App\Services\Dashboard\OrderStatisticsService::STATUS_LABELS,
            'tableStatusLabels' => \App\Services\Dashboard\TableStatisticsService::STATUS_LABELS,
        ]);
    }

    public function home(): View
    {
        $hasOrders = Schema::hasTable('orders');
        $dailyTotals = $hasOrders ? Order::query()->where('status', 'completed')
            ->whereDate('completed_at', '>=', today()->subDays(6))->get(['completed_at', 'total_amount'])
            ->groupBy(fn ($order) => $order->completed_at?->format('Y-m-d'))
            ->map(fn ($orders) => $orders->sum('total_amount')) : collect();
        $lastSevenDays = collect(range(6, 0))->map(function ($daysAgo) use ($dailyTotals) {
            $day = today()->subDays($daysAgo);
            return ['date' => $day->format('d/m'), 'amount' => (float) ($dailyTotals[$day->format('Y-m-d')] ?? 0)];
        });
        $maximum = max(1, (float) $lastSevenDays->max('amount'));
        $revenueChart = $lastSevenDays->map(fn ($day) => [
            ...$day,
            'height' => $day['amount'] > 0 ? max(3, round($day['amount'] * 100 / $maximum)) : 0,
        ]);
        return view('admin.home', [
            'revenueChart' => $revenueChart,
            'weeklyRevenue' => $lastSevenDays->sum('amount'),
            'revenue' => $hasOrders ? Order::query()->where('status', 'completed')->whereDate('completed_at', today())->sum('total_amount') : 0,
            'todayOrders' => $hasOrders ? Order::query()->whereDate('ordered_at', today())->count() : 0,
            'pendingOrders' => $hasOrders ? Order::query()->where('status', 'pending')->count() : 0,
            'productsCount' => Schema::hasTable('products') ? Product::count() : 0,
            'categoryCount' => Schema::hasTable('categories') ? Category::count() : 0,
            'promotionCount' => Schema::hasTable('promotions') ? Promotion::count() : 0,
            'pickupPending' => Schema::hasTable('pickup_requests') ? PickupRequest::where('status', 'pending')->count() : 0,
            'recentOrders' => $hasOrders ? Order::with('creator')->orderByDesc('id')->limit(6)->get() : collect(),
        ]);
    }
}