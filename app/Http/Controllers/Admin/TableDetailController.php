<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TableOrderContext;
use App\Services\TableStatusReadService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TableDetailController extends Controller
{
    public function show(string $cafe_table): View
    {
        $table = null;
        $candidates = null;
        $observedAt = CarbonImmutable::now(config('app.timezone'));
        try {
            $table = app(TableStatusReadService::class)->query($observedAt)->forFloorPlan([])->with('area:id,name,status')
                ->findOrFail($cafe_table);
            abort_unless(auth('web')->user()?->status === 'active', 403);
            $candidates = app(TableOrderContext::class)->candidates($table)->limit(2)->get(['id', 'order_code']);
        } catch (QueryException $exception) {
            Log::warning('Table detail query failed.', ['sql_state' => $exception->errorInfo[0] ?? null]);
        }

        return view('admin.tables.detail', [
            'table' => $table,
            'tableId' => $cafe_table,
            'statuses' => TableStatusReadService::LABELS,
            'candidates' => $candidates,
            'observedAt' => $observedAt,
        ]);
    }
}
