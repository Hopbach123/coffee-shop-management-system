@extends('layouts.internal')
@section('title','Hàng đợi pha chế')
@section('content')
<div class="sneat-page-title">
    <div>
        <p>Vận hành · Barista Workspace</p>
        <h1>Hàng đợi pha chế</h1>
        <span>Danh sách món cần pha chế từ các đơn hàng hợp lệ (loại trừ đơn đã hủy / đã hoàn thành)</span>
    </div>
    <span class="sneat-pill is-info">Hàng đợi: {{ $items->count() }} món</span>
</div>
<div class="kitchen-grid">
    @forelse($items as $item)
        <article class="sneat-card kitchen-item">
            <div class="sneat-card-heading">
                <a href="{{ $item->order ? route('staff.orders.show', $item->order) : '#' }}" style="color: var(--coffee-caramel); font-weight: 700; text-decoration: underline;">
                    #{{ $item->order?->order_code ?? 'ĐƠN' }}
                </a>
                <span class="sneat-pill {{ $item->status === 'ready' ? 'is-success' : ($item->status === 'preparing' ? 'is-info' : 'is-warning') }}">
                    {{ ['pending'=>'Chờ pha','preparing'=>'Đang pha','ready'=>'Sẵn sàng'][$item->status] ?? $item->status }}
                </span>
            </div>
            <h2>{{ $item->product_name ?: ($item->product?->name ?? 'Món uống') }}</h2>
            <p><strong>Kích cỡ / Size:</strong> {{ $item->variant_name ?: ($item->productVariant?->name ?? 'Tiêu chuẩn') }} · <strong>Số lượng:</strong> {{ $item->quantity }} phần</p>
            @if($item->orderItemToppings && $item->orderItemToppings->isNotEmpty())
                <p style="color: var(--coffee-accent); margin-top: 0.25rem;">
                    <strong>Món thêm:</strong> {{ $item->orderItemToppings->pluck('topping_name')->join(', ') }}
                </p>
            @endif
            @if($item->note)
                <p style="background: var(--coffee-bg); padding: 0.35rem 0.5rem; border-radius: var(--radius-sm); border-left: 3px solid var(--coffee-caramel); font-size: 0.9rem;">
                    <strong>Ghi chú:</strong> {{ $item->note }}
                </p>
            @endif
            <small style="color: var(--coffee-text-muted); display: block; margin-top: 0.5rem;">
                Nhận lúc: {{ $item->created_at?->format('H:i d/m') }}
            </small>
        </article>
    @empty
        <div class="sneat-card sneat-empty" style="grid-column: 1 / -1;">
            Hiện không có món nào trong hàng đợi pha chế.
        </div>
    @endforelse
</div>
<p class="sneat-hint" style="margin-top: 1.5rem;">
    <strong>Quy định vận hành:</strong> Màn hình pha chế phục vụ theo dõi trực quan cho quầy Barista. Việc chuyển trạng thái sang “Đang pha” tự động trừ kho nguyên liệu cần được tích hợp với <code>InventoryService</code> có kiểm tra định lượng và khóa dòng nguyên tử trước khi kích hoạt thao tác mutation.
</p>
@endsection
