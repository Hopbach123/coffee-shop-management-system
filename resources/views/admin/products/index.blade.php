@extends('layouts.internal')
@section('title', 'Sản phẩm')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div>
                <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
                <h1 id="page-title">Sản phẩm</h1>
                <p>Quản lý thông tin và trạng thái sẵn có của sản phẩm.</p>
            </div>
            <x-public.button href="{{ route('admin.products.create') }}">Thêm sản phẩm</x-public.button>
        </div>

        @if (session('success'))
            <p class="category-notice category-notice--success" role="status">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="category-notice category-notice--error" role="alert">{{ session('error') }}</p>
        @endif

        <div class="category-panel">
            <form method="GET" action="{{ route('admin.products.index') }}" class="category-search" role="search">
                <label for="product-search">Tìm sản phẩm theo tên</label>
                <div class="category-search__controls">
                    <input id="product-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập tên sản phẩm">
                    <x-public.button type="submit">Tìm kiếm</x-public.button>
                    @if ($search !== '')
                        <x-public.button href="{{ route('admin.products.index') }}" variant="outline">Xóa lọc</x-public.button>
                    @endif
                </div>
            </form>

            @if ($products->isEmpty())
                <div class="category-empty">
                    @if ($search !== '')
                        <h2>Không tìm thấy sản phẩm</h2>
                        <p>Thử tên khác hoặc xóa bộ lọc để xem toàn bộ sản phẩm.</p>
                        <x-public.button href="{{ route('admin.products.index') }}" variant="outline">Xem tất cả</x-public.button>
                    @else
                        <h2>Chưa có sản phẩm</h2>
                        <p>Thêm sản phẩm đầu tiên để bắt đầu quản lý thực đơn.</p>
                        <x-public.button href="{{ route('admin.products.create') }}" variant="outline">Thêm sản phẩm</x-public.button>
                    @endif
                </div>
            @else
                <div class="category-table-wrap product-table-wrap" role="region" aria-label="Danh sách sản phẩm" tabindex="0">
                    <table class="category-table product-table">
                        <caption class="sr-only">Danh sách sản phẩm</caption>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Tên</th>
                                <th scope="col">Danh mục</th>
                                <th scope="col">Mô tả</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col">Nổi bật</th>
                                <th scope="col">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td data-label="ID">{{ $product->id }}</td>
                                    <th scope="row" data-label="Tên">{{ $product->name }}</th>
                                    <td data-label="Danh mục">
                                        {{ $product->category?->name ?? '—' }}
                                        @if ($product->category?->trashed()) (đã xóa) @endif
                                    </td>
                                    <td data-label="Mô tả" class="category-description">{{ $product->description !== null && $product->description !== '' ? $product->description : '—' }}</td>
                                    <td data-label="Trạng thái">
                                        <span class="category-status category-status--{{ $product->status === 'available' ? 'active' : 'inactive' }}">{{ $product->status === 'available' ? 'Có sẵn' : 'Tạm hết' }}</span>
                                    </td>
                                    <td data-label="Nổi bật">{{ $product->is_featured ? 'Có' : 'Không' }}</td>
                                    <td data-label="Thao tác">
                                        <div class="category-actions">
                                            <x-public.button href="{{ route('admin.products.edit', $product) }}" variant="outline">Sửa</x-public.button>
                                            <form method="POST" action="{{ route('admin.products.toggle-status', $product) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-public.button type="submit" variant="outline">{{ $product->status === 'available' ? 'Đánh dấu tạm hết' : 'Đánh dấu có sẵn' }}</x-public.button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">
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
                @if ($products->hasPages())
                    <div class="category-pagination">{{ $products->links() }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
