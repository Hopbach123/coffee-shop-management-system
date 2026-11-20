<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\OnlineOrderRequest;
use App\Models\PickupRequest;
use App\Models\ProductVariant;
use App\Models\Topping;
use App\Services\OnlineRequestSubmissionService;
use App\Services\ScheduledPickupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        try {
            return view('public.cart.pickup-index', ['cart' => $this->cartDetails($request)]);
        } catch (ValidationException $exception) {
            // Keep invalid choices visible/removable instead of dropping them or redirect-looping.
            return view('public.cart.pickup-index', [
                'cart' => ['items' => [], 'total_cents' => 0],
                'invalidLines' => $request->session()->get('customer_cart', []),
                'cartIssue' => collect($exception->errors())->flatten()->first(),
            ]);
        }
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'toppings' => ['nullable', 'array'],
            'toppings.*' => ['integer', 'exists:toppings,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $variant = ProductVariant::query()->with('product.category')->findOrFail($data['variant_id']);
        $this->requireAvailable($variant);

        $toppingIds = array_values(array_unique(array_map('intval', (array) ($data['toppings'] ?? []))));
        sort($toppingIds);

        if ($toppingIds !== [] && Topping::whereIn('id', $toppingIds)->where('status', 'available')->count() !== count($toppingIds)) {
            throw ValidationException::withMessages(['toppings' => 'Topping đã ngừng bán hoặc không hợp lệ.']);
        }

        $note = isset($data['note']) && trim($data['note']) !== '' ? trim($data['note']) : null;
        $lineId = $this->lineSignature($variant->id, $toppingIds, $note);

        $cart = $request->session()->get('customer_cart', []);
        $currentQuantity = 0;

        if (isset($cart[$lineId])) {
            $currentQuantity = is_array($cart[$lineId]) ? (int) $cart[$lineId]['quantity'] : (int) $cart[$lineId];
        } elseif (isset($cart[(string) $variant->id]) && empty($toppingIds) && $note === null) {
            $lineId = (string) $variant->id;
            $currentQuantity = is_array($cart[$lineId]) ? (int) $cart[$lineId]['quantity'] : (int) $cart[$lineId];
        }

        $newQuantity = min(20, $currentQuantity + (int) $data['quantity']);

        $cart[$lineId] = [
            'line_id' => $lineId,
            'variant_id' => $variant->id,
            'quantity' => $newQuantity,
            'toppings' => $toppingIds,
            'note' => $note,
        ];

        $request->session()->put('customer_cart', $cart);
        $request->session()->put('checkout_intent', ['key' => bin2hex(random_bytes(32)), 'token' => bin2hex(random_bytes(32))]);
        $request->session()->forget('quoted_cart_lines');

        return redirect()->route('pickup.cart.index')->with('success', 'Đã thêm món vào giỏ hàng.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'line_id' => ['nullable', 'string'],
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $cart = $request->session()->get('customer_cart', []);
        $targetKey = $data['line_id'] ?? ((isset($data['variant_id']) && isset($cart[(string) $data['variant_id']])) ? (string) $data['variant_id'] : null);

        if ($targetKey === null && isset($data['variant_id'])) {
            // Find by variant_id if line_id not provided
            foreach ($cart as $key => $line) {
                $vid = is_array($line) ? (int) $line['variant_id'] : (int) $key;
                if ($vid === (int) $data['variant_id']) {
                    $targetKey = $key;
                    break;
                }
            }
        }

        if ($targetKey !== null && isset($cart[$targetKey])) {
            if ($data['quantity'] === 0) {
                unset($cart[$targetKey]);
            } else {
                if (is_array($cart[$targetKey])) {
                    $cart[$targetKey]['quantity'] = $data['quantity'];
                } else {
                    $cart[$targetKey] = $data['quantity'];
                }
            }
        }

        $request->session()->put('customer_cart', $cart);
        $request->session()->put('checkout_intent', ['key' => bin2hex(random_bytes(32)), 'token' => bin2hex(random_bytes(32))]);
        $request->session()->forget('quoted_cart_lines');

        return redirect()->route('pickup.cart.index');
    }

    public function checkout(Request $request): View
    {
        $cart = $this->cartDetails($request);
        abort_if($cart['items'] === [], 404);

        $request->session()->put('quoted_cart_total', $cart['total_cents']);
        $request->session()->put('quoted_cart_lines', $cart['items']);
        if (! $request->session()->has('checkout_intent')) {
            $request->session()->put('checkout_intent', ['key' => bin2hex(random_bytes(32)), 'token' => bin2hex(random_bytes(32))]);
        }

        return view('public.cart.checkout', compact('cart'));
    }

    public function place(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purchase_type' => ['nullable', 'in:online,scheduled'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'regex:/^[0-9+().\s-]{9,20}$/'],
            'pickup_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'payment_preference' => ['nullable', 'in:cash,bank_transfer'],
            'payment_timing' => ['nullable', 'in:on_receipt,prepaid'],
            'note' => ['nullable', 'string', 'max:500'],
            'confirm_price_change' => ['nullable'],
            'submission_key' => ['nullable', 'string', 'size:64'],
        ]);

        $purchaseType = $data['purchase_type'] ?? ($request->filled('pickup_at') ? 'scheduled' : 'online');
        $paymentPreference = $data['payment_preference'] ?? 'cash';
        $paymentTiming = $data['payment_timing'] ?? 'on_receipt';
        if ($paymentTiming === 'prepaid') {
            $paymentPreference = 'bank_transfer';
        }

        $pickupAt = null;
        if ($purchaseType === 'scheduled') {
            if (! $request->filled('pickup_at')) {
                throw ValidationException::withMessages(['pickup_at' => 'Vui lòng chọn thời gian nhận hàng cho đơn đặt hẹn.']);
            }

            $pickupAt = app(ScheduledPickupService::class)
                ->validateTime($data['pickup_at'])->format('Y-m-d H:i:s');
        }

        $cart = $this->cartDetails($request);
        if ($cart['items'] === [] && ! $request->session()->has('checkout_intent')) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
        }

        if (Schema::hasTable('online_order_requests')) {
            $intent = $request->session()->get('checkout_intent');
            if (! $intent || ($request->filled('submission_key') && ! hash_equals($intent['key'], (string) $request->input('submission_key')))) {
                throw ValidationException::withMessages(['cart' => 'Vui lòng mở lại checkout để xác nhận yêu cầu.']);
            }
            $quote = $request->session()->get('quoted_cart_lines');
            if (! is_array($quote)) {
                throw ValidationException::withMessages(['cart' => 'Báo giá đã hết hiệu lực. Vui lòng mở checkout.']);
            }
            $existing = OnlineOrderRequest::where('submission_key', $intent['key'])->exists();
            if (! $existing && $quote !== $cart['items']) {
                // A fresh checkout displays the new server quote; a checkbox cannot bypass stale pricing.
                throw ValidationException::withMessages(['cart' => 'Giá hoặc lựa chọn đã thay đổi. Vui lòng mở lại checkout và xác nhận từng món.']);
            }
            $record = app(OnlineRequestSubmissionService::class)->submit([
                'request_code' => 'REQ-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
                'purchase_type' => $purchaseType, 'customer_id' => null,
                'contact_name' => $data['customer_name'], 'contact_phone' => $data['customer_phone'],
                'pickup_at' => $pickupAt, 'note' => $data['note'] ?? null, 'status' => 'pending',
                'payment_preference' => $paymentPreference, 'payment_timing' => $paymentTiming,
                'submission_key' => $intent['key'], 'tracking_token_hash' => hash('sha256', $intent['token']),
            ], $quote);
            $request->session()->forget('customer_cart');
            $request->session()->put('last_online_request_id', $record->id);
            $request->session()->put('last_tracking_token', $intent['token']);

            return redirect()->route('orders.track', ['token' => $intent['token']]);
        }

        $trackingToken = bin2hex(random_bytes(32));
        $trackingTokenHash = hash('sha256', $trackingToken);
        $requestCode = 'REQ-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        $submissionKey = hash('sha256', $requestCode.microtime(true).Str::random(16));

        $requestRecord = DB::transaction(function () use ($data, $pickupAt, $cart) {
            $onlineRequest = null;
            // Sync with legacy PickupRequest if table exists
            $legacyReq = null;
            if ($onlineRequest === null && Schema::hasTable('pickup_requests')) {
                try {
                    $legacyReq = PickupRequest::create([
                        'reference' => 'MDC-'.strtoupper(Str::random(10)),
                        'customer_name' => $data['customer_name'],
                        'customer_phone' => $data['customer_phone'],
                        'pickup_at' => $pickupAt ?? now()->addHour()->toDateTimeString(),
                        'note' => $data['note'] ?? null,
                        'items' => $cart['items'],
                        'subtotal' => number_format($cart['total_cents'] / 100, 2, '.', ''),
                    ]);
                } catch (\Throwable $e) {
                    // Ignore legacy sync failures
                }
            }

            return ['online' => $onlineRequest, 'legacy' => $legacyReq];
        });

        $request->session()->forget('customer_cart');
        $request->session()->forget('quoted_cart_total');
        $request->session()->put('last_tracking_token', $trackingToken);
        if (! empty($requestRecord['online'])) {
            $request->session()->put('last_online_request_id', $requestRecord['online']->id);
        }
        if (! empty($requestRecord['legacy'])) {
            $request->session()->put('last_pickup_request', $requestRecord['legacy']->id);
        }

        if ($request->has('purchase_type') && Route::has('orders.track') && $trackingToken) {
            return redirect()->route('orders.track', ['token' => $trackingToken])
                ->with('success', 'Yêu cầu đặt món của bạn đã được gửi thành công! Mã yêu cầu: '.$requestCode);
        }

        return redirect()->route('cart.confirmation');
    }

    public function confirmation(Request $request): View
    {
        if (Schema::hasTable('online_order_requests') && $request->session()->has('last_online_request_id')) {
            $token = $request->session()->get('last_tracking_token');
            if ($token) {
                $hash = hash('sha256', $token);
                $onlineReq = OnlineOrderRequest::where('tracking_token_hash', $hash)->first();
                if ($onlineReq) {
                    return view('public.orders.track', ['orderRequest' => $onlineReq, 'token' => $token]);
                }
            }
        }

        $id = $request->session()->get('last_pickup_request');
        abort_unless($id, 404);

        return view('public.cart.confirmation', ['orderRequest' => PickupRequest::findOrFail($id)]);
    }

    /** Recalculate server-side; never trust browser prices or hidden totals. */
    private function cartDetails(Request $request): array
    {
        $draft = $request->session()->get('customer_cart', []);
        $variantIds = [];
        $toppingIds = [];

        foreach ($draft as $key => $line) {
            if (is_array($line)) {
                $variantIds[] = (int) $line['variant_id'];
                if (! empty($line['toppings'])) {
                    foreach ($line['toppings'] as $tid) {
                        $toppingIds[] = (int) $tid;
                    }
                }
            } else {
                $variantIds[] = (int) $key;
            }
        }

        $variants = ProductVariant::query()->with('product.category')->whereIn('id', array_unique($variantIds))->get()->keyBy('id');
        $toppings = (! empty($toppingIds) && Schema::hasTable('toppings'))
            ? Topping::query()->whereIn('id', array_unique($toppingIds))->get()->keyBy('id')
            : collect();

        $items = [];
        $totalCents = 0;
        $validDraft = [];

        foreach ($draft as $key => $line) {
            $variantId = is_array($line) ? (int) $line['variant_id'] : (int) $key;
            $qty = is_array($line) ? (int) $line['quantity'] : (int) $line;
            $selectedToppingIds = is_array($line) ? (array) ($line['toppings'] ?? []) : [];
            $note = is_array($line) ? ($line['note'] ?? null) : null;

            $variant = $variants->get($variantId);
            if (! $variant) {
                throw ValidationException::withMessages(['cart' => 'Món đã bị xóa. Vui lòng cập nhật giỏ hàng.']);
            }

            if ($variant->status !== 'available' || ! $variant->product || $variant->product->status !== 'available' || $variant->product->trashed() || ! $variant->product->category || $variant->product->category->trashed() || $variant->product->category->status !== 'active') {
                throw ValidationException::withMessages(['cart' => 'Món đã ngừng bán. Vui lòng cập nhật giỏ hàng.']);
            }

            $quantity = max(1, min(20, $qty));
            $unitCents = (int) round((float) $variant->price * 100);

            $lineExtraCents = 0;
            $validToppings = [];
            foreach ($selectedToppingIds as $tid) {
                $topping = $toppings->get($tid);
                if ($topping && $topping->status === 'available' && ! $topping->trashed()) {
                    $topPriceCents = (int) round((float) $topping->price * 100);
                    $lineExtraCents += $topPriceCents;
                    $validToppings[] = [
                        'id' => $topping->id,
                        'name' => $topping->name,
                        'price' => number_format($topPriceCents / 100, 2, '.', ''),
                        'quantity' => 1,
                    ];
                } else {
                    throw ValidationException::withMessages(['cart' => 'Topping đã ngừng bán. Vui lòng cập nhật giỏ hàng.']);
                }
            }

            $lineTotalCents = ($unitCents + $lineExtraCents) * $quantity;
            $totalCents += $lineTotalCents;

            $lineSignature = $this->lineSignature($variant->id, array_column($validToppings, 'id'), $note);
            $validDraft[$lineSignature] = [
                'line_id' => $lineSignature,
                'variant_id' => $variant->id,
                'quantity' => $quantity,
                'toppings' => array_column($validToppings, 'id'),
                'note' => $note,
            ];

            $items[] = [
                'line_id' => $lineSignature,
                'variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'quantity' => $quantity,
                'unit_price' => number_format($unitCents / 100, 2, '.', ''),
                'toppings' => $validToppings,
                'topping_amount' => number_format($lineExtraCents / 100, 2, '.', ''),
                'subtotal' => number_format($lineTotalCents / 100, 2, '.', ''),
                'note' => $note,
                'image' => $variant->product->imageUrl(),
            ];
        }

        if ($validDraft !== $draft) {
            $request->session()->put('customer_cart', $validDraft);
        }

        return ['items' => $items, 'total_cents' => $totalCents];
    }

    private function requireAvailable(ProductVariant $variant): void
    {
        if ($variant->status !== 'available' || ! $variant->product || $variant->product->status !== 'available' || $variant->product->trashed() || ! $variant->product->category || $variant->product->category->trashed() || $variant->product->category->status !== 'active') {
            throw ValidationException::withMessages(['cart' => 'Có món đã ngừng bán. Vui lòng cập nhật giỏ hàng hoặc liên hệ quán.']);
        }
    }

    private function lineSignature(int $variantId, array $toppingIds, ?string $note): string
    {
        sort($toppingIds);

        return md5($variantId.':'.implode(',', $toppingIds).':'.trim((string) $note));
    }
}
