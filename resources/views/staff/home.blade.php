@extends('layouts.internal')
@section('title', 'Bán hàng / POS')
@section('content')
<div class="sneat-page-title"><div><p>Nhân viên · Quầy bán hàng</p><h1>Bán hàng / POS</h1><span>Chọn món, tạo đơn và chuyển đến pha chế</span></div><span class="sneat-pill is-success">● Đang hoạt động</span></div>
<div class="pos-grid">
    <section class="pos-catalog"><div class="sneat-card"><form action="{{ route('staff.home') }}" method="GET" class="pos-search"><x-internal.icon name="search" size="18"/><input type="search" name="q" value="{{ $search }}" placeholder="Tìm món trong thực đơn..." maxlength="100"><button type="submit">Tìm</button></form></div>
    <div class="pos-product-grid">@forelse($products as $product)
        <article class="pos-product"><div class="pos-product-art">@if($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}">@else<x-internal.icon name="coffee" size="38"/>@endif</div><span class="pos-product-category">{{ $product->category?->name }}</span><h2>{{ $product->name }}</h2>
        @if($product->productVariants->isEmpty())<p class="sneat-muted">Tạm hết hàng</p>@else
        @foreach($product->productVariants as $variant)<form method="POST" action="{{ route('staff.pos.add') }}" class="pos-product-option">@csrf<input type="hidden" name="variant_id" value="{{ $variant->id }}"><span>{{ $variant->name }} <strong>{{ number_format((float)$variant->price,0,',','.') }}đ</strong></span><button type="submit" aria-label="Thêm {{ $product->name }} {{ $variant->name }}">+ Thêm</button></form>@endforeach
        @endif</article>
    @empty<div class="sneat-card sneat-empty">Không có món phù hợp. Hãy kiểm tra sản phẩm trong Admin.</div>@endforelse</div></section>
    <aside class="pos-cart"><div class="sneat-card"><div class="sneat-card-heading"><h2>Đơn hiện tại</h2><span class="sneat-pill is-info">Đang tạo</span></div>
    @forelse($lines as $line)<div class="pos-cart-line"><div><strong>{{ $line['variant']->product->name }}</strong><small>{{ $line['variant']->name }} · SL: {{ $line['qty'] }}</small><span>{{ number_format($line['priceCents']*$line['qty']/100,0,',','.') }}đ</span></div><form method="POST" action="{{ route('staff.pos.remove') }}">@csrf<input type="hidden" name="variant_id" value="{{ $line['variant']->id }}"><button aria-label="Bỏ {{ $line['variant']->product->name }} khỏi đơn" title="Bỏ món">×</button></form></div>@empty<div class="pos-cart-empty"><x-internal.icon name="bag" size="38"/><p>Chưa có món nào</p><small>Chọn món từ thực đơn để bắt đầu</small></div>@endforelse
    <div class="pos-total"><span>Tạm tính</span><strong>{{ number_format($totalCents/100,0,',','.') }}đ</strong></div>
    <form method="POST" action="{{ route('staff.pos.checkout') }}" class="pos-checkout">@csrf<label for="pos-order-type">Hình thức</label><select id="pos-order-type" name="order_type" data-order-type><option value="takeaway">Mang đi</option><option value="dine_in">Tại bàn</option></select><div data-pos-table hidden><label for="pos-table">Chọn bàn trống</label><select name="table_id" id="pos-table"><option value="">-- Chọn bàn --</option>@foreach($tables as $table)<option value="{{ $table->id }}">{{ $table->table_name ?: $table->table_code }} · {{ $table->capacity }} chỗ</option>@endforeach</select></div><label for="pos-note">Ghi chú</label><textarea id="pos-note" name="note" rows="2" maxlength="500" placeholder="Ghi chú đơn hàng (nếu có)"></textarea><button class="sneat-primary" type="submit" @if(!$lines) disabled @endif>Tạo đơn hàng <x-internal.icon name="arrow" size="18" /></button></form>
    <p class="sneat-hint">Chỉ tạo đơn ở trạng thái pending. Chưa thu tiền hay trừ nguyên liệu cho đến khi xử lý nghiệp vụ.</p></div></aside>
</div>
@endsection
