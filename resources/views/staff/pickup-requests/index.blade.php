@extends('layouts.internal')
@section('title','Online & Hẹn lấy (Scheduled / Online)')
@section('content')
<div class="sneat-page-title">
    <div>
        <p>Vận hành · Tiếp nhận yêu cầu mua online / hẹn lấy tại quán</p>
        <h1>Online & Hẹn lấy</h1>
        <span>Khách hẹn giờ lấy món tại quán hoặc đặt trước. Nhân viên kiểm tra và tạo đơn quầy khi khách đến</span>
    </div>
    <span class="sneat-pill is-info">Tổng yêu cầu: {{ $requests->total() }}</span>
</div>

<div class="pickup-grid">
    @forelse($requests as $item)
        <article class="sneat-card pickup-card">
            <div class="sneat-card-heading">
                <strong>#{{ $item->reference }}</strong>
                <span class="sneat-pill {{ $item->status === 'accepted' ? 'is-success' : ($item->status === 'cancelled' ? 'is-danger' : 'is-warning') }}">
                    {{ ['pending'=>'Chờ tiếp nhận','accepted'=>'Đã tiếp nhận','cancelled'=>'Đã từ chối'][$item->status] ?? $item->status }}
                </span>
            </div>
            <h2>{{ $item->customer_name }}</h2>
            <p>☎ <strong>{{ $item->customer_phone }}</strong></p>
            <p style="color: var(--coffee-accent); margin-top: 0.25rem;">
                ⏰ Hẹn lấy lúc: <strong>{{ $item->pickup_at->format('d/m/Y H:i') }}</strong>
            </p>
            <div style="background: var(--coffee-bg); padding: 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--coffee-border); margin: 0.75rem 0;">
                <small style="color: var(--coffee-text-muted); font-weight: 600; display: block; margin-bottom: 0.25rem;">Danh sách món snapshot:</small>
                <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.95rem;">
                    @foreach($item->items as $line)
                        <li>{{ $line['product_name'] ?? 'Món' }} · {{ $line['variant_name'] ?? 'Size' }} × <strong>{{ $line['quantity'] ?? 1 }}</strong></li>
                    @endforeach
                </ul>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <span style="color: var(--coffee-text-muted);">Giá dự kiến:</span>
                <strong style="font-size: 1.1rem; color: var(--coffee-espresso);">{{ number_format((float)$item->subtotal,0,',','.') }} ₫</strong>
            </div>
            @if($item->note)
                <p style="font-size: 0.875rem; color: var(--coffee-text-muted); margin-bottom: 0.75rem;">
                    <em>Ghi chú: {{ $item->note }}</em>
                </p>
            @endif

            @if($item->status === 'pending')
                <div class="pickup-actions">
                    <form method="POST" action="{{ route('staff.pickups.update', $item) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="accepted">
                        <button class="sneat-primary" type="submit">Tiếp nhận yêu cầu</button>
                    </form>
                    <form method="POST" action="{{ route('staff.pickups.update', $item) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button class="sneat-danger" type="submit" onclick="return confirm('Từ chối yêu cầu hẹn lấy này?')">Từ chối</button>
                    </form>
                </div>
            @else
                <div style="font-size: 0.85rem; color: var(--coffee-text-muted); padding-top: 0.5rem; border-top: 1px solid var(--coffee-border);">
                    Đã hoàn tất xử lý trạng thái ({{ $item->status === 'accepted' ? 'Đã tiếp nhận' : 'Đã từ chối' }}).
                </div>
            @endif
        </article>
    @empty
        <div class="sneat-card sneat-empty" style="grid-column: 1 / -1;">
            Chưa có yêu cầu đặt món online hoặc hẹn lấy nào trong hệ thống.
        </div>
    @endforelse
</div>

<div class="sneat-pagination">{{ $requests->links() }}</div>

<p class="sneat-hint" style="margin-top: 1.5rem;">
    <strong>Quy định nghiệp vụ:</strong> Yêu cầu nhận món (scheduled pickup) là thỏa thuận hẹn lấy tại quán, không phải đặt bàn (reservation) và chưa trừ kho hay thu tiền. Khi khách đến nhận, nhân viên tiến hành xác thực và tạo đơn POS chính thức.
</p>
@endsection
