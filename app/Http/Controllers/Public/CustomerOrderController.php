<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\SubmitCustomerOrderRequest;
use App\Models\CustomerOrderRequest;
use App\Services\CustomerCartService;
use App\Services\CustomerOrderService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerOrderController extends Controller
{
    public function create(Request $request, CustomerOrderService $service): View|RedirectResponse
    {
        $draft = $request->session()->get(CustomerCartService::SESSION_KEY, []);
        if ($draft === []) {
            return redirect()->route('cart.index');
        }
        $quote = $service->review($draft);
        $attempt = $request->session()->get('customer_order_attempt');
        if (! is_array($attempt) || $attempt['draft'] !== $draft || $attempt['quote_hash'] !== $quote['fingerprint']) {
            $attempt = ['key' => bin2hex(random_bytes(32)), 'token' => bin2hex(random_bytes(32)),
                'draft' => $draft, 'quote_hash' => $quote['fingerprint']];
            $request->session()->put('customer_order_attempt', $attempt);
        }

        return view('public.orders.create', ['quote' => $quote, 'submissionKey' => $attempt['key']]);
    }

    public function store(SubmitCustomerOrderRequest $request, CustomerOrderService $service): RedirectResponse
    {
        $attempt = $request->session()->get('customer_order_attempt');
        if (! is_array($attempt) || ! hash_equals($attempt['key'], $request->validated('submission_key'))) {
            throw ValidationException::withMessages(['cart' => 'Phiên đặt món đã thay đổi. Vui lòng kiểm tra lại giỏ hàng.']);
        }
        try {
            $result = $service->submit($request->validated(), $attempt['draft'], $attempt['key'], $attempt['token'], $attempt['quote_hash']);
        } catch (QueryException $exception) {
            Log::error('Customer request persistence failed.', ['exception_type' => $exception::class]);

            return back()->withInput()->withErrors(['cart' => 'Chưa gửi được yêu cầu. Giỏ hàng được giữ lại; hãy thử lại cùng phiên.']);
        }
        $request->session()->put('customer_order_access.'.$result->reference, $attempt['token']);
        $request->session()->put('customer_order_last_reference', $result->reference);
        $request->session()->forget(CustomerCartService::SESSION_KEY);

        return redirect()->route('customer-orders.show', $result->reference)->with('success', 'Đã gửi yêu cầu — chờ quán tiếp nhận.');
    }

    public function show(Request $request, string $reference): View
    {
        $record = CustomerOrderRequest::where('reference', $reference)->firstOrFail();
        $token = $request->session()->get('customer_order_access.'.$reference);
        abort_unless(is_string($token) && hash_equals($record->access_token_hash, hash('sha256', $token))
            && $record->access_expires_at->isFuture(), 404);
        $record->load(['items.toppings', 'order.orderItems', 'order.payments']);
        $data = $record->only(['reference', 'status', 'customer_name', 'customer_phone', 'customer_address',
            'note', 'payment_option', 'quoted_subtotal', 'delivery_fee', 'quoted_total', 'dispatched_at', 'delivered_at']);
        $data['paid'] = $record->order?->payments->contains('status', 'paid') ?? false;
        $data['order_status'] = $record->order?->status;
        $data['items'] = $record->items->map(fn ($item): array => [
            'name' => $item->product_name, 'variant' => $item->variant_name, 'quantity' => $item->quantity,
            'subtotal' => $item->subtotal, 'note' => $item->note,
            'toppings' => $item->toppings->map(fn ($row): array => ['name' => $row->topping_name, 'quantity' => $row->quantity])->all(),
        ])->all();
        $data['item_statuses'] = $record->order?->orderItems->pluck('status')->countBy()->all() ?? [];

        return view('public.orders.show', ['tracking' => $data]);
    }
}
