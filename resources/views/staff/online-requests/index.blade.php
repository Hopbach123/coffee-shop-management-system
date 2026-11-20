@extends('layouts.internal')

@section('title', 'Online & Hẹn đến lấy')

@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Breadcrumb">
            <span>Vận hành</span>
            <span class="cafe-breadcrumbs__separator" aria-hidden="true">/</span>
            <span>Tiếp nhận yêu cầu</span>
        </nav>
        <h1 class="cafe-page-title">Online & Hẹn đến lấy</h1>
        <p class="cafe-page-subtitle">Danh sách các yêu cầu đặt trước từ website đang chờ xác nhận hoặc đã liên kết đơn hàng</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="{{ route('staff.orders.index') }}" class="cafe-btn cafe-btn-secondary">
            📋 Xem đơn hàng
        </a>
    </div>
</div>

@if (! $available)
    <div class="cafe-card" role="status" style="text-align: center; padding: 48px 24px;">
        <x-internal.icon name="box" size="44"/>
        <h2 style="font-size: 1.25rem; margin: 14px 0 6px;">Chưa thể tải yêu cầu</h2>
        <p style="color: var(--cafe-text-muted, #7c716a); margin: 0;">Dữ liệu đặt online và hẹn đến lấy đang được chuẩn bị. Vui lòng liên hệ quản trị viên.</p>
    </div>
@else
    <!-- Filter Card -->
    <div class="cafe-card" style="margin-bottom: 20px; padding: 18px 22px;">
        <form method="GET" action="{{ route('staff.online-requests.index') }}" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
            <div style="display: flex; flex-direction: column; gap: 6px; min-width: 180px;">
                <label for="purchase_type" style="font-size: 0.82rem; font-weight: 700; color: var(--cafe-espresso, #3b281f);">Kênh nhận món</label>
                <select id="purchase_type" name="purchase_type" class="cafe-select" style="padding: 9px 12px; border: 1px solid var(--cafe-border, #e9e0d6); border-radius: 9px; background: #ffffff;">
                    <option value="">Tất cả kênh</option>
                    <option value="online" @selected(($filters['purchase_type'] ?? '') === 'online')>🌐 Online · Lấy sớm</option>
                    <option value="scheduled" @selected(($filters['purchase_type'] ?? '') === 'scheduled')>⏰ Đặt hẹn · Có giờ nhận</option>
                </select>
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px; min-width: 180px;">
                <label for="status" style="font-size: 0.82rem; font-weight: 700; color: var(--cafe-espresso, #3b281f);">Trạng thái yêu cầu</label>
                <select id="status" name="status" class="cafe-select" style="padding: 9px 12px; border: 1px solid var(--cafe-border, #e9e0d6); border-radius: 9px; background: #ffffff;">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>⏳ Đang chờ tiếp nhận</option>
                    <option value="accepted" @selected(($filters['status'] ?? '') === 'accepted')>✓ Đã tiếp nhận</option>
                    <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>✕ Đã từ chối</option>
                    <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Đã hủy</option>
                </select>
            </div>

            <button type="submit" class="cafe-btn cafe-btn-primary">
                Lọc danh sách
            </button>

            @if(!empty($filters['purchase_type']) || !empty($filters['status']))
                <a href="{{ route('staff.online-requests.index') }}" class="cafe-btn cafe-btn-secondary" style="font-size: 0.85rem;">
                    ✕ Xóa bộ lọc
                </a>
            @endif
        </form>
    </div>

    <!-- Requests Table Card -->
    <div class="cafe-card" style="padding: 0; overflow: hidden;">
        @if ($requests->isEmpty())
            <div style="padding: 48px 20px; text-align: center; color: var(--cafe-text-muted, #7c716a);">
                <x-internal.icon name="box" size="44"/>
                <p style="margin: 12px 0 4px; font-weight: 700; font-size: 1.1rem; color: var(--cafe-espresso, #3b281f);">Không có yêu cầu nào phù hợp</p>
                <small>Thử bỏ lọc hoặc kiểm tra lại sau khi có khách gửi yêu cầu đặt món.</small>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table class="cafe-table">
                    <thead>
                        <tr>
                            <th style="white-space: nowrap;">Mã yêu cầu</th>
                            <th style="white-space: nowrap;">Khách hàng</th>
                            <th style="white-space: nowrap;">Kênh đặt</th>
                            <th style="white-space: nowrap;">Giờ hẹn lấy (VN)</th>
                            <th style="white-space: nowrap;">Trạng thái</th>
                            <th style="white-space: nowrap; text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $record)
                            <tr>
                                <td>
                                    <strong style="font-size: 0.95rem; color: var(--cafe-espresso, #3b281f); white-space: nowrap;">{{ $record->request_code }}</strong>
                                    <small style="display: block; color: var(--cafe-text-muted, #7c716a); font-size: 0.78rem;">
                                        Gửi: {{ \Carbon\Carbon::parse($record->created_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m') }}
                                    </small>
                                </td>
                                <td>
                                    <strong>{{ $record->contact_name }}</strong>
                                    @if($record->contact_phone)
                                        <small style="display: block; color: var(--cafe-text-muted, #7c716a);">{{ $record->contact_phone }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($record->purchase_type === 'scheduled')
                                        <span class="kitchen-channel-badge channel-scheduled">⏰ Đặt hẹn</span>
                                    @else
                                        <span class="kitchen-channel-badge channel-online">🌐 Lấy sớm</span>
                                    @endif
                                </td>
                                <td>
                                    @if($record->pickup_at)
                                        <strong style="color: var(--cafe-caramel, #ad754b); white-space: nowrap;">
                                            {{ \Carbon\Carbon::parse($record->pickup_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}
                                        </strong>
                                    @else
                                        <span style="color: var(--cafe-text-muted, #7c716a);">Lấy sớm sau khi duyệt</span>
                                    @endif
                                </td>
                                <td>
                                    @if($record->status === 'pending')
                                        <span class="cafe-badge cafe-badge--warning">⏳ Chờ duyệt</span>
                                    @elseif($record->status === 'accepted')
                                        <span class="cafe-badge cafe-badge--success">✓ Đã tiếp nhận</span>
                                    @elseif($record->status === 'rejected')
                                        <span class="cafe-badge cafe-badge--danger">✕ Từ chối</span>
                                    @else
                                        <span class="cafe-badge">{{ $record->status }}</span>
                                    @endif
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="{{ route('staff.online-requests.show', $record->id) }}" class="cafe-btn cafe-btn-secondary" style="min-height: 34px; padding: 0 12px; font-size: 0.85rem;">
                                        Xem chi tiết →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
                <div style="padding: 14px 20px; border-top: 1px solid var(--cafe-border, #e9e0d6);">
                    {{ $requests->links() }}
                </div>
            @endif
        @endif
    </div>
@endif
@endsection
