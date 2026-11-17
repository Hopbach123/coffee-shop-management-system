<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCafeTableRequest;
use App\Http\Requests\Admin\UpdateCafeTableRequest;
use App\Http\Requests\Admin\UpdateCafeTableStatusRequest;
use App\Models\Area;
use App\Models\CafeTable;
use App\Services\CafeTableManagementService;
use App\Services\TableStatusReadService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CafeTableController extends Controller
{
    public const STATUS_LABELS = ['available' => 'Bàn trống', 'occupied' => 'Đang có khách', 'maintenance' => 'Đang bảo trì'];

    public function setStatus(UpdateCafeTableStatusRequest $request, CafeTable $cafe_table, CafeTableManagementService $service): RedirectResponse
    {
        try {
            $service->setStatus($cafe_table, $request->validated('expected_status'), $request->validated('status'));
        } catch (DomainException $exception) {
            return redirect()->route('admin.tables.index')->withErrors(['status' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return redirect()->route('admin.tables.index')->withErrors(['status' => 'Không thể đổi trạng thái bàn do lỗi lưu trữ.']);
        }

        return redirect()->route('admin.tables.index')->with('success', 'Đã cập nhật trạng thái bàn.');
    }

    public function edit(CafeTable $cafe_table): View
    {
        $table = $cafe_table;
        $areas = Area::query()->where(fn ($query) => $query->where('status', 'active')->orWhere('id', $table->area_id))
            ->orderBy('name')->get(['id', 'name', 'status']);

        return view('admin.tables.edit', compact('table', 'areas'));
    }

    public function update(UpdateCafeTableRequest $request, CafeTable $cafe_table, CafeTableManagementService $service): RedirectResponse
    {
        $data = $request->safe()->only(['area_id', 'table_code', 'table_name', 'capacity']);
        try {
            $service->update($cafe_table, $data);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['table' => $exception->getMessage()]);
        } catch (UniqueConstraintViolationException $exception) {
            if (! CafeTable::withTrashed()->where('table_code', $data['table_code'])->where('id', '!=', $cafe_table->getKey())->exists()) {
                throw $exception;
            }
            throw ValidationException::withMessages(['table_code' => 'Mã bàn này đã được sử dụng.']);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['table' => 'Không thể cập nhật bàn. Vui lòng thử lại.'])->withInput($data);
        }

        return redirect()->route('admin.tables.index')->with('success', 'Đã cập nhật bàn.');
    }

    public function destroy(CafeTable $cafe_table, CafeTableManagementService $service): RedirectResponse
    {
        try {
            $service->delete($cafe_table);
        } catch (DomainException $exception) {
            return redirect()->route('admin.tables.index')->withErrors(['table' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return redirect()->route('admin.tables.index')->withErrors(['table' => 'Không thể xóa bàn do ràng buộc dữ liệu hoặc lỗi lưu trữ.']);
        }

        return redirect()->route('admin.tables.index')->with('success', 'Đã xóa mềm bàn. Lịch sử được giữ nguyên.');
    }

    public function index(Request $request): View
    {
        if ($request->query('view') === 'floor') {
            return $this->floorPlan($request);
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:20'],
            'area_id' => ['nullable', 'integer', Rule::exists('areas', 'id')],
            'status' => ['nullable', Rule::in(array_keys(self::STATUS_LABELS))],
        ]);
        $tables = CafeTable::query()->with('area:id,name')
            ->when(($filters['search'] ?? '') !== '', fn ($query) => $query->where('table_code', 'like', '%'.$filters['search'].'%'))
            ->when($filters['area_id'] ?? null, fn ($query, $id) => $query->where('area_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(15)->withQueryString();
        $areas = Area::withTrashed()->orderBy('name')->get(['id', 'name', 'deleted_at']);
        $statuses = self::STATUS_LABELS;
        $transitions = CafeTableManagementService::STATUS_TRANSITIONS;

        return view('admin.tables.index', compact('tables', 'areas', 'filters', 'statuses', 'transitions'));
    }

    private function floorPlan(Request $request): View
    {
        $tables = $areas = $summary = null;
        $filters = [];
        $statuses = TableStatusReadService::LABELS;
        $observedAt = CarbonImmutable::now(config('app.timezone'));
        try {
            $filters = $request->validate([
                'search' => ['nullable', 'string', 'max:100'],
                'area_id' => ['nullable', 'integer', Rule::exists('areas', 'id')->whereNull('deleted_at')],
                'status' => ['nullable', Rule::in(array_keys($statuses))],
            ]);
            $query = app(TableStatusReadService::class)->query($observedAt)
                ->forFloorPlan(array_diff_key($filters, ['status' => true]))
                ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('display_status', $value));
            $summary = (clone $query)->select([])->selectRaw("COUNT(*) as total,
                COALESCE(SUM(CASE WHEN display_status = 'available' THEN 1 ELSE 0 END), 0) as available,
                COALESCE(SUM(CASE WHEN display_status = 'occupied' THEN 1 ELSE 0 END), 0) as occupied,
                COALESCE(SUM(CASE WHEN display_status = 'maintenance' THEN 1 ELSE 0 END), 0) as maintenance,
                COALESCE(SUM(CASE WHEN display_status = 'reserved' THEN 1 ELSE 0 END), 0) as reserved,
                COALESCE(SUM(CASE WHEN display_status = 'conflict' THEN 1 ELSE 0 END), 0) as conflict,
                COALESCE(SUM(CASE WHEN display_status = 'unknown' THEN 1 ELSE 0 END), 0) as unknown")->first();
            $tables = $query->with('area:id,name,status')->orderByDesc('created_at')->orderByDesc('id')
                ->paginate(15)->withQueryString();
            $areas = Area::query()->withCount('cafeTables')->orderBy('name')->orderBy('id')
                ->get(['id', 'name', 'status']);
        } catch (QueryException $exception) {
            Log::warning('Table floor plan query failed.', ['sql_state' => $exception->errorInfo[0] ?? null]);
            $tables = $areas = $summary = null;
        }

        return view('admin.tables.floor-plan', compact('tables', 'areas', 'summary', 'filters', 'statuses', 'observedAt'));
    }

    public function create(): View
    {
        $areas = Area::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('admin.tables.create', compact('areas'));
    }

    public function store(StoreCafeTableRequest $request, CafeTableManagementService $service): RedirectResponse
    {
        $data = $request->safe()->only(['area_id', 'table_code', 'table_name', 'capacity']);
        try {
            $service->create($data);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['area_id' => $exception->getMessage()]);
        } catch (UniqueConstraintViolationException $exception) {
            if (! CafeTable::withTrashed()->where('table_code', $data['table_code'])->exists()) {
                throw $exception;
            }
            throw ValidationException::withMessages(['table_code' => 'Mã bàn này đã được sử dụng.']);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['table' => 'Không thể thêm bàn. Vui lòng thử lại hoặc liên hệ quản lý.'])->withInput($data);
        }

        return redirect()->route('admin.tables.index')->with('success', 'Đã thêm bàn.');
    }
}
