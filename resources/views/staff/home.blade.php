@extends('layouts.internal')

@section('title', 'Bán hàng / POS')

@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Breadcrumb">
            <span>Nhân viên</span>
            <span class="cafe-breadcrumbs__separator" aria-hidden="true">/</span>
            <span>Quầy bán hàng</span>
        </nav>
        <h1 class="cafe-page-title">Bán hàng / POS</h1>
        <p class="cafe-page-subtitle">Chọn món từ thực đơn, lên đơn nhanh và chuyển lệnh pha chế cho Barista</p>
    </div>
    <span class="cafe-badge cafe-badge--info">Tạo đơn tại quầy</span>
</div>

<div class="pos-grid">
    <!-- Catalog Section -->
    <section class="pos-catalog">
        <!-- Search Card -->
        <div class="cafe-card" style="margin-bottom: 18px; padding: 12px 16px;">
            <form action="{{ route('staff.home') }}" method="GET" class="pos-search">
                <x-internal.icon name="search" size="18"/>
                <label for="pos-menu-search" class="sr-only">Tìm món theo tên</label>
                <input id="pos-menu-search" type="search" name="q" value="{{ $search }}" placeholder="Tìm món trong thực đơn theo tên..." maxlength="100">
                @if($search)
                    <a href="{{ route('staff.home') }}" style="color: var(--cafe-text-muted, #7c716a); font-size: 0.85rem; padding-right: 8px;">✕ Xóa tìm</a>
                @endif
                <button type="submit" class="cafe-btn cafe-btn-primary">Tìm kiếm</button>
            </form>
        </div>

        <!-- Product Grid -->
        <div class="pos-product-grid">
            @forelse($products as $product)
                <article class="pos-product">
                    <div class="pos-product-art">
                        @if($product->imageUrl())
                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy">
                        @else
                            <x-internal.icon name="coffee" size="38"/>
                        @endif
                    </div>

                    <span class="pos-product-category">{{ $product->category?->name ?? 'Maison Coffee' }}</span>
                    <h2>{{ $product->name }}</h2>

                    @if($product->productVariants->isEmpty())
                        <p style="margin-top: auto; font-size: 0.85rem; color: var(--cafe-text-muted, #7c716a);">Tạm hết hàng</p>
                    @else
                        <div style="margin-top: auto; display: grid; gap: 6px;">
                            @foreach($product->productVariants as $variant)
                                <form method="POST" action="{{ route('staff.pos.add') }}" class="pos-product-option">
                                    @csrf
                                    <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                                    <span>
                                        <span>Size {{ $variant->name }}</span>
                                        <strong style="font-variant-numeric: tabular-nums;">{{ number_format((float)$variant->price, 0, ',', '.') }}đ</strong>
                                    </span>
                                    <button type="submit" aria-label="Thêm {{ $product->name }} {{ $variant->name }}">
                                        + Thêm vào đơn
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </article>
            @empty
                <div class="cafe-card" style="grid-column: 1 / -1; padding: 48px; text-align: center; color: var(--cafe-text-muted, #7c716a);">
                    <x-internal.icon name="coffee" size="44"/>
                    <p style="margin: 12px 0 4px; font-size: 1.1rem; font-weight: 700; color: var(--cafe-espresso, #3b281f);">Không tìm thấy món phù hợp</p>
                    <small>Thử tìm với từ khóa khác hoặc kiểm tra lại danh mục sản phẩm trong Admin.</small>
                </div>
            @endforelse
        </div>
    </section>

    <!-- POS Cart Sidebar -->
    <aside class="pos-cart">
        <div class="cafe-card" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="font-size: 1.15rem; margin: 0; font-weight: 700;">Đơn hiện tại</h2>
                <span class="cafe-badge cafe-badge--info">Đang tạo</span>
            </div>

            @forelse($lines as $line)
                <div class="pos-cart-line">
                    <div>
                        <strong>{{ $line['variant']->product->name }}</strong>
                        <small>{{ $line['variant']->name }} · SL: {{ $line['qty'] }}</small>
                        <span style="font-variant-numeric: tabular-nums;">{{ number_format($line['priceCents'] * $line['qty'] / 100, 0, ',', '.') }}đ</span>
                    </div>
                    <form method="POST" action="{{ route('staff.pos.remove') }}">
                        @csrf
                        <input type="hidden" name="variant_id" value="{{ $line['variant']->id }}">
                        <button type="submit" aria-label="Bỏ {{ $line['variant']->product->name }} khỏi đơn" title="Bỏ món">×</button>
                    </form>
                </div>
            @empty
                <div class="pos-cart-empty">
                    <x-internal.icon name="bag" size="38"/>
                    <p style="margin: 8px 0 2px; font-weight: 700; color: var(--cafe-espresso, #3b281f);">Chưa có món nào</p>
                    <small style="color: var(--cafe-text-muted, #7c716a);">Chọn món từ thực đơn bên trái để bắt đầu</small>
                </div>
            @endforelse

            <div class="pos-total">
                <span>Tạm tính</span>
                <strong style="font-variant-numeric: tabular-nums;">{{ number_format($totalCents / 100, 0, ',', '.') }}đ</strong>
            </div>

            <form method="POST" action="{{ route('staff.pos.checkout') }}" class="pos-checkout">
                @csrf
                <input type="hidden" name="submission_key" value="{{ session('pos_intent') }}">

                <div>
                    <label for="pos-order-type" style="font-weight: 700; font-size: 0.85rem; color: var(--cafe-espresso, #3b281f); margin-bottom: 4px; display: block;">Hình thức phục vụ</label>
                    <select id="pos-order-type" name="order_type" data-order-type class="cafe-select" style="width: 100%; padding: 8px 12px; border: 1px solid var(--cafe-border, #e9e0d6); border-radius: 6px;">
                        <option value="takeaway">Mang đi (Takeaway)</option>
                        <option value="dine_in">Tại bàn (Dine-in)</option>
                    </select>
                </div>

                <div data-pos-table hidden>
                    <label for="pos-table" style="font-weight: 700; font-size: 0.85rem; color: var(--cafe-espresso, #3b281f); margin-bottom: 4px; display: block;">Chọn bàn phục vụ</label>
                    <select name="table_id" id="pos-table" class="cafe-select" style="width: 100%; padding: 8px 12px; border: 1px solid var(--cafe-border, #e9e0d6); border-radius: 6px;">
                        <option value="">-- Chọn bàn trống --</option>
                        @foreach($tables as $table)
                            <option value="{{ $table->id }}">
                                {{ $table->table_name ?: $table->table_code }} · {{ $table->capacity }} chỗ
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="pos-note" style="font-weight: 700; font-size: 0.85rem; color: var(--cafe-espresso, #3b281f); margin-bottom: 4px; display: block;">Ghi chú pha chế / phục vụ</label>
                    <textarea id="pos-note" name="note" rows="2" maxlength="500" placeholder="Ghi chú đơn hàng (ví dụ: ít ngọt, không đá...)" style="width: 100%; padding: 8px 12px; border: 1px solid var(--cafe-border, #e9e0d6); border-radius: 6px;"></textarea>
                </div>

                <button class="cafe-btn cafe-btn-primary" type="submit" @if(!$lines) disabled @endif style="width: 100%; justify-content: center; min-height: 44px; margin-top: 10px;">
                    Tạo đơn hàng <x-internal.icon name="arrow" size="18" />
                </button>
            </form>

            <p style="margin-top: 14px; line-height: 1.4; font-size: 0.78rem; color: var(--cafe-text-muted, #7c716a);">
                ✧ Đơn hàng được tạo ở trạng thái <code>pending</code>. Barista sẽ bắt đầu pha và hệ thống tự động kiểm tra trừ nguyên liệu kho khi bắt đầu pha.
            </p>
        </div>
    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const orderTypeSelect = document.querySelector('[data-order-type]');
    const tableContainer = document.querySelector('[data-pos-table]');
    if (!orderTypeSelect || !tableContainer) return;

    function syncTableVisibility() {
        const isDineIn = orderTypeSelect.value === 'dine_in';
        tableContainer.hidden = !isDineIn;
        const select = tableContainer.querySelector('select');
        if (select) {
            select.required = isDineIn;
            if (!isDineIn) select.value = '';
        }
    }

    orderTypeSelect.addEventListener('change', syncTableVisibility);
    syncTableVisibility();
});
</script>
@endsection
