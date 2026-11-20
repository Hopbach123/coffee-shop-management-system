@extends('layouts.public')

@section('title', 'Theo dõi đơn hàng #' . $orderRequest->request_code . ' — Maison du Café')

@section('content')
<section class="coffee-section commerce-page">
    <div class="coffee-container">
        <header class="commerce-hero">
            <p class="section-heading__eyebrow">ORDER TRACKING</p>
            <h1>Theo dõi tiến độ đơn hàng</h1>
            <p>
                Mã yêu cầu: <strong style="color: var(--coffee-caramel); font-family: monospace; font-size: 1.15rem;">{{ $orderRequest->request_code }}</strong> · 
                Kênh: <strong>{{ $orderRequest->purchase_type === 'scheduled' ? '⏰ Đặt hẹn đến lấy' : '🌐 Online lấy sớm' }}</strong>
            </p>
        </header>

        @if(session('success'))
            <div class="commerce-alert" role="status">
                {{ session('success') }}
            </div>
        @endif

        @php
            $isPending = $orderRequest->status === 'pending';
            $isAccepted = $orderRequest->status === 'accepted';
            $isRejected = $orderRequest->status === 'rejected';
            $order = $orderRequest->order;
            $orderStatus = $order?->status ?? 'pending';
            $isPreparing = $order && in_array($orderStatus, ['preparing', 'ready', 'completed']);
            $isReady = $order && in_array($orderStatus, ['ready', 'completed']);
            $isCompleted = $order && $orderStatus === 'completed';
        @endphp

        <!-- Visual Stepper Progress Bar -->
        @if(!$isRejected)
            <div style="background: var(--coffee-surface); border: 1px solid var(--coffee-border); border-radius: var(--radius-md); padding: 24px; margin-bottom: 24px;">
                <ol class="tracking-stepper">
                    <li class="tracking-step is-done">
                        <div class="tracking-step-circle">✓</div>
                        <div>
                            <span class="tracking-step-label">1. Gửi yêu cầu</span>
                            <span class="tracking-step-desc">Đã tiếp nhận vào hệ thống</span>
                        </div>
                    </li>

                    <li class="tracking-step {{ $isAccepted ? 'is-done' : ($isPending ? 'is-active' : '') }}">
                        <div class="tracking-step-circle">{{ $isAccepted ? '✓' : '2' }}</div>
                        <div>
                            <span class="tracking-step-label">2. Quán tiếp nhận</span>
                            <span class="tracking-step-desc">{{ $isAccepted ? 'Đã duyệt đơn' : 'Đang chờ nhân viên' }}</span>
                        </div>
                    </li>

                    <li class="tracking-step {{ $isReady ? 'is-done' : ($isPreparing ? 'is-active' : '') }}">
                        <div class="tracking-step-circle">{{ $isReady ? '✓' : '3' }}</div>
                        <div>
                            <span class="tracking-step-label">3. Pha chế món</span>
                            <span class="tracking-step-desc">{{ $isReady ? 'Pha xong' : ($isPreparing ? 'Barista đang pha' : 'Chờ chuẩn bị') }}</span>
                        </div>
                    </li>

                    <li class="tracking-step {{ $isCompleted ? 'is-done' : ($isReady ? 'is-active' : '') }}">
                        <div class="tracking-step-circle">{{ $isCompleted ? '✓' : '4' }}</div>
                        <div>
                            <span class="tracking-step-label">4. Sẵn sàng nhận</span>
                            <span class="tracking-step-desc">{{ $isCompleted ? 'Đã nhận đồ' : ($isReady ? 'Mời bạn ghé lấy' : 'Chờ giao') }}</span>
                        </div>
                    </li>
                </ol>
            </div>
        @endif

        <div style="display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.9fr); gap: 24px; align-items: start;">
            <!-- Left Column: Status & Items -->
            <div style="display: grid; gap: 20px;">
                <!-- Status Detail Card -->
                <div style="background: var(--coffee-surface); border: 1px solid var(--coffee-border); border-radius: var(--radius-md); padding: 22px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                        <h2 style="font-family: var(--font-display); font-size: 1.35rem; margin: 0; color: var(--coffee-espresso);">
                            Trạng thái đơn hàng
                        </h2>

                        @if($isPending)
                            <span style="background: #fef3c7; color: #92400e; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 0.88rem; border: 1px solid #fde68a;">
                                ⏳ Đang chờ quán tiếp nhận
                            </span>
                        @elseif($isAccepted)
                            <span style="background: #d1fae5; color: #065f46; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 0.88rem; border: 1px solid #a7f3d0;">
                                ✓ Đã tiếp nhận đơn
                            </span>
                        @elseif($isRejected)
                            <span style="background: #fee2e2; color: #991b1b; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 0.88rem; border: 1px solid #fecaca;">
                                ✕ Quán từ chối tiếp nhận
                            </span>
                        @endif
                    </div>

                    @if($isPending)
                        <p style="margin: 0; color: var(--coffee-text-muted); line-height: 1.5;">
                            Quán đang kiểm tra khả năng phục vụ và sẽ xác nhận yêu cầu của bạn trong giây lát. Hãy lưu hoặc giữ trang này để theo dõi tiến độ pha chế nhé!
                        </p>
                    @elseif($isAccepted)
                        <div style="background: var(--coffee-surface-alt); padding: 14px 18px; border-radius: 8px; border: 1px solid var(--coffee-border); margin-bottom: 14px;">
                            <p style="margin: 0; color: var(--coffee-espresso); font-weight: 600;">
                                ✧ Quán đã tiếp nhận yêu cầu! Đơn hàng chính thức: <strong style="color: var(--coffee-caramel);">#{{ $order?->order_code }}</strong>
                            </p>
                            @if($isReady)
                                <p style="margin: 6px 0 0; color: var(--coffee-success); font-weight: 600;">
                                    🎉 Thức uống của bạn đã sẵn sàng tại quầy nhận món!
                                </p>
                            @endif
                        </div>
                    @elseif($isRejected)
                        <div style="background: #fdf2f2; border: 1px solid #f8b4b4; border-radius: 8px; padding: 14px 18px;">
                            <strong style="color: var(--coffee-danger); display: block; margin-bottom: 4px;">Lý do từ chối từ quán:</strong>
                            <span style="color: #771d1d;">{{ $orderRequest->rejection_reason ?: 'Quán hiện quá tải hoặc hết nguyên liệu pha chế món này. Rất mong bạn thông cảm!' }}</span>
                        </div>
                    @endif

                    <!-- Key Details Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; border-top: 1px dashed var(--coffee-border); padding-top: 16px; margin-top: 16px;">
                        <div>
                            <small style="color: var(--coffee-text-muted); display: block;">Khách hàng</small>
                            <strong style="color: var(--coffee-espresso);">{{ $orderRequest->contact_name }}</strong>
                            <small style="display: block; color: var(--coffee-text-muted);">{{ $orderRequest->contact_phone }}</small>
                        </div>

                        <div>
                            <small style="color: var(--coffee-text-muted); display: block;">Thời gian hẹn lấy</small>
                            <strong style="color: var(--coffee-espresso);">
                                @if($orderRequest->pickup_at)
                                    {{ $orderRequest->pickup_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}
                                @else
                                    Lấy sớm sau khi duyệt
                                @endif
                            </strong>
                        </div>

                        <div>
                            <small style="color: var(--coffee-text-muted); display: block;">Thanh toán dự kiến</small>
                            <strong style="color: var(--coffee-espresso);">
                                {{ $orderRequest->payment_preference === 'cash' ? '💵 Tiền mặt' : '🏦 Chuyển khoản' }}
                            </strong>
                            <small style="display: block; color: var(--coffee-text-muted);">
                                {{ $orderRequest->payment_timing === 'prepaid' ? 'Trả trước' : 'Tại quầy nhận đồ' }}
                            </small>
                        </div>

                        <div>
                            <small style="color: var(--coffee-text-muted); display: block;">Trạng thái thanh toán</small>
                            <strong>
                                @if($order?->isPaidInFull())
                                    <span style="color: var(--coffee-success);">✓ Đã thanh toán</span>
                                @else
                                    <span style="color: var(--coffee-caramel);">Chưa thanh toán</span>
                                @endif
                            </strong>
                        </div>
                    </div>
                </div>

                <!-- Ordered Items Card -->
                <div style="background: var(--coffee-surface); border: 1px solid var(--coffee-border); border-radius: var(--radius-md); padding: 22px;">
                    <h2 style="font-family: var(--font-display); font-size: 1.35rem; margin-top: 0; margin-bottom: 16px; color: var(--coffee-espresso);">
                        Danh sách món đã đặt
                    </h2>

                    <div style="display: grid; gap: 12px;">
                        @foreach($orderRequest->items as $item)
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 12px; border-bottom: 1px solid color-mix(in srgb, var(--coffee-border) 40%, transparent); gap: 12px;">
                                <div>
                                    <strong style="font-size: 1.05rem; color: var(--coffee-espresso);">{{ $item->product_name }}</strong>
                                    <span style="color: var(--coffee-text-muted); font-size: 0.9rem;"> · {{ $item->variant_name }} × {{ $item->quantity }}</span>

                                    @if($item->toppings && $item->toppings->isNotEmpty())
                                        <div style="margin-top: 4px; display: flex; flex-wrap: wrap; gap: 6px;">
                                            @foreach($item->toppings as $top)
                                                <span style="font-size: 0.78rem; background: #fdf6ec; border: 1px solid #eed9be; color: var(--coffee-caramel); padding: 2px 8px; border-radius: 4px;">
                                                    + {{ $top->topping_name }} ({{ number_format((float)$top->price, 0, ',', '.') }}đ)
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($item->note)
                                        <div style="font-size: 0.82rem; color: var(--coffee-text-muted); margin-top: 4px;">
                                            Ghi chú: {{ $item->note }}
                                        </div>
                                    @endif
                                </div>

                                <strong style="color: var(--coffee-caramel); font-size: 1.05rem; white-space: nowrap;">
                                    {{ number_format((float)$item->unit_price * $item->quantity, 0, ',', '.') }}đ
                                </strong>
                            </div>
                        @endforeach
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-top: 18px; padding-top: 14px; border-top: 2px solid var(--coffee-border);">
                        <span style="font-size: 1.1rem; font-weight: 700; color: var(--coffee-espresso);">Tổng cộng</span>
                        <strong style="font-size: 1.5rem; color: var(--coffee-caramel); font-family: var(--font-display);">
                            {{ number_format((float)$orderRequest->totalAmount(), 0, ',', '.') }}đ
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Right Column: Instructions & Actions -->
            <aside style="display: grid; gap: 20px;">
                <div style="background: var(--coffee-surface); border: 1px solid var(--coffee-border); border-radius: var(--radius-md); padding: 22px;">
                    <h3 style="font-family: var(--font-display); font-size: 1.2rem; color: var(--coffee-cocoa); margin-top: 0; margin-bottom: 12px;">
                        Hướng dẫn nhận đồ tại quán
                    </h3>
                    <ul style="padding-left: 18px; margin: 0; display: grid; gap: 10px; font-size: 0.9rem; color: var(--coffee-text); line-height: 1.5;">
                        <li>Đến quầy thu ngân Maison du Café và thông báo mã <strong>{{ $orderRequest->request_code }}</strong> hoặc số điện thoại của bạn.</li>
                        <li>Nếu đã hẹn giờ cụ thể, barista sẽ căn thời gian pha chế chuẩn xác để thức uống tươi ngon nhất.</li>
                        <li>Bạn có thể quét mã QR thanh toán nhanh hoặc trả tiền mặt khi nhận đồ tại quầy.</li>
                    </ul>

                    <div style="margin-top: 20px; text-align: center;">
                        <a href="{{ route('menu.index') }}" class="coffee-button coffee-button--primary" style="width: 100%; justify-content: center; text-align: center; display: inline-flex;">
                            Khám phá thêm món khác →
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
