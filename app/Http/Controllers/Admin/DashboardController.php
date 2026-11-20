<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Dashboard\OrderStatisticsService;
use App\Services\Dashboard\PeriodStatisticsService;
use App\Services\Dashboard\RevenueStatisticsService;
use App\Services\Dashboard\TableStatisticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function home(
        Request $request,
        OrderStatisticsService $orderStatistics,
        RevenueStatisticsService $revenueStatistics,
        TableStatisticsService $tableStatistics,
        PeriodStatisticsService $periodStatistics,
    ): View|Response {
        try {
            $orderStats = $orderStatistics->summarize();
        } catch (Throwable) {
            return response()->view('admin.dashboard.unavailable', [], 503);
        }
        try {
            $revenueStats = $revenueStatistics->summarize();
            $days = $request->query('days', '7');
            $period = $periodStatistics->summarize($days === '30' ? 30 : 7, $revenueStats['available'],
                $request->query('metric') === 'completed' ? 'completed' : 'paid');
            $recentOrders = $period && $period['has_order_details']
                ? Order::with('creator')->latest('ordered_at')->latest('id')->limit(6)->get() : collect();
        } catch (Throwable) {
            return response()->view('admin.dashboard.unavailable', [], 503);
        }
        try {
            $tableStats = $tableStatistics->summarize();
        } catch (Throwable) {
            return response()->view('admin.dashboard.unavailable', [], 503);
        }

        return view('admin.home', [
            'period' => $period,
            'recentOrders' => $recentOrders,
            'orderStatistics' => $orderStats,
            'revenueStatistics' => $revenueStats,
            'tableStatistics' => $tableStats,
            'orderStatusLabels' => \App\Services\Dashboard\OrderStatisticsService::STATUS_LABELS,
            'tableStatusLabels' => \App\Services\Dashboard\TableStatisticsService::STATUS_LABELS,
        ]);
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('admin.home');
    }
}
