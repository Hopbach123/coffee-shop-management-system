<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelReservationRequest;
use App\Http\Requests\Admin\StoreReservationRequest;
use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\Reservation;
use App\Services\ReservationApprovalService;
use App\Services\ReservationCancellationService;
use App\Services\ReservationCreationService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public const STATUS_LABELS = [
        'pending' => 'Chờ duyệt',
        'confirmed' => 'Đã xác nhận',
        'arrived' => 'Đã đến',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
        'no_show' => 'Không đến',
    ];

    private const RELATIONS = ['creator:id,name', 'customer:id,name,phone', 'cafeTable:id,area_id,table_code,table_name,capacity', 'cafeTable.area:id,name'];

    public function cancel(CancelReservationRequest $request, Reservation $reservation, ReservationCancellationService $service): RedirectResponse
    {
        try {
            $service->cancel($reservation, $request->validated('expected_status'));
        } catch (QueryException $exception) {
            return redirect()->route('admin.reservations.show', $reservation)
                ->withErrors(['cancellation' => 'Không thể hủy đặt bàn do lỗi lưu trữ. Vui lòng thử lại.']);
        }

        return redirect()->route('admin.reservations.show', $reservation)->with('success', 'Đã hủy yêu cầu đặt bàn.');
    }

    public function approve(Reservation $reservation, ReservationApprovalService $service): RedirectResponse
    {
        try {
            $service->approve($reservation);
        } catch (QueryException $exception) {
            return redirect()->route('admin.reservations.show', $reservation)
                ->withErrors(['approval' => 'Không thể duyệt đặt bàn do lỗi lưu trữ. Vui lòng thử lại.']);
        }

        return redirect()->route('admin.reservations.show', $reservation)->with('success', 'Đã xác nhận yêu cầu đặt bàn.');
    }

    public function create(): View
    {
        abort_unless(app()->environment(['local', 'testing']), 403);
        $customers = Customer::query()->where('status', 'active')->orderBy('name')->orderBy('id')->get(['id', 'name', 'phone']);
        $tables = CafeTable::query()->whereIn('status', ['available', 'occupied'])
            ->whereHas('area', fn ($query) => $query->where('status', 'active')->whereNull('deleted_at'))
            ->with('area:id,name')->orderBy('table_code')->get(['id', 'area_id', 'table_code', 'capacity', 'status']);

        return view('admin.reservations.create', compact('customers', 'tables'));
    }

    public function store(StoreReservationRequest $request, ReservationCreationService $service): RedirectResponse
    {
        try {
            $reservation = $service->create($request->validated(), $request->user('web'));
        } catch (QueryException $exception) {
            // Do not report SQL bindings containing customer contact data.
            return back()->withErrors(['reservation' => 'Không thể lưu yêu cầu đặt bàn. Vui lòng thử lại.'])->withInput($request->validated());
        }

        return redirect()->route('admin.reservations.show', $reservation)
            ->with('success', 'Đã tiếp nhận yêu cầu đặt bàn, đang chờ xác nhận');
    }

    public function index(Request $request): View
    {
        if (is_string($request->input('search'))) {
            $request->merge(['search' => trim($request->input('search'))]);
        }
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUS_LABELS))],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
        ]);
        $query = Reservation::query()->with(self::RELATIONS);
        $search = $filters['search'] ?? '';
        if ($search !== null && $search !== '') {
            $query->where(fn ($query) => $query->where('customer_name', 'like', '%'.$search.'%')->orWhere('customer_phone', 'like', '%'.$search.'%'));
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($date = $filters['date'] ?? null) {
            $start = CarbonImmutable::parse($date, config('app.timezone'))->startOfDay();
            $query->where('reservation_start_at', '>=', $start)->where('reservation_start_at', '<', $start->addDay());
        }
        if ($area = $filters['area_id'] ?? null) {
            $query->whereHas('cafeTable', fn ($query) => $query->where('area_id', $area));
        }
        $reservations = $query->orderByDesc('reservation_start_at')->orderByDesc('id')->paginate(15)->withQueryString();
        $areas = Area::withTrashed()->orderBy('name')->get(['id', 'name', 'deleted_at']);
        $statuses = self::STATUS_LABELS;

        return view('admin.reservations.index', compact('reservations', 'areas', 'filters', 'statuses'));
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load(self::RELATIONS);
        $statuses = self::STATUS_LABELS;

        return view('admin.reservations.show', compact('reservation', 'statuses'));
    }
}
