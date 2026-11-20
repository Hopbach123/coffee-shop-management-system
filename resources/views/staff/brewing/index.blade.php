@extends('layouts.internal')

@section('title', 'Hàng đợi pha chế')

@section('content')
@php
    $pendingItems = $items->where('status', 'pending');
    $preparingItems = $items->where('status', 'preparing');
    $readyItems = $items->where('status', 'ready');
@endphp

<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Breadcrumb">
            <span>Vận hành</span>
            <span class="cafe-breadcrumbs__separator" aria-hidden="true">/</span>
            <span>Barista Workspace</span>
        </nav>
        <h1 class="cafe-page-title">Hàng đợi pha chế</h1>
        <p class="cafe-page-subtitle">Thao tác theo từng món, rõ trạng thái và tự động kiểm tra trừ nguyên liệu kho khi bắt đầu pha.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <span class="cafe-badge cafe-badge--warning" style="font-size: 0.9rem; padding: 6px 14px;">
            ☕ Hàng đợi: {{ $items->count() }} món
        </span>
    </div>
</div>

@if($items->isEmpty())
    <div class="cafe-card" style="margin-bottom: 20px; padding: 40px 20px; text-align: center; color: var(--cafe-text-muted, #7c716a);">
        <x-internal.icon name="chef" size="48"/>
        <p style="margin: 12px 0 4px; font-weight: 700; font-size: 1.15rem; color: var(--cafe-espresso, #3b281f);">
            Chưa có món nào trong hàng đợi pha chế
        </p>
        <small>Tất cả các món đã được pha chế và phục vụ khách hàng chu đáo.</small>
    </div>
@endif

