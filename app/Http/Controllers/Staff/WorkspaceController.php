<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CafeTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PickupRequest;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function orders(): View
    {
        $orders = Schema::hasTable('orders')
            ? Order::with(['creator', 'cafeTable', 'orderItems'])->latest('ordered_at')->paginate(15)
            : new LengthAwarePaginator([], 0, 15);

        return view('staff.orders.index', compact('orders'));
    }

    public function tables(): View
    {
        $tables = Schema::hasTable('cafe_tables')
            ? CafeTable::with('area')->orderBy('table_code')->get()
            : collect();

        return view('staff.tables.index', compact('tables'));
    }

    public function brewing(): View
    {
        $items = Schema::hasTable('order_items')
            ? OrderItem::with(['order', 'product'])->whereIn('status', ['pending', 'preparing', 'ready'])->latest()->limit(60)->get()
            : collect();

        return view('staff.brewing.index', compact('items'));
    }

    public function reservations(): View
    {
        $reservations = Schema::hasTable('reservations')
            ? Reservation::latest('reservation_start_at')->paginate(15)
            : new LengthAwarePaginator([], 0, 15);

        return view('staff.reservations.index', compact('reservations'));
    }

    public function pickups(): View
    {
        $requests = Schema::hasTable('pickup_requests')
            ? PickupRequest::latest()->paginate(15)
            : new LengthAwarePaginator([], 0, 15);

        return view('staff.pickup-requests.index', compact('requests'));
    }

    public function updatePickup(Request $request, PickupRequest $pickupRequest): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:accepted,cancelled']]);
        if ($pickupRequest->status !== 'pending') {
            return back()->with('error', 'Yêu cầu này đã được xử lý.');
        }
        $pickupRequest->update(['status' => $data['status']]);

        return back()->with('success', 'Đã cập nhật yêu cầu nhận hàng.');
    }
}
