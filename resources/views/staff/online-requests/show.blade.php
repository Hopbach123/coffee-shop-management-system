@extends('layouts.internal')

@section('title', 'Chi tiết yêu cầu #' . $requestRecord->request_code)

@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Breadcrumb">
            <span>Tiếp nhận yêu cầu</span>
            <span class="cafe-breadcrumbs__separator" aria-hidden="true">/</span>
            <span>Chi tiết yêu cầu</span>
        </nav>
        <div style="display: flex; align-items: baseline; gap: 14px; flex-wrap: wrap;">
            <h1 class="cafe-page-title" style="margin: 0; white-space: nowrap;">#{{ $requestRecord->request_code }}</h1>
            @if($requestRecord->status === 'pending')
                <span class="cafe-badge cafe-badge--warning">⏳ Chờ duyệt</span>
            @elseif($requestRecord->status === 'accepted')
                <span class="cafe-badge cafe-badge--success">✓ Đã tiếp nhận</span>
            @elseif($requestRecord->status === 'rejected')
                <span class="cafe-badge cafe-badge--danger">✕ Đã từ chối</span>
            @else
                <span class="cafe-badge">{{ $requestRecord->status }}</span>
            @endif
        </div>
        <p class="cafe-page-subtitle">Gửi lúc: {{ \Carbon\Carbon::parse($requestRecord->created_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}</p>
    </div>
    <a href="{{ route('staff.online-requests.index') }}" class="cafe-btn cafe-btn-secondary">
        ← Về danh sách
    </a>
</div>

<div style="display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.9fr); gap: 22px; align-items: start;">
    <!-- Left Column: Customer & Items -->
    <div style="display: grid; gap: 20px;">
        <!-- Customer Info Card -->
        <div class="cafe-card" style="padding: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="font-size: 1.15rem; margin: 0; font-weight: 700;">Thông tin khách & Phương thức nhận</h2>
                @if($requestRecord->purchase_type === 'scheduled')
                    <span class="kitchen-channel-badge channel-scheduled">⏰ Đặt hẹn</span>
                @else
                    <span class="kitchen-channel-badge channel-online">🌐 Lấy sớm</span>
                @endif
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div>
                    <small style="color: var(--cafe-text-muted, #7c716a); display: block;">Khách hàng liên hệ</small>
                    <strong style="font-size: 1.05rem; color: var(--cafe-espresso, #3b281f);">{{ $requestRecord->contact_name }}</strong>
                    <span style="display: block; color: var(--cafe-caramel, #ad754b); font-weight: 600;">{{ $requestRecord->contact_phone }}</span>
                </div>

                <div>
                    <small style="color: var(--cafe-text-muted, #7c716a); display: block;">Thời gian hẹn lấy (VN)</small>
                    <strong style="color: var(--cafe-espresso, #3b281f);">
                        @if($requestRecord->pickup_at)
                            {{ \Carbon\Carbon::parse($requestRecord->pickup_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}
                        @else
                            Lấy sớm sau khi được duyệt
                        @endif
                    </strong>
                </div>

                <div>
                    <small style="color: var(--cafe-text-muted, #7c716a); display: block;">Hình thức thanh toán</small>
                    <strong style="color: var(--cafe-espresso, #3b281f);">
                        {{ $requestRecord->payment_preference === 'cash' ? '💵 Tiền mặt' : '🏦 Chuyển khoản' }} · 
                        {{ $requestRecord->payment_timing === 'prepaid' ? 'Trả trước' : 'Tại quầy khi nhận đồ' }}
                    </strong>
                    <small style="display: block; color: var(--cafe-text-muted, #7c716a); font-size: 0.78rem;">(Chưa xác nhận thu tiền)</small>
                </div>
            </div>

            @if($requestRecord->payment_timing === 'prepaid')
                <div style="margin-top: 16px; padding: 12px 14px; border-left: 3px solid var(--cafe-caramel, #ad754b); background: var(--cafe-surface-alt, #fcf8f2); border-radius: 0 6px 6px 0;">
                    <strong style="display: block; color: var(--cafe-espresso, #3b281f); margin-bottom: 3px;">Cần xác minh từ hai phía</strong>
                    <small style="color: var(--cafe-text-muted, #7c716a); line-height: 1.45;">Khách chỉ mới chọn chuyển khoản trước. Sau khi nhận đơn, hãy đối chiếu số tiền và nội dung giao dịch thực nhận, rồi mới ghi Payment tại màn hình thanh toán. Không xác nhận paid chỉ từ lựa chọn của khách.</small>
                </div>
            @endif

            @if($requestRecord->note)
                <div style="margin-top: 16px; padding: 10px 14px; background: var(--cafe-surface-alt, #fcf8f2); border-left: 3px solid var(--cafe-caramel, #ad754b); border-radius: 0 6px 6px 0;">
                    <small style="color: var(--cafe-text-muted, #7c716a); font-weight: 700; display: block; margin-bottom: 2px;">Ghi chú từ khách:</small>
                    <span>{{ $requestRecord->note }}</span>
                </div>
            @endif

            @if($requestRecord->rejection_reason)
                <div style="margin-top: 16px; padding: 12px 16px; background: #fdf2f2; border: 1px solid #f8b4b4; border-radius: 6px;">
                    <strong style="color: #b54640; display: block; margin-bottom: 2px;">Lý do từ chối:</strong>
                    <span style="color: #771d1d;">{{ $requestRecord->rejection_reason }}</span>
                </div>
            @endif
        </div>

        <!-- Items Card -->
        <div class="cafe-card" style="padding: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2 style="font-size: 1.15rem; margin: 0; font-weight: 700;">Món khách yêu cầu</h2>
                <span class="cafe-badge cafe-badge--info">{{ $items->count() }} món</span>
            </div>

            <p style="color: var(--cafe-text-muted, #7c716a); font-size: 0.85rem; margin-top: 0; margin-bottom: 16px;">
                Tên và đơn giá ghi nhận tại thời điểm khách gửi yêu cầu.
            </p>

            <div style="display: grid; gap: 14px;">
                @php $calcTotal = 0; @endphp
                @forelse ($items as $item)
                    @php
                        $itemToppings = $toppings->get($item->id, collect());
                        $topSum = $itemToppings->sum(fn($t) => (float)$t->price * (int)$t->quantity);
                        $lineTotal = ((float)$item->unit_price + $topSum) * (int)$item->quantity;
                        $calcTotal += $lineTotal;
                    @endphp
                    <div style="padding: 14px; background: var(--cafe-surface-alt, #fcf8f2); border-radius: 8px; border: 1px solid var(--cafe-border, #e9e0d6); display: flex; justify-content: space-between; align-items: flex-start; gap: 14px;">
                        <div>
                            <strong style="font-size: 1.05rem; color: var(--cafe-espresso, #3b281f);">{{ $item->product_name }}</strong>
                            <span style="color: var(--cafe-text-muted, #7c716a); font-size: 0.9rem;"> · Size {{ $item->variant_name }}</span>
                            <div style="margin-top: 2px; color: var(--cafe-text-muted, #7c716a); font-size: 0.85rem;">
                                {{ $item->quantity }} phần × {{ number_format((float)$item->unit_price, 0, ',', '.') }}đ
                            </div>

                            @if($itemToppings->isNotEmpty())
                                <div style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 6px;">
                                    @foreach($itemToppings as $top)
                                        <span class="kitchen-toppings-pill">
                                            + {{ $top->topping_name }} ({{ $top->quantity }} phần · {{ number_format((float)$top->price, 0, ',', '.') }}đ)
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if($item->note)
                                <div style="margin-top: 6px; font-size: 0.82rem; color: var(--cafe-text-muted, #7c716a);">
                                    Ghi chú: <em>{{ $item->note }}</em>
                                </div>
                            @endif
                        </div>

                        <strong style="color: var(--cafe-caramel, #ad754b); font-size: 1.1rem; white-space: nowrap; font-variant-numeric: tabular-nums;">
                            {{ number_format($lineTotal, 0, ',', '.') }}đ
                        </strong>
                    </div>
                @empty
                    <p style="color: var(--cafe-text-muted, #7c716a); text-align: center; padding: 20px;">Yêu cầu không có dòng món nào.</p>
                @endforelse
            </div>

            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-top: 20px; padding-top: 14px; border-top: 2px solid var(--cafe-border, #e9e0d6);">
                <span style="font-weight: 700; font-size: 1.05rem;">Tổng dự tính:</span>
                <strong style="font-size: 1.4rem; color: var(--cafe-caramel, #ad754b); font-variant-numeric: tabular-nums;">
                    {{ number_format($calcTotal, 0, ',', '.') }}đ
                </strong>
            </div>
        </div>
    </div>

    <!-- Right Column: Order Link & Actions -->
    <aside style="display: grid; gap: 20px;">
        <div class="cafe-card" style="padding: 22px;">
            <div style="margin-bottom: 16px;">
                <h2 style="font-size: 1.15rem; margin: 0; font-weight: 700;">Xử lý yêu cầu</h2>
            </div>

            @if($order)
                <div style="background: #eaf4ee; border: 1px solid #c3e2cf; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <strong style="color: #276845; font-size: 1.1rem;">#{{ $order->order_code }}</strong>
                        <span class="cafe-badge cafe-badge--success">Đã tạo đơn</span>
                    </div>
                    <p style="margin: 0; color: var(--cafe-text, #342820); font-size: 0.9rem;">
                        Tổng chốt: <strong>{{ number_format((float)$order->total_amount, 0, ',', '.') }}đ</strong>
                    </p>
                </div>

                @if(auth('web')->user()?->canOperateSales())
                    <a href="{{ route('staff.orders.show', $order->id) }}" class="cafe-btn cafe-btn-primary" style="width: 100%; text-align: center; justify-content: center;">
                        Mở chi tiết đơn hàng →
                    </a>
                @endif
            @elseif($requestRecord->status === 'pending' && auth('web')->user()?->canOperateSales())
                <p style="color: var(--cafe-text-muted, #7c716a); font-size: 0.88rem; line-height: 1.5; margin-top: 0; margin-bottom: 18px;">
                    Khi nhân viên tiếp nhận, hệ thống sẽ xác nhận lại khả năng phục vụ và tạo đơn hàng chính thức để Barista bắt đầu pha chế.
                </p>

                <div style="display: grid; gap: 12px;">
                    <form method="POST" action="{{ route('staff.online-requests.accept', $requestRecord->id) }}" onsubmit="return confirm('Xác nhận tiếp nhận yêu cầu này và tạo đơn hàng chính thức?');">
                        @csrf
                        <button type="submit" class="cafe-btn cafe-btn-primary" style="width: 100%; justify-content: center;">
                            ✓ Tiếp nhận & Tạo đơn
                        </button>
                    </form>

                    <details style="border: 1px solid var(--cafe-border, #e9e0d6); border-radius: 9px; padding: 10px 14px; background: var(--cafe-surface-alt, #fcf8f2);">
                        <summary style="cursor: pointer; font-weight: 700; color: #b54640; font-size: 0.9rem;">
                            ✕ Từ chối yêu cầu
                        </summary>
                        <form method="POST" action="{{ route('staff.online-requests.reject', $requestRecord->id) }}" style="margin-top: 12px; display: grid; gap: 10px;">
                            @csrf
                            <div>
                                <label for="rejection_reason" style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 4px; color: var(--cafe-espresso, #3b281f);">
                                    Lý do từ chối (bắt buộc):
                                </label>
                                <input type="text" name="rejection_reason" id="rejection_reason" required maxlength="500" placeholder="VD: Hết nguyên liệu, quá tải..." style="width: 100%; padding: 8px 10px; border: 1px solid var(--cafe-border, #e9e0d6); border-radius: 6px; background: #ffffff;">
                            </div>
                            <button type="submit" class="cafe-btn cafe-btn-danger" style="width: 100%; justify-content: center;">
                                Xác nhận từ chối
                            </button>
                        </form>
                    </details>
                </div>
            @else
                <p style="color: var(--cafe-text-muted, #7c716a); font-size: 0.88rem; margin: 0;">
                    Yêu cầu đã được xử lý hoặc bạn không có quyền thao tác bán hàng.
                </p>
            @endif
        </div>
    </aside>
</div>
@endsection
