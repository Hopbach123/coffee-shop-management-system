<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        return view('public.cart.index', ['cart' => $this->cartDetails($request)]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate(['variant_id' => ['required', 'integer', 'exists:product_variants,id'], 'quantity' => ['required', 'integer', 'min:1', 'max:20']]);
        $variant = ProductVariant::query()->with('product.category')->findOrFail($data['variant_id']);
        $this->requireAvailable($variant);
        $cart = $request->session()->get('customer_cart', []);
        $id = (string) $variant->id;
        $cart[$id] = min(20, ($cart[$id] ?? 0) + $data['quantity']);
        $request->session()->put('customer_cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Đã thêm món vào giỏ hàng.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['variant_id' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:0', 'max:20']]);
        $cart = $request->session()->get('customer_cart', []);
        $id = (string) $data['variant_id'];
        if ($data['quantity'] === 0) {
            unset($cart[$id]);
        } elseif (array_key_exists($id, $cart)) {
            $cart[$id] = $data['quantity'];
        }
        $request->session()->put('customer_cart', $cart);

        return redirect()->route('cart.index');
    }

    public function checkout(Request $request): View
    {
        $cart = $this->cartDetails($request);
        abort_if($cart['items'] === [], 404);

        return view('public.cart.checkout', compact('cart'));
    }

    public function place(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'regex:/^[0-9+().\s-]{9,20}$/'],
            'pickup_at' => ['required', 'date', 'after:+14 minutes', 'before:+7 days'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $cart = $this->cartDetails($request);
        if ($cart['items'] === []) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
        }
        $requestRecord = DB::transaction(fn () => PickupRequest::create([
            'reference' => 'MDC-'.strtoupper(Str::random(10)),
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'pickup_at' => $data['pickup_at'],
            'note' => $data['note'] ?? null,
            'items' => $cart['items'],
            'subtotal' => number_format($cart['total_cents'] / 100, 2, '.', ''),
        ]));
        $request->session()->forget('customer_cart');
        // The receipt can only be retrieved through this session, avoiding a public lookup of contact data.
        $request->session()->put('last_pickup_request', $requestRecord->id);

        return redirect()->route('cart.confirmation');
    }

    public function confirmation(Request $request): View
    {
        $id = $request->session()->get('last_pickup_request');
        abort_unless($id, 404);

        return view('public.cart.confirmation', ['orderRequest' => PickupRequest::findOrFail($id)]);
    }

    /** Recalculate server-side; never trust browser prices or hidden totals. */
    private function cartDetails(Request $request): array
    {
        $draft = $request->session()->get('customer_cart', []);
        $variants = ProductVariant::query()->with('product.category')->whereIn('id', array_keys($draft))->get()->keyBy('id');
        $items = [];
        $total = 0;
        $validDraft = [];
        foreach ($draft as $id => $qty) {
            $variant = $variants->get($id);
            if (!$variant) {
                continue;
            }
            if ($variant->status !== 'available' || !$variant->product || $variant->product->status !== 'available' || $variant->product->trashed() || !$variant->product->category || $variant->product->category->trashed() || $variant->product->category->status !== 'active') {
                continue;
            }
            $quantity = max(1, min(20, (int) $qty));
            $validDraft[$id] = $quantity;
            $unitCents = (int) round((float) $variant->price * 100);
            $lineCents = $unitCents * $quantity;
            $total += $lineCents;
            $items[] = [
                'variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'quantity' => $quantity,
                'unit_price' => number_format($unitCents / 100, 2, '.', ''),
                'subtotal' => number_format($lineCents / 100, 2, '.', ''),
                'image' => $variant->product->imageUrl(),
            ];
        }

        if ($validDraft !== $draft) {
            $request->session()->put('customer_cart', $validDraft);
        }
        return ['items' => $items, 'total_cents' => $total];
    }

    private function requireAvailable(ProductVariant $variant): void
    {
        if ($variant->status !== 'available' || !$variant->product || $variant->product->status !== 'available' || $variant->product->trashed() || !$variant->product->category || $variant->product->category->trashed() || $variant->product->category->status !== 'active') {
            throw ValidationException::withMessages(['cart' => 'Có món đã ngừng bán. Vui lòng cập nhật giỏ hàng hoặc liên hệ quán.']);
        }
    }
}
