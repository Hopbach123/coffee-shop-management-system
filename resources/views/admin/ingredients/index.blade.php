@extends('layouts.internal')
@section('title', 'Nguyên liệu')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div>
                <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
                <h1 id="page-title">Nguyên liệu</h1>
                <p>Quản lý nguyên liệu, đơn vị tính, giá vốn và ngưỡng tồn tối thiểu.</p>
            </div>
            <x-public.button href="{{ route('admin.ingredients.create') }}">Thêm nguyên liệu</x-public.button>
        </div>

        @if (session('success'))
            <p class="category-notice category-notice--success" role="status">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="category-notice category-notice--error" role="alert">{{ session('error') }}</p>
        @endif

        <div class="category-panel">
            <form method="GET" action="{{ route('admin.ingredients.index') }}" class="category-search" role="search">
                <label for="ingredient-search">Tìm nguyên liệu theo tên</label>
                <div class="category-search__controls">
                    <input id="ingredient-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập tên nguyên liệu">
                    <x-public.button type="submit">Tìm kiếm</x-public.button>
                    @if ($search !== '')
                        <x-public.button href="{{ route('admin.ingredients.index') }}" variant="outline">Xóa lọc</x-public.button>
                    @endif
                </div>
            </form>

            @if ($ingredients->isEmpty())
                <div class="category-empty">
                    @if ($search !== '')
                        <h2>Không tìm thấy nguyên liệu</h2>
                        <p>Thử tên khác hoặc xóa bộ lọc.</p>
                        <x-public.button href="{{ route('admin.ingredients.index') }}" variant="outline">Xem tất cả</x-public.button>
                    @else
                        <h2>Chưa có nguyên liệu</h2>
                        <p>Thêm nguyên liệu đầu tiên để bắt đầu quản lý.</p>
                        <x-public.button href="{{ route('admin.ingredients.create') }}" variant="outline">Thêm nguyên liệu</x-public.button>
                    @endif
                </div>
            @else
                <div class="category-table-wrap" role="region" aria-label="Danh sách nguyên liệu" tabindex="0">
                    <table class="category-table">
                        <caption class="sr-only">Danh sách nguyên liệu</caption>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Tên</th>
                                <th scope="col">Đơn vị tính</th>
                                <th scope="col">Tồn hiện tại</th>
                                <th scope="col">Tồn tối thiểu</th>
                                <th scope="col">Giá vốn</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ingredients as $ingredient)
                                <tr>
                                    <td data-label="ID">{{ $ingredient->id }}</td>
                                    <th scope="row" data-label="Tên">{{ $ingredient->name }}</th>
                                    <td data-label="Đơn vị tính">{{ $ingredient->unit }}</td>
                                    <td data-label="Tồn hiện tại">{{ $ingredient->current_stock }}</td>
                                    <td data-label="Tồn tối thiểu">{{ $ingredient->minimum_stock }}</td>
                                    <td data-label="Giá vốn">{{ number_format((float) $ingredient->cost_price, 2, ',', '.') }} ₫</td>
                                    <td data-label="Trạng thái">
                                        <span class="category-status category-status--{{ $ingredient->status === 'active' ? 'active' : 'inactive' }}">{{ $ingredient->status === 'active' ? 'Đang dùng' : 'Ngừng dùng' }}</span>
                                    </td>
                                    <td data-label="Thao tác">
                                        <div class="category-actions">
                                            <x-public.button href="{{ route('admin.ingredients.edit', $ingredient) }}" variant="outline">Sửa</x-public.button>
                                            <form method="POST" action="{{ route('admin.ingredients.toggle-status', $ingredient) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-public.button type="submit" variant="outline">{{ $ingredient->status === 'active' ? 'Ngừng sử dụng' : 'Sử dụng lại' }}</x-public.button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.ingredients.destroy', $ingredient) }}" onsubmit="return confirm('Bạn có chắc muốn xóa nguyên liệu này?');">
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
                @if ($ingredients->hasPages())
                    <div class="category-pagination">{{ $ingredients->links() }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
