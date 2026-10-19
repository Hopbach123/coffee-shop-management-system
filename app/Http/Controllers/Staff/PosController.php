<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CafeTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $products = Schema::hasTable('products') && Schema::hasTable('product_variants')
            ? Product::with(['category', 'productVariants' => fn ($query) => $query->where('status', 'available')])->where('status', 'available')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderBy('name')->get() : collect();
        $draft = $request->session()->get('pos_cart', []);
        $variants = Schema::hasTable('product_variants') ? ProductVariant::with('product')->whereIn('id', array_keys($draft))->get()->keyBy('id') : collect();
        $lines = [];
        $total = 0;
        foreach ($draft as $id => $quantity) {
            $variant = $variants->get($id);
            if (!$variant || $variant->status !== 'available' || !$variant->product || $variant->product->status !== 'available') continue;
            $qty = max(1, min(20, (int) $quantity));
            $priceCents = (int) round((float) $variant->price * 100);
            $total += $qty * $priceCents;
            $lines[] = compact('variant', 'qty', 'priceCents');
        }
        return view('staff.home', ['products' => $products, 'lines' => $lines, 'totalCents' => $total, 'search' => $search, 'tables' => Schema::hasTable('cafe_tables') ? CafeTable::where('status', 'available')->orderBy('table_code')->get() : collect()]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate(['variant_id' => ['required', 'integer', 'exists:product_variants,id']]);
        $variant = ProductVariant::with('product')->findOrFail($data['variant_id']);
        if ($variant->status !== 'available' || ($variant->product?->status !== 'available' || $variant->product?->trashed())) {
            throw ValidationException::withMessages(['variant_id' => 'Món hiện không có sẵn.']);
        }
        $cart = $request->session()->get('pos_cart', []);
        $id = (string) $variant->id;
        $cart[$id] = min(20, ($cart[$id] ?? 0) + 1);
        $request->session()->put('pos_cart', $cart);
        return redirect()->route('staff.home');
    }

    public function remove(Request $request): RedirectResponse
    {
        $data = $request->validate(['variant_id' => ['required', 'integer']]);
        $cart = $request->session()->get('pos_cart', []);
        unset($cart[(string) $data['variant_id']]);
        $request->session()->put('pos_cart', $cart);
        return redirect()->route('staff.home');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_type' => ['required', 'in:dine_in,takeaway'],
            'table_id' => ['nullable', 'integer', 'exists:cafe_tables,id', 'required_if:order_type,dine_in'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $draft = $request->session()->get('pos_cart', []);
        if (!$draft) throw ValidationException::withMessages(['cart' => 'Chưa có món trong đơn.']);
        $order = DB::transaction(function () use ($draft, $data, $request) {
            if ($data['order_type'] === 'dine_in') {
                // Lock the selected table to prevent duplicate table assignment.
                $table = CafeTable::whereKey($data['table_id'])->lockForUpdate()->firstOrFail();
                if ($table->status !== 'available') throw ValidationException::withMessages(['table_id' => 'Bàn không còn trống.']);
            }
            $variants = ProductVariant::with('product')->whereIn('id', array_keys($draft))->get()->keyBy('id');
            $items = [];
            $subtotalCents = 0;
            foreach ($draft as $id => $quantity) {
                $variant = $variants->get($id);
                if (!$variant || $variant->status !== 'available' || ($variant->product?->status !== 'available' || $variant->product?->trashed())) {
                    throw ValidationException::withMessages(['cart' => 'Một số món không còn có sẵn.']);
                }
                $quantity = max(1, min(20, (int) $quantity));
                $price = (int) round((float) $variant->price * 100);
                $subtotalCents += $price * $quantity;
                $items[] = ['product_id' => $variant->product_id, 'variant_id' => $variant->id, 'product_name' => $variant->product->name, 'variant_name' => $variant->name, 'quantity' => $quantity, 'unit_price' => number_format($price / 100, 2, '.', ''), 'topping_amount' => '0.00', 'subtotal' => number_format($price * $quantity / 100, 2, '.', ''), 'status' => 'pending'];
            }
            $order = new Order();
            $order->order_code = 'POS-'.strtoupper(Str::random(12));
            $order->user_id = $request->user()->id;
            $order->order_type = $data['order_type'];
            $order->table_id = $data['order_type'] === 'dine_in' ? $data['table_id'] : null;
            $order->status = 'pending';
            $order->subtotal = number_format($subtotalCents / 100, 2, '.', '');
            $order->total_amount = $order->subtotal;
            $order->discount_amount = '0.00';
            $order->tax_amount = '0.00';
            $order->ordered_at = now();
            $order->note = $data['note'] ?? null;
            $order->save();
            $order->orderItems()->createMany($items);
            if (isset($table)) $table->forceFill(['status' => 'occupied'])->save();
            return $order;
        });
        $request->session()->forget('pos_cart');
        return redirect()->route('staff.orders.index')->with('success', 'Đã tạo đơn '.$order->order_code.'. Món đang chờ pha chế.');
    }
}
