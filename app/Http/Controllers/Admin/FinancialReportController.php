<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\FinancialReportRequest;
use App\Services\FinancialReportService;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function index(FinancialReportRequest $request, FinancialReportService $service): View
    {
        return view('admin.finance.index', ['report' => $service->report($request->validated('from'), $request->validated('to'))]);
    }
}
