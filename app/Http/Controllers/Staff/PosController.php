<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CafeTable;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $this->assertActiveStaff($request);
        $search = trim((string) $request->query('q', ''));
        $products = Schema::hasTable('products') && Schema::hasTable('product_variants')
            ? Product::with(['category', 'productVariants' => fn ($query) => $query->where('status', 'available')])->where('status', 'available')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderBy('name')->get() : collect();
        $draft = $request->session()->get('pos_cart', []);
        $variants = Schema::hasTable('product_variants') ? ProductVariant::with('product')->whereIn('id', array_keys($draft))->get()->keyBy('id') : collect();
        $lines = [];
        foreach ($draft as $id => $quantity) {
            $variant = $variants->get($id);
            if (!$variant || $variant->status !== 'available' || !$variant->product || $variant->product->status !== 'available') continue;
            $qty = max(1, min(20, (int) $quantity));
            $lines[] = compact('variant', 'qty');
        }
        return view('staff.home', ['products' => $products, 'lines' => $lines, 'search' => $search, 'tables' => Schema::hasTable('cafe_tables') ? CafeTable::where('status', 'available')->orderBy('table_code')->get() : collect()]);
    }

    public function add(Request $request): RedirectResponse
    {
        $this->assertActiveStaff($request);
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
        $this->assertActiveStaff($request);
        $data = $request->validate(['variant_id' => ['required', 'integer']]);
        $cart = $request->session()->get('pos_cart', []);
        unset($cart[(string) $data['variant_id']]);
        $request->session()->put('pos_cart', $cart);
        return redirect()->route('staff.home');
    }

    public function checkout(Request $request, OrderService $service): RedirectResponse
    {
        $this->assertActiveStaff($request);
        $data = $request->validate([
            'order_type' => ['required', 'in:dine_in,takeaway'],
            'table_id' => ['nullable', 'integer', 'exists:cafe_tables,id', 'required_if:order_type,dine_in'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $draft = $request->session()->get('pos_cart', []);
        if (!$draft) throw ValidationException::withMessages(['cart' => 'Chưa có món trong đơn.']);

        $items = collect($draft)
            ->map(fn ($quantity, $variantId) => ['variant_id' => $variantId, 'quantity' => $quantity])
            ->values()
            ->all();

        try {
            $order = $service->createWithItems($data, $items, $request->user('web'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        }

        $request->session()->forget('pos_cart');
        return redirect()->route('staff.orders.show', $order)->with('success', 'Đã tạo đơn '.$order->order_code.'. Món đang chờ pha chế.');
    }

    private function assertActiveStaff(Request $request): void
    {
        abort_unless($request->user('web')?->canOperateSales(), 403);
    }
}
