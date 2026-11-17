@extends('layouts.internal')
@section('title', 'Tạm tính đơn tại bàn')
@section('content')
<section class="category-page checkout-summary" aria-labelledby="page-title">
    <header class="category-heading"><div><p class="section-heading__eyebrow">Maison du Café · Đối chiếu đơn</p><h1 id="page-title">Tạm tính đơn tại bàn</h1><p>Chỉ xem dữ liệu đã lưu. Món còn trong draft không được tính vào đây.</p></div>
        <x-public.button :href="route('staff.table-checkout.show', [$tableId, $orderId])" variant="outline">Làm mới</x-public.button>
    </header>
    @if ($unavailable)
        <div class="sneat-card" role="alert"><h2>Chưa thể tải tạm tính</h2><p>Dữ liệu chưa khả dụng. Vui lòng thử làm mới sau.</p></div>
    @else
        <div class="checkout-actions">
            <x-public.button :href="route('staff.orders.show', $order->id)" variant="outline">Quay lại đơn</x-public.button>
            @if (auth('web')->user()->role === 'admin')<x-public.button :href="route('admin.tables.index', ['view' => 'floor', 'area_id' => $table->area_id])" variant="outline">Sơ đồ bàn</x-public.button>@else<x-public.button :href="route('staff.tables.index')" variant="outline">Danh sách bàn</x-public.button>@endif
            @if ($order->orderItems->isNotEmpty())<x-public.button :href="route('staff.table-checkout.print', [$table->id, $order->id])" variant="outline">Xem / in phiếu tạm tính</x-public.button>@endif
        </div>
        <div class="checkout-layout">
            <section class="sneat-card" aria-labelledby="order-summary-title"><h2 id="order-summary-title">Đơn {{ $order->order_code }} (#{{ $order->id }})</h2>
                <p>Bàn {{ $table->table_code }} · {{ $table->area?->name ?? 'Khu vực không còn khả dụng' }} · Trạng thái bàn: {{ $table->status }}</p>
                <p>Trạng thái Order: {{ $order->status }} · Thời điểm tạo: {{ $order->ordered_at?->format('d/m/Y H:i') ?? 'Chưa xác định' }}</p>
                @if ($order->status === 'cancelled')<p role="status">Đơn đã hủy. Số liệu chỉ để tham chiếu, không phải yêu cầu thanh toán.</p>@endif
                @include('staff.orders.components.estimate-items')
            </section>
            <aside class="sneat-card checkout-readiness" aria-labelledby="payment-readiness-title"><h2 id="payment-readiness-title">Thanh toán chưa khả dụng</h2>
                <p>Chưa có luồng xác minh và xử lý thanh toán tại màn hình này.</p>
                <dl><dt>Số tiền đã thanh toán được xác minh</dt><dd>Chưa xác định</dd><dt>Số tiền còn phải trả được xác minh</dt><dd>Chưa xác định</dd></dl>
                <p>Không tạo giao dịch mới hoặc gửi lại giao dịch khi chưa xác minh kết quả trước đó. Tổng giá trị đơn không xác nhận số tiền còn nợ.</p>
            </aside>
        </div>
        <section class="sneat-card" aria-labelledby="payment-records-title"><h2 id="payment-records-title">Bản ghi thanh toán tham chiếu</h2>
            <p>Giá trị dưới đây được đọc nguyên từ dữ liệu đã lưu; không thay thế xác minh thanh toán, không phải biên nhận. Không suy ra trạng thái Order hoặc cộng/trừ số tiền đã trả, hoàn tiền hay còn nợ tại đây.</p>
            @forelse ($payments as $payment)
                <article class="checkout-payment-record"><h3>{{ $payment->payment_code }}</h3>
                    <p>Trạng thái lưu: {{ $payment->status }} · Phương thức lưu: {{ $payment->payment_method }} · Số tiền lưu: {{ $payment->amount }} VNĐ</p>
                    <p>Thời điểm paid_at lưu: {{ $payment->paid_at?->format('d/m/Y H:i') ?? 'Chưa xác định' }}</p>
                </article>
            @empty <p>Chưa có bản ghi thanh toán. Điều này không tự xác nhận số tiền còn phải trả.</p>@endforelse
            <div class="category-pagination">{{ $payments->links() }}</div>
        </section>
    @endif
</section>
@endsection
