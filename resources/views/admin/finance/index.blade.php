@extends('layouts.internal')
@section('title', 'Báo cáo thu tiền')
@section('content')
<section class="category-page" aria-labelledby="page-title">
    <header class="category-heading">
        <div><h1 id="page-title">Báo cáo thu tiền</h1><p>Khoản thanh toán paid theo thời điểm nhận tiền, giờ Việt Nam.</p></div>
        <x-public.button href="{{ route('admin.dashboard.index') }}" variant="outline">Dashboard</x-public.button>
    </header>
    <form method="GET" action="{{ route('admin.finance.index') }}" class="category-panel category-form">
        <div class="category-field"><label for="from">Từ ngày</label><input id="from" type="date" name="from" value="{{ old('from', $report['from']) }}" required></div>
        <div class="category-field"><label for="to">Đến ngày</label><input id="to" type="date" name="to" value="{{ old('to', $report['to']) }}" required></div>
        <x-public.button type="submit">Xem báo cáo</x-public.button>
    </form>
    @if ($report['available'])
        <section class="category-panel" aria-labelledby="summary-title">
            <h2 id="summary-title">Tổng thu trong kỳ</h2>
            <dl>
                <dt>Tổng thanh toán thành công</dt><dd><x-public.money :amount="$report['total']" /></dd>
                <dt>Tiền mặt</dt><dd><x-public.money :amount="$report['cash']" /></dd>
                <dt>Chuyển khoản</dt><dd><x-public.money :amount="$report['bank_transfer']" /></dd>
                <dt>Số đơn đã thu</dt><dd>{{ $report['paid_orders'] }}</dd>
            </dl>
        </section>
    @else
        <p class="category-notice category-notice--error" role="alert">Có đơn chứa nhiều Payment paid. Cần kiểm tra dữ liệu trước khi xác nhận tổng thu.</p>
    @endif
    @if ($report['undated_paid'] > 0)
        <p role="status">Có {{ $report['undated_paid'] }} khoản paid thiếu thời điểm nhận tiền, chưa thể xếp vào kỳ báo cáo.</p>
    @endif
    <section class="category-panel" aria-labelledby="receipts-title">
        <h2 id="receipts-title">Chứng từ trong kỳ</h2>
        <div class="category-table-wrap"><table class="category-table">
            <caption class="sr-only">Các khoản thu paid trong khoảng ngày đã chọn</caption>
            <thead><tr><th scope="col">Chứng từ</th><th scope="col">Đơn hàng</th><th scope="col">Phương thức</th><th scope="col">Số tiền</th><th scope="col">Nhận tiền lúc</th><th scope="col">Người ghi nhận</th></tr></thead>
            <tbody>
                @forelse ($report['receipts'] as $payment)
                    <tr>
                        <th scope="row" data-label="Chứng từ">{{ $payment->payment_code }}<small>{{ $payment->transaction_code }}</small></th>
                        <td data-label="Đơn hàng"><a href="{{ route('staff.orders.payments.show', $payment->order_id) }}">{{ $payment->order?->order_code ?? $payment->order_id }}</a></td>
                        <td data-label="Phương thức">{{ $payment->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản' }}</td>
                        <td data-label="Số tiền"><x-public.money :amount="$payment->amount" /></td>
                        <td data-label="Nhận tiền lúc">{{ $payment->paid_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</td>
                        <td data-label="Người ghi nhận">{{ $payment->creator?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Chưa có khoản thu trong kỳ.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        {{ $report['receipts']->links() }}
    </section>
</section>
@endsection
