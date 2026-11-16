<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\CheckoutPosRequest;
use App\Http\Requests\Pos\StorePosCartItemRequest;
use App\Models\CafeTable;
use App\Models\Product;
use App\Models\Topping;
use App\Services\CustomerCartService;
use App\Services\PosCheckoutService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request, CustomerCartService $cart, PosCheckoutService $checkout): View
    {
        $this->assertActiveStaff($request);
        $search = trim((string) $request->query('q', ''));
        $products = Schema::hasTable('products') && Schema::hasTable('product_variants')
            ? Product::with(['category', 'productVariants' => fn ($query) => $query->where('status', 'available')])->where('status', 'available')
                ->whereHas('category', fn ($query) => $query->where('status', 'active'))
                ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
                ->orderBy('name')->get() : collect();
        $draft = $this->draft($request);
        $quote = $checkout->review($draft);
        $attempt = $request->session()->get('pos_checkout_attempt');
        if ($draft !== [] && (! is_array($attempt) || $attempt['draft'] !== $draft || $attempt['quote_hash'] !== $quote['fingerprint'])) {
            $attempt = ['key' => bin2hex(random_bytes(32)), 'draft' => $draft, 'quote_hash' => $quote['fingerprint']];
            $request->session()->put('pos_checkout_attempt', $attempt);
        }
        $priceLabels = [];
        foreach ($products as $product) {
            foreach ($product->productVariants as $variant) {
                $priceLabels[$variant->id] = $cart->formatPrice($variant->getRawOriginal('price'));
            }
        }
        $toppings = Schema::hasTable('toppings') ? Topping::where('status', 'available')->orderBy('name')->get() : collect();
        $toppingPriceLabels = $toppings->mapWithKeys(fn (Topping $topping): array => [$topping->id => $cart->formatPrice($topping->getRawOriginal('price'))])->all();

        return view('staff.home', ['products' => $products, 'quote' => $quote, 'attempt' => $attempt,
            'priceLabels' => $priceLabels, 'toppings' => $toppings, 'toppingPriceLabels' => $toppingPriceLabels, 'search' => $search,
            'tables' => Schema::hasTable('cafe_tables') ? CafeTable::where('status', 'available')->orderBy('table_code')->get() : collect()]);
    }

    public function add(StorePosCartItemRequest $request, CustomerCartService $service): RedirectResponse
    {
        $this->assertActiveStaff($request);
        $cart = $service->add($this->draft($request), $request->validated());
        $request->session()->put('pos_cart', $cart);
        $request->session()->forget('pos_checkout_attempt');

        return redirect()->route('staff.home');
    }

    public function remove(Request $request): RedirectResponse
    {
        $this->assertActiveStaff($request);
        $data = $request->validate(['line_id' => ['required', 'string', 'max:100']]);
        $cart = $this->draft($request);
        unset($cart[$data['line_id']]);
        $request->session()->put('pos_cart', $cart);
        $request->session()->forget('pos_checkout_attempt');

        return redirect()->route('staff.home');
    }

    public function checkout(CheckoutPosRequest $request, PosCheckoutService $service): RedirectResponse
    {
        $this->assertActiveStaff($request);
        $data = $request->validated();
        $attempt = $request->session()->get('pos_checkout_attempt');
        if (! is_array($attempt) || ! hash_equals($attempt['key'], $data['checkout_key'])) {
            throw ValidationException::withMessages(['cart' => 'Phiên kiểm tra đơn đã thay đổi. Vui lòng kiểm tra lại giỏ hàng.']);
        }
        if ($data['order_type'] === 'takeaway') {
            $data['table_id'] = null;
        }

        try {
            $order = $service->checkout($data, $attempt['draft'], $attempt['key'], $attempt['quote_hash'], $request->user('web'));
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $errors['payment'][] = 'Chưa hoàn tất ghi nhận. Nếu đã nhận tiền, kiểm tra giao dịch trước khi thử lại; không thu tiền lần hai.';
            throw ValidationException::withMessages($errors);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage(),
                'payment' => 'Chưa hoàn tất ghi nhận. Nếu đã nhận tiền, kiểm tra giao dịch trước khi thử lại; không thu tiền lần hai.']);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withInput()->withErrors(['payment' => 'Chưa hoàn tất ghi nhận. Nếu đã nhận tiền, hãy kiểm tra giao dịch trước khi thử lại cùng phiên này; không thu tiền lần hai.']);
        }

        $request->session()->forget('pos_cart');

        return redirect()->route('staff.orders.show', $order)->with('success', 'Đã ghi nhận đủ tiền cho đơn '.$order->order_code.'. Món đã chuyển sang chuẩn bị.');
    }

    private function draft(Request $request): array
    {
        $draft = $request->session()->get('pos_cart', []);
        // Preserve draft sessions created before the prepaid POS release.
        foreach ($draft as $id => $line) {
            if (! is_array($line)) {
                $draft[$id] = ['variant_id' => $id, 'quantity' => $line, 'toppings' => [], 'note' => null];
            }
        }

        return $draft;
    }

    private function assertActiveStaff(Request $request): void
    {
        abort_unless($request->user('web')?->status === 'active'
            && in_array($request->user('web')?->role, ['admin', 'staff'], true), 403);
    }
}
