@extends('layouts.internal')
@section('title', 'Topping')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div>
                <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
                <h1 id="page-title">Topping</h1>
                <p>Quản lý topping dùng chung, giá bán và trạng thái sẵn có.</p>
            </div>
            <x-public.button href="{{ route('admin.toppings.create') }}">Thêm topping</x-public.button>
        </div>

        @if (session('success'))
            <p class="category-notice category-notice--success" role="status">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="category-notice category-notice--error" role="alert">{{ session('error') }}</p>
        @endif

        <div class="category-panel">
            <form method="GET" action="{{ route('admin.toppings.index') }}" class="category-search" role="search">
                <label for="topping-search">Tìm topping theo tên</label>
                <div class="category-search__controls">
                    <input id="topping-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập tên topping">
                    <x-public.button type="submit">Tìm kiếm</x-public.button>
                    @if ($search !== '')
                        <x-public.button href="{{ route('admin.toppings.index') }}" variant="outline">Xóa lọc</x-public.button>
                    @endif
                </div>
            </form>

            @if ($toppings->isEmpty())
                <div class="category-empty">
                    @if ($search !== '')
                        <h2>Không tìm thấy topping</h2>
                        <p>Thử tên khác hoặc xóa bộ lọc.</p>
                        <x-public.button href="{{ route('admin.toppings.index') }}" variant="outline">Xem tất cả</x-public.button>
                    @else
                        <h2>Chưa có topping</h2>
                        <p>Thêm topping đầu tiên để bắt đầu quản lý.</p>
                        <x-public.button href="{{ route('admin.toppings.create') }}" variant="outline">Thêm topping</x-public.button>
                    @endif
                </div>
            @else
                <div class="category-table-wrap" role="region" aria-label="Danh sách topping" tabindex="0">
                    <table class="category-table">
                        <caption class="sr-only">Danh sách topping</caption>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Tên</th>
                                <th scope="col">Giá bán</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($toppings as $topping)
                                <tr>
                                    <td data-label="ID">{{ $topping->id }}</td>
                                    <th scope="row" data-label="Tên">{{ $topping->name }}</th>
                                    <td data-label="Giá bán">{{ number_format((float) $topping->price, 2, ',', '.') }} ₫</td>
                                    <td data-label="Trạng thái">
                                        <span class="category-status category-status--{{ $topping->status === 'available' ? 'active' : 'inactive' }}">{{ $topping->status === 'available' ? 'Có sẵn' : 'Tạm hết' }}</span>
                                    </td>
                                    <td data-label="Thao tác">
                                        <div class="category-actions">
                                            <x-public.button href="{{ route('admin.toppings.edit', $topping) }}" variant="outline">Sửa</x-public.button>
                                            <form method="POST" action="{{ route('admin.toppings.toggle-status', $topping) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-public.button type="submit" variant="outline">{{ $topping->status === 'available' ? 'Đánh dấu tạm hết' : 'Đánh dấu có sẵn' }}</x-public.button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.toppings.destroy', $topping) }}" onsubmit="return confirm('Bạn có chắc muốn xóa topping này?');">
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
                @if ($toppings->hasPages())
                    <div class="category-pagination">{{ $toppings->links() }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
