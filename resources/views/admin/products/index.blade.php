@extends('layouts.internal')
@section('title', 'Sản phẩm')
@section('content')
<section class="category-page" aria-labelledby="page-title" data-catalog-view>
    <div class="cafe-page-header"><div><nav class="cafe-breadcrumbs" aria-label="Điều hướng"><a href="{{ route('admin.home') }}">Tổng quan</a><span>›</span><span>Thực đơn</span></nav><p class="v5-eyebrow">MAISON DU CAFÉ · QUẢN TRỊ</p>
        <h1 id="page-title" class="cafe-page-title">Quản lý sản phẩm</h1><p class="cafe-page-subtitle">Thông tin món, hình ảnh và trạng thái bán. Giá bán được quản lý tại từng size.</p></div><a href="{{ route('admin.products.create') }}" class="cafe-btn-primary"><x-internal.icon name="plus" size="18" /> Thêm sản phẩm</a></div>
    <div class="cafe-card category-panel">
        <div class="v5-toolbar">
            <form method="GET" action="{{ route('admin.products.index') }}" role="search" class="v5-search-field"><label for="product-search">Tìm sản phẩm</label><div class="v5-actions"><input id="product-search" class="cafe-input" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập tên sản phẩm…"><button class="cafe-btn-secondary" type="submit">Tìm</button>@if($search !== '')<a class="cafe-btn-secondary" href="{{ route('admin.products.index') }}">Xóa lọc</a>@endif</div></form>
            <div class="v5-view-switch" data-catalog-controls hidden aria-label="Cách hiển thị sản phẩm"><button type="button" data-catalog-mode="grid" aria-controls="product-grid-container" aria-pressed="true">Lưới</button><button type="button" data-catalog-mode="table" aria-controls="product-table-container" aria-pressed="false">Bảng</button></div>
        </div>
        @if($products->isEmpty())
            <div class="v5-empty" role="status"><x-internal.icon name="coffee" size="40" /><h2>{{ $search !== '' ? 'Không tìm thấy sản phẩm' : 'Chưa có sản phẩm' }}</h2><p>{{ $search !== '' ? 'Thử tên khác hoặc xóa bộ lọc.' : 'Thêm sản phẩm đầu tiên để bắt đầu quản lý thực đơn.' }}</p><a class="cafe-btn-secondary" href="{{ $search !== '' ? route('admin.products.index') : route('admin.products.create') }}">{{ $search !== '' ? 'Xem tất cả' : 'Thêm sản phẩm' }}</a></div>
        @else
            <div id="product-grid-container" data-catalog-panel="grid" class="v5-gallery">
                @foreach($products as $product)
                <article class="v5-product-card"><div class="v5-product-media">@if($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy" width="400" height="300">@else<x-internal.icon name="coffee" size="48" />@endif<span class="cafe-badge cafe-badge--{{ $product->status === 'available' ? 'success' : 'warning' }}">{{ $product->status === 'available' ? 'Có sẵn' : 'Tạm hết' }}</span></div><div class="v5-product-body"><span class="v5-eyebrow">{{ $product->category?->name ?? 'Chưa có danh mục' }} @if($product->category?->trashed())(đã xóa)@endif</span><h2>{{ $product->name }}</h2><p>{{ $product->description ?: 'Chưa có mô tả chi tiết.' }}</p>@if($product->is_featured)<span class="cafe-badge cafe-badge--info">Sản phẩm nổi bật</span>@endif<div class="v5-card-footer">@include('admin.products._actions')</div></div></article>
                @endforeach
            </div>
            <div id="product-table-container" data-catalog-panel="table" class="cafe-table-wrap" hidden><table class="cafe-table"><caption class="sr-only">Danh sách sản phẩm</caption><thead><tr><th scope="col">Sản phẩm</th><th scope="col">Danh mục</th><th scope="col">Mô tả</th><th scope="col">Trạng thái</th><th scope="col">Nổi bật</th><th scope="col">Thao tác</th></tr></thead><tbody>@foreach($products as $product)<tr><th scope="row">{{ $product->name }}</th><td>{{ $product->category?->name ?? '—' }} @if($product->category?->trashed())(đã xóa)@endif</td><td>{{ $product->description ?: '—' }}</td><td><span class="cafe-badge cafe-badge--{{ $product->status === 'available' ? 'success' : 'warning' }}">{{ $product->status === 'available' ? 'Có sẵn' : 'Tạm hết' }}</span></td><td>{{ $product->is_featured ? 'Có' : 'Không' }}</td><td>@include('admin.products._actions')</td></tr>@endforeach</tbody></table></div>
            <div class="v5-pagination"><span>Hiển thị {{ $products->firstItem() }}–{{ $products->lastItem() }} / {{ $products->total() }} kết quả</span>@if($products->hasPages())<div>{{ $products->links() }}</div>@endif</div>
        @endif
    </div>
</section>
@endsection
