@extends('layouts.internal')

@section('title', 'Thanh toán đơn hàng')

@section('content')
    <section class="category-page order-detail" aria-labelledby="page-title">
        <header class="order-detail__header">
            <div>
                <p class="section-heading__eyebrow">Thanh toán và lịch sử</p>
                <h1 id="page-title">{{ $order->order_code }}</h1>
                <p>Trạng thái đơn: <strong>{{ $order->status }}</strong></p>
            </div>
            <x-public.button href="{{ route('staff.orders.show', $order) }}" variant="outline">Chi tiết đơn</x-public.button>
        </header>

        <section class="category-panel" aria-labelledby="payment-items-title">
            <h2 id="payment-items-title">Các món trong đơn</h2>
            <div class="category-table-wrap"><table class="category-table">
                <caption class="sr-only">Món và giá đã ghi nhận khi bán, đơn vị VND</caption>
                <thead><tr><th scope="col">Món / Topping</th><th scope="col">Số lượng</th><th scope="col">Đơn giá (VND)</th><th scope="col">Thành tiền (VND)</th><th scope="col">Trạng thái</th></tr></thead>
                <tbody>
                    @forelse ($order->orderItems as $item)
                        <tr><th scope="row" data-label="Món">{{ $item->product_name }} · {{ $item->variant_name }}</th><td data-label="Số lượng">{{ $item->quantity }}</td><td data-label="Đơn giá (VND)">{{ $item->unit_price }}</td><td data-label="Thành tiền (VND)">{{ $item->subtotal }}</td><td data-label="Trạng thái">{{ $item->status }}</td></tr>
                        @foreach ($item->orderItemToppings as $topping)
                            <tr><td data-label="Topping">+ {{ $topping->topping_name }}</td><td data-label="Số lượng">{{ $topping->quantity }}</td><td data-label="Đơn giá (VND)">{{ $topping->price }}</td><td data-label="Ghi chú" colspan="2">Đã tính trong giá trị món</td></tr>
                        @endforeach
                    @empty
                        <tr><td colspan="5">Đơn chưa có món.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            <p>Tạm tính: {{ $order->subtotal }} VND · Giảm giá: {{ $order->discount_amount }} VND · Thuế: {{ $order->tax_amount }} VND · Phí giao: {{ $order->delivery_fee ?? '0.00' }} VND</p>
            <p><strong>Tổng thanh toán: {{ $order->total_amount }} VND</strong></p>
        </section>

        <section class="category-panel" aria-labelledby="promotion-title">
            <h2 id="promotion-title">Khuyến mãi</h2>
            @if ($order->orderPromotion)
                <p>{{ $order->orderPromotion->promotion_code }} · Giảm {{ $order->orderPromotion->discount_amount }} VND</p>
            @elseif ($canApplyPromotion)
                <form method="POST" action="{{ route('staff.orders.promotion.store', $order) }}" class="category-form">
                    @csrf
                    <div class="category-field">
                        <label for="promotion-code">Mã khuyến mãi</label>
                        <input id="promotion-code" name="code" maxlength="50" value="{{ old('code') }}" required>
                        @error('code')<p role="alert">{{ $message }}</p>@enderror
                    </div>
                    <x-public.button type="submit" variant="outline">Áp dụng mã</x-public.button>
                </form>
            @else
                <p>Giá trị đơn đã chốt.</p>
            @endif
        </section>

        <section class="category-panel" aria-labelledby="record-payment-title">
            <h2 id="record-payment-title">Ghi nhận tiền đã nhận</h2>
            @if ($canPay)
                <p>Chỉ xác nhận khi đã nhận đủ tiền thực tế. Với chuyển khoản, hãy kiểm tra tài khoản ngân hàng trước khi tiếp tục.</p>
                <form method="POST" action="{{ route('staff.orders.payments.store', $order) }}" class="category-form">
                    @csrf
                    <div class="category-field">
                        <label for="payment_method">Phương thức</label>
                        <select id="payment_method" name="payment_method" required>
                            @unless($isWebTransfer)<option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Tiền mặt</option>@endunless
                            <option value="bank_transfer" @selected($isWebTransfer || old('payment_method') === 'bank_transfer')>Chuyển khoản</option>
                        </select>
                    </div>
                    <div class="category-field">
                        <label for="transaction_code">Mã giao dịch chuyển khoản (không bắt buộc)</label>
                        <input id="transaction_code" name="transaction_code" type="text" maxlength="100" value="{{ old('transaction_code') }}">
                    </div>
                    @unless($isWebTransfer)
                        <div class="category-field">
                            <label for="received_amount">Tiền khách đưa khi trả tiền mặt (VND)</label>
                            <input id="received_amount" name="received_amount" inputmode="decimal" value="{{ old('received_amount') }}" placeholder="Để trống khi nhận đúng tổng tiền">
                            @error('received_amount')<p role="alert">{{ $message }}</p>@enderror
                        </div>
                    @endunless
                    <p><label for="received_confirmation"><input id="received_confirmation" name="received_confirmation" type="checkbox" value="1" required> Tôi xác nhận đã nhận đủ tiền cho đơn hàng này.</label></p>
                    <x-public.button type="submit">Xác nhận thanh toán</x-public.button>
                </form>
            @else
                <p role="status">Đơn chưa đủ điều kiện ghi nhận thêm tiền hoặc đã thanh toán.</p>
            @endif
            @if($customerRequest)<p><a href="{{ route('staff.customer-orders.show', $customerRequest) }}">Mở yêu cầu web để theo dõi giao món{{ $customerRequest->payment_option === 'cod' ? ' và xác nhận thu COD khi khách nhận' : '' }}</a></p>@endif
        </section>

        <section class="category-panel" aria-labelledby="payment-history-title">
            <h2 id="payment-history-title">Lịch sử thanh toán</h2>
            @foreach ($order->payments->where('status', 'paid') as $receipt)
                @if ($receipt->payment_method === 'cash')
                    <p>{{ $receipt->payment_code }} · Khách đưa {{ $receipt->received_amount ?? $receipt->amount }} VND · Tiền thừa {{ $receipt->change_amount ?? '0.00' }} VND</p>
                @endif
            @endforeach
            <div class="category-table-wrap"><table class="category-table">
                <caption class="sr-only">Lịch sử các lần thanh toán của đơn</caption>
                <thead><tr><th scope="col">Mã thanh toán</th><th scope="col">Phương thức</th><th scope="col">Số tiền (VND)</th><th scope="col">Trạng thái</th><th scope="col">Thời gian nhận tiền</th><th scope="col">Người ghi nhận</th></tr></thead>
                <tbody>@forelse ($order->payments as $payment)
                    <tr><th scope="row" data-label="Mã thanh toán">{{ $payment->payment_code }} @if ($payment->transaction_code)<small>{{ $payment->transaction_code }}</small>@endif</th><td data-label="Phương thức">{{ $payment->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản' }}</td><td data-label="Số tiền (VND)">{{ $payment->amount }}</td><td data-label="Trạng thái">{{ $payment->status }}</td><td data-label="Thời gian">{{ $payment->paid_at?->format('d/m/Y H:i') ?? 'Chưa nhận tiền' }}</td><td data-label="Người ghi nhận">{{ $payment->creator?->name ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="6">Chưa có thanh toán.</td></tr>
                @endforelse</tbody>
            </table></div>
        </section>
    </section>
@endsection
