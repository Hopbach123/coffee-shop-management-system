@extends('layouts.internal')
@section('title', 'Danh mục')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div>
                <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
                <h1 id="page-title">Danh mục</h1>
                <p>Quản lý danh mục và trạng thái hiển thị.</p>
            </div>
            <x-public.button href="{{ route('admin.categories.create') }}">Thêm danh mục</x-public.button>
        </div>

        @if (session('success'))
            <p class="category-notice category-notice--success" role="status">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="category-notice category-notice--error" role="alert">{{ session('error') }}</p>
        @endif

        <div class="category-panel">
            <form method="GET" action="{{ route('admin.categories.index') }}" class="category-search" role="search">
                <label for="category-search">Tìm danh mục theo tên</label>
                <div class="category-search__controls">
                    <input id="category-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập tên danh mục">
                    <x-public.button type="submit">Tìm kiếm</x-public.button>
                    @if ($search !== '')
                        <x-public.button href="{{ route('admin.categories.index') }}" variant="outline">Xóa lọc</x-public.button>
                    @endif
                </div>
            </form>

            @if ($categories->isEmpty())
                <div class="category-empty">
                    @if ($search !== '')
                        <h2>Không tìm thấy danh mục</h2>
                        <p>Thử một tên khác hoặc xóa bộ lọc để xem toàn bộ danh mục.</p>
                        <x-public.button href="{{ route('admin.categories.index') }}" variant="outline">Xem tất cả</x-public.button>
                    @else
                        <h2>Chưa có danh mục</h2>
                        <p>Thêm danh mục đầu tiên để bắt đầu quản lý thực đơn.</p>
                        <x-public.button href="{{ route('admin.categories.create') }}" variant="outline">Thêm danh mục</x-public.button>
                    @endif
                </div>
            @else
                <div class="category-table-wrap">
                    <table class="category-table">
                        <caption class="sr-only">Danh sách danh mục</caption>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Tên</th>
                                <th scope="col">Mô tả</th>
                                <th scope="col">Thứ tự</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td data-label="ID">{{ $category->id }}</td>
                                    <th scope="row" data-label="Tên">{{ $category->name }}</th>
                                    <td data-label="Mô tả" class="category-description">{{ $category->description !== null && $category->description !== '' ? $category->description : '—' }}</td>
                                    <td data-label="Thứ tự">{{ $category->display_order }}</td>
                                    <td data-label="Trạng thái">
                                        <span class="category-status category-status--{{ $category->status }}">{{ $category->status === 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</span>
                                    </td>
                                    <td data-label="Thao tác">
                                        <div class="category-actions">
                                            <x-public.button href="{{ route('admin.categories.edit', $category) }}" variant="outline">Sửa</x-public.button>
                                            <form method="POST" action="{{ route('admin.categories.toggle-status', $category) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-public.button type="submit" variant="outline">{{ $category->status === 'active' ? 'Ngừng hoạt động' : 'Kích hoạt' }}</x-public.button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');">
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
                @if ($categories->hasPages())
                    <div class="category-pagination">{{ $categories->links() }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
