@extends('layouts.internal')
@section('title', 'Đơn giao từ web')
@section('content')
<div class="sneat-page-title"><div><p>Khách đặt trên website</p><h1>Đơn giao từ web</h1><span>Tiếp nhận yêu cầu, xác nhận tiền và theo dõi giao món.</span></div></div>
<div class="sneat-card"><div class="sneat-responsive"><table class="sneat-datatable"><thead><tr><th>Mã yêu cầu</th><th>Khách / liên hệ</th><th>Thanh toán dự kiến</th><th>Tổng gồm phí giao</th><th>Tiếp nhận</th><th>Thao tác</th></tr></thead><tbody>
@forelse($requests as $record)<tr><td>{{ $record->reference }}</td><td>{{ $record->customer_name }}<br>{{ $record->customer_phone }}</td><td>{{ $record->payment_option === 'cod' ? 'COD' : 'Chuyển khoản trước' }}</td><td>{{ $record->quoted_total }} VND</td><td>{{ $record->status }}</td><td><a href="{{ route('staff.customer-orders.show', $record) }}">Xem yêu cầu</a></td></tr>@empty<tr><td colspan="6">Chưa có yêu cầu từ website.</td></tr>@endforelse
</tbody></table></div>{{ $requests->links() }}</div>
@endsection
