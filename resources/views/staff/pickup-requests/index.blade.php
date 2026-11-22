@extends('layouts.internal')
@section('title', 'Online & Hẹn lấy')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng"><span>Vận hành</span><span>Online &amp; Hẹn lấy</span></nav>
        <h1 class="cafe-page-title">Online &amp; Hẹn lấy</h1>
        <p class="cafe-page-subtitle">Khách hẹn giờ lấy món tại quán hoặc đặt trước. Nhân viên kiểm tra và tạo đơn quầy khi khách đến.</p>
    </div>
    <span class="cafe-badge cafe-badge--info" style="align-self:center;">Tổng: {{ $requests->total() }} yêu cầu</span>
</div>

@if($requests->isEmpty())
    <div class="cafe-card" style="padding:3rem;text-align:center;color:var(--cafe-text-secondary);">
        <x-internal.icon name="calendar" size="48" style="opacity:.25;margin-bottom:1rem;" />
        <p style="font-weight:600;font-size:1.1rem;margin-bottom:.35rem;">Chưa có yêu cầu nào</p>
        <p style="font-size:.9rem;">Chưa có yêu cầu đặt món online hoặc hẹn lấy nào trong hệ thống.</p>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem;">
        @foreach($requests as $item)
            <article class="cafe-card" style="padding:1.25rem;display:flex;flex-direction:column;gap:.75rem;">
                {{-- Header --}}
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <strong style="font-size:1rem;color:var(--cafe-text-primary);">#{{ $item->reference }}</strong>
                    <span class="cafe-badge {{ $item->status === 'accepted' ? 'cafe-badge--success' : ($item->status === 'cancelled' ? 'cafe-badge--danger' : 'cafe-badge--warning') }}">
                        {{ ['pending'=>'Chờ tiếp nhận','accepted'=>'Đã tiếp nhận','cancelled'=>'Đã từ chối'][$item->status] ?? $item->status }}
                    </span>
                </div>

                {{-- Customer info --}}
                <div>
                    <p style="font-weight:700;font-size:1rem;color:var(--cafe-text-primary);margin-bottom:.2rem;">{{ $item->customer_name }}</p>
                    <p style="font-size:.875rem;color:var(--cafe-text-secondary);">
                        <x-internal.icon name="phone" size="13" style="vertical-align:middle;" /> {{ $item->customer_phone }}
                    </p>
                </div>

                {{-- Pickup time --}}
                <div style="display:flex;align-items:center;gap:.5rem;padding:.6rem .75rem;background:rgba(173,118,76,.08);border-radius:8px;border:1px solid rgba(173,118,76,.2);">
                    <x-internal.icon name="clock" size="16" style="color:var(--cafe-accent);flex-shrink:0;" />
                    <span style="font-size:.875rem;color:var(--cafe-text-primary);">Hẹn lấy lúc: <strong>{{ $item->pickup_at->format('d/m/Y H:i') }}</strong></span>
                </div>

                {{-- Items snapshot --}}
                <div style="background:var(--cafe-surface-alt);border-radius:8px;padding:.75rem;border:1px solid var(--cafe-border);">
                    <p style="font-size:.78rem;font-weight:700;color:var(--cafe-text-secondary);text-transform:uppercase;letter-spacing:.04em;margin-bottom:.5rem;">Danh sách món:</p>
                    <ul style="margin:0;padding-left:1.1rem;font-size:.9rem;color:var(--cafe-text-primary);display:flex;flex-direction:column;gap:.2rem;">
                        @foreach($item->items as $line)
                            <li>{{ $line['product_name'] ?? 'Món' }} · {{ $line['variant_name'] ?? 'Size' }} × <strong>{{ $line['quantity'] ?? 1 }}</strong></li>
                        @endforeach
                    </ul>
                </div>

                {{-- Price --}}
                <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-top:1px solid var(--cafe-border);">
                    <span style="font-size:.875rem;color:var(--cafe-text-secondary);">Giá dự kiến:</span>
                    <strong style="font-size:1.1rem;color:var(--cafe-text-primary);">{{ number_format((float)$item->subtotal,0,',','.') }} ₫</strong>
                </div>

                {{-- Note --}}
                @if($item->note)
                    <p style="font-size:.85rem;color:var(--cafe-text-secondary);font-style:italic;padding:.5rem .75rem;background:var(--cafe-surface-alt);border-radius:6px;margin:0;">
                        Ghi chú: {{ $item->note }}
                    </p>
                @endif

                {{-- Actions --}}
                @if($item->status === 'pending')
                    <div style="display:flex;gap:.5rem;">
                        <form method="POST" action="{{ route('staff.pickups.update', $item) }}" style="flex:1;">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="accepted">
                            <button class="cafe-btn-primary" type="submit" style="width:100%;">
                                <x-internal.icon name="check" size="15" /> Tiếp nhận
                            </button>
                        </form>
                        <form method="POST" action="{{ route('staff.pickups.update', $item) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button class="cafe-btn-icon cafe-btn-icon--danger" type="submit" onclick="return confirm('Từ chối yêu cầu hẹn lấy này?')" aria-label="Từ chối">
                                <x-internal.icon name="x" size="15" />
                            </button>
                        </form>
                    </div>
                @else
                    <p style="font-size:.85rem;color:var(--cafe-text-secondary);padding-top:.5rem;border-top:1px solid var(--cafe-border);margin:0;">
                        Đã hoàn tất xử lý ({{ $item->status === 'accepted' ? 'Đã tiếp nhận' : 'Đã từ chối' }}).
                    </p>
                @endif
            </article>
        @endforeach
    </div>

    <div style="margin-top:1rem;">{{ $requests->links() }}</div>
@endif

<div class="cafe-notice cafe-notice--info" style="margin-top:1.25rem;">
    <strong>Quy định nghiệp vụ:</strong> Yêu cầu nhận món (scheduled pickup) là thỏa thuận hẹn lấy tại quán và chưa trừ kho hay thu tiền. Khi khách đến nhận, nhân viên tiến hành xác thực và tạo đơn POS chính thức.
</div>
@endsection
