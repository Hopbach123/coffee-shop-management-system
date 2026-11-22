<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\SubmitCustomerOrderRequest;
use App\Models\CustomerOrderRequest;
use App\Services\CustomerCartService;
use App\Services\CustomerOrderService;
use DomainException;
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
        if (is_string($request->query('promotion')) && trim($request->query('promotion')) !== '') {
            $request->session()->put('customer_order_promotion_code', trim($request->query('promotion')));
            $request->session()->forget('customer_order_attempt');
        }
        $promotionCode = $request->session()->get('customer_order_promotion_code');
        try {
            $quote = $service->review($draft, false, is_string($promotionCode) ? $promotionCode : null);
        } catch (DomainException $exception) {
            $request->session()->forget(['customer_order_promotion_code', 'customer_order_attempt']);

            return redirect()->route('customer-orders.create')->withErrors(['promotion_code' => $exception->getMessage()]);
        }
        $attempt = $request->session()->get('customer_order_attempt');
        if (! is_array($attempt) || $attempt['draft'] !== $draft || $attempt['quote_hash'] !== $quote['fingerprint']) {
            $attempt = ['key' => bin2hex(random_bytes(32)), 'token' => bin2hex(random_bytes(32)),
                'draft' => $draft, 'quote_hash' => $quote['fingerprint'], 'promotion_code' => $quote['promotion_code']];
            $request->session()->put('customer_order_attempt', $attempt);
        }

        return view('public.orders.create', ['quote' => $quote, 'submissionKey' => $attempt['key'],
            'savedPromotionCodes' => array_values(array_filter((array) $request->session()->get('public_saved_promotion_codes', []), 'is_string'))]);
    }

    public function store(SubmitCustomerOrderRequest $request, CustomerOrderService $service): RedirectResponse
    {
        $attempt = $request->session()->get('customer_order_attempt');
        if (! is_array($attempt) || ! hash_equals($attempt['key'], $request->validated('submission_key'))) {
            throw ValidationException::withMessages(['cart' => 'Phiên đặt món đã thay đổi. Vui lòng kiểm tra lại giỏ hàng.']);
        }
        try {
            $result = $service->submit($request->validated(), $attempt['draft'], $attempt['key'], $attempt['token'], $attempt['quote_hash'], $attempt['promotion_code'] ?? null);
        } catch (QueryException $exception) {
            Log::error('Customer request persistence failed.', ['exception_type' => $exception::class]);

            return back()->withInput()->withErrors(['cart' => 'Chưa gửi được yêu cầu. Giỏ hàng được giữ lại; hãy thử lại cùng phiên.']);
        }
        $request->session()->put('customer_order_access.'.$result->reference, $attempt['token']);
        $request->session()->put('customer_order_last_reference', $result->reference);
        $request->session()->forget(CustomerCartService::SESSION_KEY);
        $request->session()->forget('customer_order_promotion_code');

        return redirect()->route('customer-orders.show', $result->reference)->with('success', 'Đã gửi yêu cầu — chờ quán tiếp nhận.');
    }

    public function applyPromotion(Request $request, CustomerOrderService $service): RedirectResponse
    {
        $code = $request->validate(['promotion_code' => ['required', 'string', 'max:50']])['promotion_code'];
        $draft = $request->session()->get(CustomerCartService::SESSION_KEY, []);
        if (! is_array($draft) || $draft === []) {
            return redirect()->route('cart.index');
        }

        try {
            $quote = $service->review($draft, false, $code);
        } catch (DomainException $exception) {
            return redirect()->route('customer-orders.create')->withErrors(['promotion_code' => $exception->getMessage()]);
        }

        $request->session()->put('customer_order_promotion_code', $quote['promotion_code']);
        $request->session()->forget('customer_order_attempt');

        return redirect()->route('customer-orders.create')->with('success', 'Đã áp dụng mã '.$quote['promotion_code'].'.');
    }

    public function removePromotion(Request $request): RedirectResponse
    {
        $request->session()->forget(['customer_order_promotion_code', 'customer_order_attempt']);

        return redirect()->route('customer-orders.create')->with('success', 'Đã bỏ mã khuyến mãi.');
    }

    public function show(Request $request, string $reference): View
    {
        $record = CustomerOrderRequest::where('reference', $reference)->firstOrFail();
        $token = $request->session()->get('customer_order_access.'.$reference);
        abort_unless(is_string($token) && hash_equals($record->access_token_hash, hash('sha256', $token))
            && $record->access_expires_at->isFuture(), 404);
        $record->load(['items.toppings', 'order.orderItems', 'order.payments']);
        $data = $record->only(['reference', 'status', 'customer_name', 'customer_phone', 'customer_address',
            'note', 'payment_option', 'promotion_code', 'promotion_discount', 'quoted_subtotal', 'delivery_fee', 'quoted_total', 'dispatched_at', 'delivered_at']);
        $data['paid'] = $record->order?->payments->contains('status', 'paid') ?? false;
        $data['order_status'] = $record->order?->status;
        $data['items'] = collect($record->items)->map(fn ($item): array => [
            'name' => $item->product_name, 'variant' => $item->variant_name, 'quantity' => $item->quantity,
            'subtotal' => $item->subtotal, 'note' => $item->note,
            'toppings' => collect($item->toppings)->map(fn ($row): array => ['name' => $row->topping_name, 'quantity' => $row->quantity])->all(),
        ])->all();
        $data['item_statuses'] = collect($record->order?->orderItems)->pluck('status')->countBy()->all();

        return view('public.orders.show', ['tracking' => $data]);
    }
}
