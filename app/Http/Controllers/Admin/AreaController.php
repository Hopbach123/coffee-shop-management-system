<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SetAreaStatusRequest;
use App\Http\Requests\Admin\StoreAreaRequest;
use App\Http\Requests\Admin\UpdateAreaRequest;
use App\Models\Area;
use App\Services\AreaManagementService;
use App\Services\TableStatusReadService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function edit(Area $area): View
    {
        return view('admin.areas.edit', compact('area'));
    }

    public function update(UpdateAreaRequest $request, Area $area): RedirectResponse
    {
        try {
            $area->fill($request->safe()->only(['name', 'description']))->save();
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['area' => 'Không thể cập nhật khu vực. Vui lòng thử lại.'])
                ->withInput($request->safe()->only(['name', 'description']));
        }

        return redirect()->route('admin.areas.index')->with('success', 'Đã cập nhật khu vực.');
    }

    public function setStatus(SetAreaStatusRequest $request, Area $area): RedirectResponse
    {
        try {
            $area->status = $request->validated('status');
            $area->save();
        } catch (QueryException $exception) {
            report($exception);

            return redirect()->route('admin.areas.index')->withErrors(['area' => 'Không thể đổi trạng thái khu vực.']);
        }

        return redirect()->route('admin.areas.index')->with('success', $area->status === 'active' ? 'Đã kích hoạt khu vực.' : 'Đã vô hiệu hóa khu vực.');
    }

    public function destroy(Area $area, AreaManagementService $service): RedirectResponse
    {
        try {
            $service->delete($area);
        } catch (DomainException $exception) {
            return redirect()->route('admin.areas.index')->withErrors(['area' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return redirect()->route('admin.areas.index')->withErrors(['area' => 'Không thể xóa khu vực do ràng buộc dữ liệu hoặc lỗi lưu trữ.']);
        }

        return redirect()->route('admin.areas.index')->with('success', 'Đã xóa mềm khu vực.');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? '';
        $areas = $summary = null;
        $observedAt = CarbonImmutable::now(config('app.timezone'));
        $displayLabels = TableStatusReadService::LABELS;
        try {
            $query = app(TableStatusReadService::class)->areas($search, $status, $observedAt);
            $summary = DB::query()->fromSub(clone $query, 'area_counts')->selectRaw(
                'COUNT(*) as areas_count, COALESCE(SUM(tables_count), 0) as tables_count,
                 COALESCE(SUM(available_count), 0) as available_count,
                 COALESCE(SUM(occupied_count), 0) as occupied_count,
                 COALESCE(SUM(maintenance_count), 0) as maintenance_count,
                 COALESCE(SUM(reserved_count), 0) as reserved_count,
                 COALESCE(SUM(conflict_count), 0) as conflict_count,
                 COALESCE(SUM(unknown_count), 0) as unknown_count'
            )->first();
            $areas = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(15)->withQueryString();
        } catch (QueryException $exception) {
            // Do not log SQL or bindings, which may contain the searched name.
            Log::warning('Area overview query failed.', ['sql_state' => $exception->errorInfo[0] ?? null]);
            $areas = $summary = null;
        }

        return view('admin.areas.index', compact('areas', 'summary', 'search', 'status', 'observedAt', 'displayLabels'));
    }

    public function create(): View
    {
        return view('admin.areas.create');
    }

    public function store(StoreAreaRequest $request): RedirectResponse
    {
        try {
            Area::create($request->safe()->only(['name', 'description']));
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['area' => 'Không thể thêm khu vực. Vui lòng thử lại hoặc liên hệ quản lý.'])
                ->withInput($request->safe()->only(['name', 'description']));
        }

        return redirect()->route('admin.areas.index')->with('success', 'Đã thêm khu vực.');
    }
}
