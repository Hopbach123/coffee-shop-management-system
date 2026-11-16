<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnlineRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'accepted', 'rejected', 'cancelled'])],
            'purchase_type' => ['nullable', Rule::in(['online', 'scheduled'])],
        ]);
        $available = Schema::hasTable('online_order_requests') && Schema::hasColumn('orders', 'online_request_id');
        $requests = $available ? DB::table('online_order_requests')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['purchase_type'] ?? null, fn ($query, $type) => $query->where('purchase_type', $type))
            ->select(['id', 'request_code', 'purchase_type', 'contact_name', 'pickup_at', 'status', 'payment_preference', 'payment_timing', 'created_at'])
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(15)->withQueryString() : null;

        return view('staff.online-requests.index', compact('requests', 'available', 'filters'));
    }

    public function show(int $onlineRequest): View
    {
        abort_unless(Schema::hasTable('online_order_requests') && Schema::hasTable('online_order_request_items')
            && Schema::hasTable('online_order_request_item_toppings') && Schema::hasColumn('orders', 'online_request_id'), 503, 'Yêu cầu đặt món tạm thời chưa sẵn sàng.');
        $requestRecord = DB::table('online_order_requests')->select([
            'id', 'request_code', 'purchase_type', 'contact_name', 'contact_phone', 'pickup_at', 'note',
            'status', 'payment_preference', 'payment_timing', 'accepted_at', 'rejection_reason', 'created_at',
        ])->find($onlineRequest);
        abort_if($requestRecord === null, 404);
        $items = DB::table('online_order_request_items')->where('request_id', $onlineRequest)->orderBy('id')->get();
        $toppings = DB::table('online_order_request_item_toppings')->whereIn('request_item_id', $items->pluck('id'))->orderBy('id')->get()->groupBy('request_item_id');
        $order = DB::table('orders')->where('online_request_id', $onlineRequest)->first(['id', 'order_code', 'status', 'total_amount']);

        return view('staff.online-requests.show', compact('requestRecord', 'items', 'toppings', 'order'));
    }
}
