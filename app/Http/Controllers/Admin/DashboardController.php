<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\OrderStatisticsService;
use App\Services\Dashboard\RevenueStatisticsService;
use App\Services\Dashboard\TableStatisticsService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(OrderStatisticsService $service, RevenueStatisticsService $revenue, TableStatisticsService $tables): View|Response
    {
        try {
            $statistics = [
                'orderStatistics' => $service->summarize(),
                'orderStatusLabels' => OrderStatisticsService::STATUS_LABELS,
                'revenueStatistics' => $revenue->summarize(),
                'tableStatistics' => $tables->summarize(),
                'tableStatusLabels' => TableStatisticsService::STATUS_LABELS,
            ];
        } catch (QueryException $exception) {
            // SQL bindings can contain sensitive data; do not render/report the raw exception.
            return response()->view('admin.dashboard.unavailable', [], 503);
        }

        return view('admin.dashboard.index', $statistics);
    }
}
