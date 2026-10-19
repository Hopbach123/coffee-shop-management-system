@extends('layouts.internal')
@section('title', 'Khu vực')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div>
                <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
                <h1 id="page-title">Khu vực</h1>
                <p>Danh sách khu vực của quán.</p>
            </div>
            <x-public.button href="{{ route('admin.areas.create') }}">Thêm khu vực</x-public.button>
        </div>
        @if (session('success'))
            <p class="category-notice category-notice--success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div class="category-notice category-notice--error" role="alert">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif
        <div class="category-panel">
            <form method="GET" action="{{ route('admin.areas.index') }}" class="category-search" role="search">
                <label for="area-search">Tìm khu vực theo tên</label>
                <div class="category-search__controls">
                    <input id="area-search" type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Nhập tên khu vực" @error('search') aria-invalid="true" aria-describedby="area-search-error" @enderror>
                    <x-public.button type="submit">Tìm kiếm</x-public.button>
                    @if ($search !== '')
                        <x-public.button href="{{ route('admin.areas.index') }}" variant="outline">Xóa lọc</x-public.button>
                    @endif
                </div>
                @error('search') <p class="category-field__error" id="area-search-error" role="alert">{{ $message }}</p> @enderror
            </form>
            @if ($areas->isEmpty())
                <div class="category-empty">
                    @if ($search !== '')
                        <h2>Không tìm thấy khu vực</h2>
                        <p>Thử tên khác hoặc xóa bộ lọc.</p>
                        <x-public.button href="{{ route('admin.areas.index') }}" variant="outline">Xem tất cả</x-public.button>
                    @else
                        <h2>Chưa có khu vực</h2>
                        <p>Thêm khu vực để lưu thông tin không gian quán.</p>
                    @endif
                </div>
            @else
                <div class="category-table-wrap product-table-wrap">
                    <table class="category-table">
                        <caption class="sr-only">Danh sách khu vực</caption>
                        <thead><tr>
                            <th scope="col">ID</th><th scope="col">Tên khu vực</th><th scope="col">Mô tả</th><th scope="col">Trạng thái</th><th scope="col">Ngày tạo</th>
                            <th scope="col">Thao tác</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($areas as $area)
                                <tr>
                                    <td data-label="ID">{{ $area->id }}</td>
                                    <th scope="row" data-label="Tên khu vực">{{ $area->name }}</th>
                                    <td data-label="Mô tả" class="category-description">{{ $area->description ?? '—' }}</td>
                                    <td data-label="Trạng thái"><span class="category-status category-status--{{ $area->status }}">{{ $area->status === 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</span></td>
                                    <td data-label="Ngày tạo">{{ $area->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td data-label="Thao tác">
                                        <div class="category-actions">
                                            <x-public.button href="{{ route('admin.areas.edit', $area) }}" variant="outline" aria-label="Sửa khu vực {{ $area->id }}">Sửa</x-public.button>
                                            <form method="POST" action="{{ route('admin.areas.status', $area) }}" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái khu vực này? Trạng thái bàn và giao dịch được giữ nguyên.');">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $area->status === 'active' ? 'inactive' : 'active' }}">
                                                <x-public.button type="submit" variant="outline" aria-label="Đổi trạng thái khu vực {{ $area->id }}">{{ $area->status === 'active' ? 'Vô hiệu hóa' : 'Kích hoạt' }}</x-public.button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.areas.destroy', $area) }}" onsubmit="return confirm('Bạn có chắc muốn xóa mềm khu vực này? Chỉ khu vực không có bàn liên kết mới được xóa.');">
                                                @csrf
                                                @method('DELETE')
                                                <x-public.button type="submit" variant="outline" class="category-delete" aria-label="Xóa khu vực {{ $area->id }}">Xóa</x-public.button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($areas->hasPages())
                    <div class="category-pagination">{{ $areas->links() }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