<div class="kitchen-kanban">
    {{-- Lane 1: Chờ pha --}}
    <section class="kitchen-lane" aria-labelledby="lane-pending-heading">
        <div class="kitchen-lane-head amber">
            <span id="lane-pending-heading">◉ Chờ pha</span>
            <span>{{ $pendingItems->count() }} món</span>
        </div>
        <div class="kitchen-lane-body">
            @forelse($pendingItems as $item)
                <article class="kitchen-card-barista status-pending">
                    <div class="kitchen-card-top">
                        <div>
                            <a href="{{ $item->order ? route('staff.orders.show', $item->order) : '#' }}" class="kitchen-order-code">
                                #{{ $item->order?->order_code ?? 'ĐƠN' }}
                            </a>
                            <div style="margin-top: 4px;">
                                @php $purchaseType = $item->order?->purchase_type ?? 'counter'; @endphp
                                @if($purchaseType === 'scheduled')
                                    <span class="kitchen-channel-badge channel-scheduled">⏰ Đặt hẹn</span>
                                @elseif($purchaseType === 'online')
                                    <span class="kitchen-channel-badge channel-online">🌐 Online</span>
                                @else
                                    <span class="kitchen-channel-badge channel-counter">🏪 Tại quầy</span>
                                @endif
                            </div>
                        </div>
                        <span class="cafe-badge cafe-badge--warning">Chờ pha</span>
                    </div>

                    <h2 class="kitchen-item-title" style="font-size: 1.15rem; margin: 8px 0 4px;">
                        {{ $item->product_name ?: ($item->product?->name ?? 'Món uống') }}
                    </h2>

                    <div class="kitchen-item-meta">
                        <span>Size: <strong>{{ $item->variant_name ?: ($item->productVariant?->name ?? 'Tiêu chuẩn') }}</strong></span>
                        <span>·</span>
                        <span>SL: <strong style="color: var(--cafe-caramel, #ad754b); font-size: 1.05rem;">{{ $item->quantity }}</strong></span>
                    </div>

                    @if($item->orderItemToppings && $item->orderItemToppings->isNotEmpty())
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            @foreach($item->orderItemToppings as $topping)
                                <span class="kitchen-toppings-pill">
                                    + {{ $topping->topping_name }} ({{ $topping->quantity }} phần)
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if($item->note)
                        <div class="kitchen-note-box">
                            <strong>Ghi chú:</strong> {{ $item->note }}
                        </div>
                    @endif

                    <div class="kitchen-time-info">
                        <span>Nhận đơn: {{ $item->created_at?->format('H:i d/m') }}</span>
                        @if($item->order?->onlineOrderRequest?->pickup_at)
                            <span style="color: var(--cafe-caramel, #ad754b); font-weight: 600;">
                                ⏰ Giờ hẹn: {{ $item->order->onlineOrderRequest->pickup_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}
                            </span>
                        @endif
                    </div>

                    <div class="kitchen-card-actions">
                        @if ($item->order)
                            <form method="POST" action="{{ route('staff.orders.items.status.update', [$item->order, $item]) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="preparing">
                                <button type="submit" class="btn-barista-start">Bắt đầu pha</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="kitchen-lane-empty">
                    <p style="margin: 0; font-weight: 600;">Không có món chờ pha</p>
                    <small>Tất cả món đã được bắt đầu hoặc hoàn tất.</small>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Lane 2: Đang pha --}}
    <section class="kitchen-lane" aria-labelledby="lane-preparing-heading">
        <div class="kitchen-lane-head blue">
            <span id="lane-preparing-heading">◉ Đang pha</span>
            <span>{{ $preparingItems->count() }} món</span>
        </div>
        <div class="kitchen-lane-body">
            @forelse($preparingItems as $item)
                <article class="kitchen-card-barista status-preparing">
                    <div class="kitchen-card-top">
                        <div>
                            <a href="{{ $item->order ? route('staff.orders.show', $item->order) : '#' }}" class="kitchen-order-code">
                                #{{ $item->order?->order_code ?? 'ĐƠN' }}
                            </a>
                            <div style="margin-top: 4px;">
                                @php $purchaseType = $item->order?->purchase_type ?? 'counter'; @endphp
                                @if($purchaseType === 'scheduled')
                                    <span class="kitchen-channel-badge channel-scheduled">⏰ Đặt hẹn</span>
                                @elseif($purchaseType === 'online')
                                    <span class="kitchen-channel-badge channel-online">🌐 Online</span>
                                @else
                                    <span class="kitchen-channel-badge channel-counter">🏪 Tại quầy</span>
                                @endif
                            </div>
                        </div>
                        <span class="cafe-badge cafe-badge--info">Đang pha</span>
                    </div>

                    <h2 class="kitchen-item-title" style="font-size: 1.15rem; margin: 8px 0 4px;">
                        {{ $item->product_name ?: ($item->product?->name ?? 'Món uống') }}
                    </h2>

                    <div class="kitchen-item-meta">
                        <span>Size: <strong>{{ $item->variant_name ?: ($item->productVariant?->name ?? 'Tiêu chuẩn') }}</strong></span>
                        <span>·</span>
                        <span>SL: <strong style="color: var(--cafe-caramel, #ad754b); font-size: 1.05rem;">{{ $item->quantity }}</strong></span>
                    </div>

                    @if($item->orderItemToppings && $item->orderItemToppings->isNotEmpty())
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            @foreach($item->orderItemToppings as $topping)
                                <span class="kitchen-toppings-pill">
                                    + {{ $topping->topping_name }} ({{ $topping->quantity }} phần)
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if($item->note)
                        <div class="kitchen-note-box">
                            <strong>Ghi chú:</strong> {{ $item->note }}
                        </div>
                    @endif

                    <div class="kitchen-time-info">
                        <span>Nhận đơn: {{ $item->created_at?->format('H:i d/m') }}</span>
                        @if($item->order?->onlineOrderRequest?->pickup_at)
                            <span style="color: var(--cafe-caramel, #ad754b); font-weight: 600;">
                                ⏰ Giờ hẹn: {{ $item->order->onlineOrderRequest->pickup_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}
                            </span>
                        @endif
                    </div>

                    <div class="kitchen-card-actions">
                        @if ($item->order)
                            <form method="POST" action="{{ route('staff.orders.items.status.update', [$item->order, $item]) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="ready">
                                <button type="submit" class="btn-barista-done">Pha xong</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="kitchen-lane-empty">
                    <p style="margin: 0; font-weight: 600;">Không có món đang pha</p>
                    <small>Chọn "Bắt đầu pha" từ cột Chờ pha.</small>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Lane 3: Sẵn sàng --}}
    <section class="kitchen-lane" aria-labelledby="lane-ready-heading">
        <div class="kitchen-lane-head green">
            <span id="lane-ready-heading">◉ Sẵn sàng</span>
            <span>{{ $readyItems->count() }} món</span>
        </div>
        <div class="kitchen-lane-body">
            @forelse($readyItems as $item)
                <article class="kitchen-card-barista status-ready">
                    <div class="kitchen-card-top">
                        <div>
                            <a href="{{ $item->order ? route('staff.orders.show', $item->order) : '#' }}" class="kitchen-order-code">
                                #{{ $item->order?->order_code ?? 'ĐƠN' }}
                            </a>
                            <div style="margin-top: 4px;">
                                @php $purchaseType = $item->order?->purchase_type ?? 'counter'; @endphp
                                @if($purchaseType === 'scheduled')
                                    <span class="kitchen-channel-badge channel-scheduled">⏰ Đặt hẹn</span>
                                @elseif($purchaseType === 'online')
                                    <span class="kitchen-channel-badge channel-online">🌐 Online</span>
                                @else
                                    <span class="kitchen-channel-badge channel-counter">🏪 Tại quầy</span>
                                @endif
                            </div>
                        </div>
                        <span class="cafe-badge cafe-badge--success">Sẵn sàng</span>
                    </div>

                    <h2 class="kitchen-item-title" style="font-size: 1.15rem; margin: 8px 0 4px;">
                        {{ $item->product_name ?: ($item->product?->name ?? 'Món uống') }}
                    </h2>

                    <div class="kitchen-item-meta">
                        <span>Size: <strong>{{ $item->variant_name ?: ($item->productVariant?->name ?? 'Tiêu chuẩn') }}</strong></span>
                        <span>·</span>
                        <span>SL: <strong style="color: var(--cafe-caramel, #ad754b); font-size: 1.05rem;">{{ $item->quantity }}</strong></span>
                    </div>

                    @if($item->orderItemToppings && $item->orderItemToppings->isNotEmpty())
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            @foreach($item->orderItemToppings as $topping)
                                <span class="kitchen-toppings-pill">
                                    + {{ $topping->topping_name }} ({{ $topping->quantity }} phần)
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if($item->note)
                        <div class="kitchen-note-box">
                            <strong>Ghi chú:</strong> {{ $item->note }}
                        </div>
                    @endif

                    <div class="kitchen-time-info">
                        <span>Nhận đơn: {{ $item->created_at?->format('H:i d/m') }}</span>
                        @if($item->order?->onlineOrderRequest?->pickup_at)
                            <span style="color: var(--cafe-caramel, #ad754b); font-weight: 600;">
                                ⏰ Giờ hẹn: {{ $item->order->onlineOrderRequest->pickup_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}
                            </span>
                        @endif
                    </div>

                    <div class="kitchen-card-actions">
                        @if ($item->order)
                            <a href="{{ route('staff.orders.show', $item->order) }}" class="btn-barista-serve">
                                Xem chi tiết & giao đơn →
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="kitchen-lane-empty">
                    <p style="margin: 0; font-weight: 600;">Chưa có món sẵn sàng</p>
                    <small>Món sau khi pha xong sẽ chuyển sang đây để phục vụ.</small>
                </div>
            @endforelse
        </div>
    </section>
</div>

<div style="margin-top: 24px; padding: 14px 18px; background: var(--cafe-surface-alt, #fcf8f2); border-radius: 9px; border: 1px solid var(--cafe-border, #e9e0d6); line-height: 1.5; font-size: 13px; color: var(--cafe-text-muted, #776d65);">
    <strong>Quy định vận hành Barista:</strong> Lệnh "Bắt đầu pha" sẽ tự động kiểm tra công thức và trừ tồn kho nguyên liệu tương ứng trong một giao dịch an toàn. Nếu thiếu công thức hoặc thiếu tồn kho, hệ thống sẽ cảnh báo và giữ món ở hàng đợi. Sau khi pha xong, bấm "Pha xong" để thông báo món đã sẵn sàng phục vụ.
</div>
@endsection
