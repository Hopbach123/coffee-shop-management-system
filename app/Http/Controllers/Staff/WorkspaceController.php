<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CafeTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PickupRequest;
use App\Models\Product;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function orders(): View
    {
        return view('staff.orders.index', ['orders' => Order::with(['creator', 'cafeTable', 'orderItems'])->latest('ordered_at')->paginate(15)]);
    }

    public function orderShow(Order $order): View
    {
        $order->load([
            'creator',
            'cafeTable.area',
            'customer',
            'reservation',
            'orderItems.product',
            'orderItems.productVariant',
            'orderItems.orderItemToppings.topping',
            'payments.creator',
            'orderPromotion.promotion',
        ]);

        return view('staff.orders.show', compact('order'));
    }

    public function tables(): View
    {
        return view('staff.tables.index', ['tables' => CafeTable::with('area')->orderBy('table_code')->get()]);
    }

    public function brewing(): View
    {
        $items = \Illuminate\Support\Facades\Schema::hasTable('order_items') && \Illuminate\Support\Facades\Schema::hasTable('orders')
            ? OrderItem::with(['order', 'product', 'productVariant', 'orderItemToppings.topping'])
                ->whereHas('order', function ($query) {
                    $query->whereNotIn('status', ['cancelled', 'completed']);
                })
                ->whereIn('status', ['pending', 'preparing', 'ready'])
                ->latest()
                ->limit(60)
                ->get()
            : collect();

        return view('staff.brewing.index', compact('items'));
    }

    public function reservations(): View
    {
        return view('staff.reservations.index', ['reservations' => Reservation::latest('reservation_start_at')->paginate(15)]);
    }

    public function pickups(): View
    {
        return view('staff.pickup-requests.index', ['requests' => PickupRequest::latest()->paginate(15)]);
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
