<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\OnlineOrderRequest;
use Illuminate\Http\Response;

class OrderTrackingController extends Controller
{
    public function show(string $token): Response
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/', $token), 404);
        $hash = hash('sha256', $token);
        $orderRequest = OnlineOrderRequest::query()
            ->with([
                'items.toppings',
                'order.orderItems.orderItemToppings',
                'order.payments',
            ])
            ->where('tracking_token_hash', $hash)
            ->first();

        abort_if($orderRequest === null, 404, 'Không tìm thấy thông tin yêu cầu với mã tra cứu này.');

        return response()->view('public.orders.track', compact('orderRequest', 'token'))
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
