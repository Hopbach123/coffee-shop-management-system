@extends('layouts.internal')
@section('title', 'Biến thể sản phẩm')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div>
                <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
                <h1 id="page-title">Biến thể sản phẩm</h1>
                <p>Quản lý kích cỡ, SKU, giá bán và trạng thái của từng sản phẩm.</p>
            </div>
            <x-public.button href="{{ route('admin.product-variants.create') }}">Thêm biến thể</x-public.button>
        </div>

        @if (session('success'))
            <p class="category-notice category-notice--success" role="status">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="category-notice category-notice--error" role="alert">{{ session('error') }}</p>
        @endif

        <div class="category-panel">
            <form method="GET" action="{{ route('admin.product-variants.index') }}" class="category-search" role="search">
                <label for="variant-search">Tìm biến thể theo tên hoặc SKU</label>
                <div class="category-search__controls">
                    <input id="variant-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập tên biến thể hoặc SKU">
                    <x-public.button type="submit">Tìm kiếm</x-public.button>
                    @if ($search !== '')
                        <x-public.button href="{{ route('admin.product-variants.index') }}" variant="outline">Xóa lọc</x-public.button>
                    @endif
                </div>
            </form>

            @if ($variants->isEmpty())
                <div class="category-empty">
                    @if ($search !== '')
                        <h2>Không tìm thấy biến thể</h2>
                        <p>Thử tên hoặc SKU khác, hoặc xóa bộ lọc.</p>
                        <x-public.button href="{{ route('admin.product-variants.index') }}" variant="outline">Xem tất cả</x-public.button>
                    @else
                        <h2>Chưa có biến thể</h2>
                        <p>Thêm biến thể đầu tiên để thiết lập giá bán cho sản phẩm.</p>
                        <x-public.button href="{{ route('admin.product-variants.create') }}" variant="outline">Thêm biến thể</x-public.button>
                    @endif
                </div>
            @else
                <div class="category-table-wrap product-table-wrap" role="region" aria-label="Danh sách biến thể sản phẩm" tabindex="0">
                    <table class="category-table product-table">
                        <caption class="sr-only">Danh sách biến thể sản phẩm</caption>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Sản phẩm</th>
                                <th scope="col">Tên biến thể</th>
                                <th scope="col">SKU</th>
                                <th scope="col">Giá bán</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($variants as $variant)
                                <tr>
                                    <td data-label="ID">{{ $variant->id }}</td>
                                    <td data-label="Sản phẩm">
                                        {{ $variant->product?->name ?? '—' }}
                                        @if ($variant->product?->trashed()) (đã xóa) @endif
                                    </td>
                                    <th scope="row" data-label="Tên biến thể">{{ $variant->name }}</th>
                                    <td data-label="SKU">{{ $variant->sku ?? '—' }}</td>
                                    <td data-label="Giá bán">{{ number_format((float) $variant->price, 2, ',', '.') }} ₫</td>
                                    <td data-label="Trạng thái">
                                        <span class="category-status category-status--{{ $variant->status === 'available' ? 'active' : 'inactive' }}">{{ $variant->status === 'available' ? 'Có sẵn' : 'Tạm hết' }}</span>
                                    </td>
                                    <td data-label="Thao tác">
                                        <div class="category-actions">
                                            <x-public.button href="{{ route('admin.product-variants.edit', $variant) }}" variant="outline">Sửa</x-public.button>
                                            <form method="POST" action="{{ route('admin.product-variants.toggle-status', $variant) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-public.button type="submit" variant="outline">{{ $variant->status === 'available' ? 'Đánh dấu tạm hết' : 'Đánh dấu có sẵn' }}</x-public.button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.product-variants.destroy', $variant) }}" onsubmit="return confirm('Bạn có chắc muốn xóa biến thể này?');">
                                                @csrf
                                                @method('DELETE')
                                                <x-public.button type="submit" variant="outline" class="category-delete">Xóa</x-public.button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($variants->hasPages())
                    <div class="category-pagination">{{ $variants->links() }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
