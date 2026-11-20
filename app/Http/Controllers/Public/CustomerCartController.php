<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCustomerCartItemRequest;
use App\Http\Requests\Cart\UpdateCustomerCartItemRequest;
use App\Services\CustomerCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerCartController extends Controller
{
    public function index(Request $request, CustomerCartService $cart): View
    {
        return view('public.cart.index', ['cart' => $cart->quote($this->draft($request))]);
    }

    public function store(StoreCustomerCartItemRequest $request, CustomerCartService $cart): RedirectResponse
    {
        $draft = $cart->add($this->draft($request), $request->validated());
        $request->session()->put(CustomerCartService::SESSION_KEY, $draft);
        $request->session()->forget('customer_order_attempt');

        return redirect()->route('cart.index')->with('success', 'Đã thêm món vào giỏ hàng.');
    }

    public function update(UpdateCustomerCartItemRequest $request, string $line, CustomerCartService $cart): RedirectResponse
    {
        $draft = $this->draft($request);
        abort_unless(isset($draft[$line]), 404);
        $draft = $cart->updateQuantity($draft, $line, $request->validated('quantity'));
        $request->session()->put(CustomerCartService::SESSION_KEY, $draft);
        $request->session()->forget('customer_order_attempt');

        return redirect()->route('cart.index')->with('success', 'Đã cập nhật số lượng món.');
    }

    public function destroy(Request $request, string $line): RedirectResponse
    {
        $draft = $this->draft($request);
        abort_unless(isset($draft[$line]), 404);
        unset($draft[$line]);
        $request->session()->put(CustomerCartService::SESSION_KEY, $draft);
        $request->session()->forget('customer_order_attempt');

        return redirect()->route('cart.index')->with('success', 'Đã xóa món khỏi giỏ hàng.');
    }

    private function draft(Request $request): array
    {
        $draft = $request->session()->get(CustomerCartService::SESSION_KEY, []);

        return is_array($draft) ? $draft : [];
    }
}
