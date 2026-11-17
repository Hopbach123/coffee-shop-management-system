@extends('layouts.internal')
@section('title', 'Sơ đồ bàn')
@section('content')
<section class="category-page floor-plan" aria-labelledby="page-title">
    <header class="category-heading">
        <div><p class="section-heading__eyebrow">Maison du Café · Không gian quán</p><h1 id="page-title">Sơ đồ bàn</h1><p>Quan sát tình trạng bàn theo khu vực.</p></div>
        <div class="floor-plan-actions">
            <x-public.button href="{{ route('admin.areas.index') }}" variant="outline">Khu vực</x-public.button>
            <x-public.button href="{{ route('admin.tables.index') }}" variant="outline">Quản lý bàn</x-public.button>
        </div>
    </header>
    @if ($tables === null || $areas === null || $summary === null)
        <div class="sneat-card category-empty" role="alert"><h2>Chưa thể tải sơ đồ bàn</h2><p>Dữ liệu hiện chưa khả dụng. Vui lòng thử lại sau.</p>
            <x-public.button :href="route('admin.tables.index', array_merge($filters, ['view' => 'floor']))" variant="outline">Thử lại</x-public.button>
        </div>
    @else
        @include('admin.tables.components.status-freshness')
        <nav class="sneat-card floor-areas" aria-label="Chọn khu vực">
            <p>Số bàn bên cạnh khu vực là tổng bàn chưa xóa, không áp dụng tìm kiếm hoặc lọc trạng thái.</p>
            <div class="floor-area-links">
                <a href="{{ route('admin.tables.index', array_merge($filters, ['view' => 'floor', 'area_id' => null])) }}" @if (empty($filters['area_id'])) aria-current="page" @endif>Tất cả khu vực</a>
                @foreach ($areas as $area)
                    <a href="{{ route('admin.tables.index', array_merge($filters, ['view' => 'floor', 'area_id' => $area->id])) }}" @if ((string) ($filters['area_id'] ?? '') === (string) $area->id) aria-current="page" @endif>{{ $area->name }} <span>({{ $area->cafe_tables_count }})</span>{{ $area->status !== 'active' ? ' · Ngừng hoạt động' : '' }}</a>
                @endforeach
            </div>
        </nav>
        <form method="GET" action="{{ route('admin.tables.index') }}" class="sneat-card floor-filters" role="search" aria-label="Tìm và lọc bàn">
            <input type="hidden" name="view" value="floor">
            <div class="category-field">
                <label for="floor-search">Tìm mã hoặc tên bàn</label>
                <input id="floor-search" name="search" type="search" maxlength="100" value="{{ $filters['search'] ?? '' }}" placeholder="Nhập mã hoặc tên bàn" @error('search') aria-invalid="true" aria-describedby="floor-search-error" @enderror>
                @error('search')<p id="floor-search-error" class="category-field__error">{{ $message }}</p>@enderror
            </div>
            <div class="category-field">
                <label for="floor-area">Khu vực</label>
                <select id="floor-area" name="area_id" @error('area_id') aria-invalid="true" aria-describedby="floor-area-error" @enderror>
                    <option value="">Tất cả khu vực</option>
                    @foreach ($areas as $area)<option value="{{ $area->id }}" @selected((string) ($filters['area_id'] ?? '') === (string) $area->id)>{{ $area->name }}{{ $area->status !== 'active' ? ' · Ngừng hoạt động' : '' }}</option>@endforeach
                </select>
                @error('area_id')<p id="floor-area-error" class="category-field__error">{{ $message }}</p>@enderror
            </div>
            <div class="category-field">
                <label for="floor-status">Trạng thái bàn</label>
                <select id="floor-status" name="status" @error('status') aria-invalid="true" aria-describedby="floor-status-error" @enderror>
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach
                </select>
                @error('status')<p id="floor-status-error" class="category-field__error">{{ $message }}</p>@enderror
            </div>
            <div class="floor-plan-actions"><x-public.button type="submit">Tìm kiếm / Lọc</x-public.button><x-public.button href="{{ route('admin.tables.index', ['view' => 'floor']) }}" variant="outline">Xóa lọc</x-public.button></div>
        </form>
        @include('admin.tables.components.status-summary')
        <div class="floor-results"><h2>Bàn trong khu vực</h2><p>Mới nhất trước · {{ $tables->total() }} bàn</p></div>
        <p class="floor-integration-note">Sơ đồ hiển thị trạng thái tổng hợp từ dữ liệu đã lưu. Chọn Xem chi tiết để mở thông tin bàn; mở sơ đồ hoặc chi tiết không giữ chỗ hoặc thay đổi trạng thái bàn.</p>
        @if ($tables->isEmpty())
            <div class="sneat-card category-empty">
                @if ($areas->isEmpty())
                    <h2>Chưa có khu vực</h2><p>Quản lý khu vực để thiết lập không gian quán.</p>
                @elseif (!empty($filters['search']) || !empty($filters['status']) || $tables->total() > 0)
                    <h2>Không tìm thấy bàn</h2><p>Thử bộ lọc khác hoặc quay về trang đầu.</p>
                @elseif (!empty($filters['area_id']))
                    <h2>Khu vực chưa có bàn</h2><p>Chọn khu vực khác hoặc mở Quản lý bàn.</p>
                @else
                    <h2>Chưa có bàn</h2><p>Mở Quản lý bàn để thêm bàn vào khu vực.</p>
                @endif
            </div>
        @else
            <div class="floor-table-grid">
                @foreach ($tables as $table)
                    @include('admin.tables.components.table-card')
                @endforeach
            </div>
            @if ($tables->hasPages())<div class="category-pagination">{{ $tables->links() }}</div>@endif
        @endif
    @endif
</section>
@endsection
