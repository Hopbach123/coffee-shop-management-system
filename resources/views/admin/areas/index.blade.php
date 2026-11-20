@extends('layouts.internal')
@section('title', 'Quản lý khu vực')
@section('content')
<section class="category-page area-overview" aria-labelledby="page-title">
    <header class="category-heading">
        <div>
            <p class="section-heading__eyebrow">Maison du Café · Không gian quán</p>
            <h1 id="page-title">Quản lý khu vực</h1>
            <p>Quan sát không gian, tình trạng bàn và quản lý từng khu vực.</p>
        </div>
        <x-public.button href="{{ route('admin.areas.create') }}"><x-internal.icon name="plus" size="18" /> Thêm khu vực</x-public.button>
    </header>
    <form method="GET" action="{{ route('admin.areas.index') }}" class="sneat-card area-filters" role="search" aria-label="Lọc khu vực">
        <div class="category-field">
            <label for="area-search">Tìm khu vực theo tên</label>
            <input id="area-search" type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Nhập tên khu vực" @error('search') aria-invalid="true" aria-describedby="area-search-error" @enderror>
            @error('search') <p class="category-field__error" id="area-search-error">{{ $message }}</p> @enderror
        </div>
        <div class="category-field">
            <label for="area-status">Trạng thái khu vực</label>
            <select id="area-status" name="status" @error('status') aria-invalid="true" aria-describedby="area-status-error" @enderror>
                <option value="">Tất cả trạng thái</option>
                <option value="active" @selected($status === 'active')>Đang hoạt động</option>
                <option value="inactive" @selected($status === 'inactive')>Ngừng hoạt động</option>
            </select>
            @error('status') <p class="category-field__error" id="area-status-error">{{ $message }}</p> @enderror
        </div>
        <div class="area-filter-actions">
            <x-public.button type="submit"><x-internal.icon name="search" size="18" /> Tìm kiếm</x-public.button>
            @if ($search !== '' || $status !== '')
                <x-public.button href="{{ route('admin.areas.index') }}" variant="outline">Xóa lọc</x-public.button>
            @endif
        </div>
    </form>
    @if ($areas === null || $summary === null)
        <div class="sneat-card category-empty" role="alert">
            <h2>Chưa thể tải khu vực</h2>
            <p>Dữ liệu thống kê hiện chưa khả dụng. Vui lòng thử lại sau.</p>
            <x-public.button href="{{ route('admin.areas.index', ['search' => $search, 'status' => $status]) }}" variant="outline">Thử lại</x-public.button>
        </div>
    @else
        @include('admin.tables.components.status-freshness')
        <section aria-label="Tổng quan theo bộ lọc">
            <p class="area-scope">Thống kê trạng thái hiển thị theo bộ lọc, trên tất cả các trang. Chỉ tính khu vực và bàn chưa xóa; bàn trong khu vực ngừng hoạt động được tính là Chưa xác định.</p>
            <dl class="area-summary">
                @foreach (['areas_count' => 'Tổng khu vực', 'tables_count' => 'Tổng số bàn', 'available_count' => 'Trống', 'occupied_count' => 'Có khách', 'reserved_count' => 'Đã đặt', 'maintenance_count' => 'Bảo trì', 'conflict_count' => 'Cần kiểm tra', 'unknown_count' => 'Chưa xác định'] as $key => $label)
                    <div class="sneat-card area-summary-item"><dt>{{ $label }}</dt><dd>{{ number_format($summary->$key) }}</dd></div>
                @endforeach
            </dl>
        </section>
        <div class="area-results-heading"><h2>Không gian của quán</h2><p>Mới nhất trước · {{ $areas->total() }} khu vực</p></div>
        @if ($areas->isEmpty())
            <div class="sneat-card category-empty">
                <x-internal.icon name="layers" size="36" />
                @if ($search !== '' || $status !== '' || $areas->total() > 0)
                    <h2>Không tìm thấy khu vực</h2><p>Thử tên khác hoặc xóa bộ lọc. Nếu đang ở trang cũ, hãy về trang đầu.</p>
                    <x-public.button href="{{ route('admin.areas.index') }}" variant="outline">Xem tất cả</x-public.button>
                @else
                    <h2>Chưa có khu vực</h2><p>Thêm khu vực để lưu thông tin không gian quán.</p>
                @endif
            </div>
        @else
            <div class="area-grid">
                @foreach ($areas as $area)
                    <article class="sneat-card area-card" aria-labelledby="area-{{ $area->id }}">
                        <header class="area-card-heading">
                            <span class="area-card-icon"><x-internal.icon name="layers" size="24" /></span>
                            <span class="category-status category-status--{{ $area->status }}">{{ $area->status === 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</span>
                        </header>
                        <h3 id="area-{{ $area->id }}">{{ $area->name }}</h3>
                        <p class="area-description">{{ $area->description ?? '—' }}</p>
                        <p class="area-table-total"><x-internal.icon name="table" size="20" /> <strong>{{ $area->tables_count }}</strong> bàn trong khu vực</p>
                        <dl class="area-table-counts">
                            @foreach ($displayLabels as $key => $label)
                                <div><dt>{{ $label }}</dt><dd>{{ $area->{$key.'_count'} }}</dd></div>
                            @endforeach
                        </dl>
                        @if ($area->tables_count === 0)<p>Chưa có bàn trong khu vực.</p>@endif
                        <p class="area-created">Ngày tạo: {{ $area->created_at?->format('d/m/Y H:i') ?? '—' }}</p>
                        <x-public.button :href="route('admin.tables.index', ['area_id' => $area->id, 'view' => 'floor'])" variant="outline" aria-label="Xem bàn khu vực {{ $area->name }}">Xem bàn <x-internal.icon name="arrow" size="17" /></x-public.button>
                        <div class="area-card-actions">
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
                        <small>Chỉ xóa được khu vực không có bàn liên kết, kể cả bàn đã xóa mềm.</small>
                    </article>
                @endforeach
            </div>
            @if ($areas->hasPages())<div class="category-pagination">{{ $areas->links() }}</div>@endif
        @endif
    @endif
</section>
@endsection
