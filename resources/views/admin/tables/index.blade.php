@extends('layouts.internal')
@section('title', 'Quản lý bàn')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div><p class="section-heading__eyebrow">Maison du Café · Quản trị</p><h1 id="page-title">Quản lý bàn</h1><p>Danh sách bàn và khu vực của quán.</p></div>
            <x-public.button href="{{ route('admin.tables.create') }}">Thêm bàn</x-public.button>
        </div>
        @if (session('success')) <p class="category-notice category-notice--success" role="status">{{ session('success') }}</p> @endif
        @if ($errors->any())
            <div class="category-notice category-notice--error" role="alert">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif
        <div class="category-panel">
            <form method="GET" action="{{ route('admin.tables.index') }}" class="category-search" role="search">
                <div class="category-fields">
                    <div class="category-field">
                        <label for="search">Tìm theo mã bàn</label>
                        <input id="search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="20" @error('search') aria-invalid="true" aria-describedby="search-error" @enderror>
                        @error('search') <p id="search-error" class="category-field__error">{{ $message }}</p> @enderror
                    </div>
                    <div class="category-field">
                        <label for="area-filter">Khu vực</label>
                        <select id="area-filter" name="area_id" @error('area_id') aria-invalid="true" aria-describedby="area-filter-error" @enderror>
                            <option value="">Tất cả khu vực</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" @selected((string) ($filters['area_id'] ?? '') === (string) $area->id)>{{ $area->name }}{{ $area->trashed() ? ' (đã xóa mềm)' : '' }}</option>
                            @endforeach
                        </select>
                        @error('area_id') <p id="area-filter-error" class="category-field__error">{{ $message }}</p> @enderror
                    </div>
                    <div class="category-field">
                        <label for="status-filter">Trạng thái</label>
                        <select id="status-filter" name="status" @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                            <option value="">Tất cả trạng thái</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <p id="status-error" class="category-field__error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="category-form-actions"><x-public.button type="submit">Tìm kiếm / Lọc</x-public.button><x-public.button href="{{ route('admin.tables.index') }}" variant="outline">Xóa lọc</x-public.button></div>
            </form>
            @if ($tables->isEmpty())
                <div class="category-empty">
                    @if (($filters['search'] ?? '') !== '' || !empty($filters['area_id']) || !empty($filters['status']))
                        <h2>Không tìm thấy bàn</h2><p>Thử mã bàn hoặc bộ lọc khác.</p>
                    @else
                        <h2>Chưa có bàn</h2><p>Thêm bàn để bắt đầu quản lý.</p>
                    @endif
                </div>
            @else
                <div class="category-table-wrap product-table-wrap">
                    <table class="category-table">
                        <caption class="sr-only">Danh sách bàn</caption>
                        <thead><tr><th scope="col">ID</th><th scope="col">Mã bàn</th><th scope="col">Tên bàn</th><th scope="col">Khu vực</th><th scope="col">Sức chứa</th><th scope="col">Trạng thái</th><th scope="col">Ngày tạo</th><th scope="col">Thao tác</th></tr></thead>
                        <tbody>
                            @foreach ($tables as $table)
                                <tr>
                                    <td data-label="ID">{{ $table->id }}</td>
                                    <th scope="row" data-label="Mã bàn">{{ $table->table_code }}</th>
                                    <td data-label="Tên bàn">{{ $table->table_name ?? '—' }}</td>
                                    <td data-label="Khu vực">{{ $table->area?->name ?? 'Không tìm thấy khu vực' }}</td>
                                    <td data-label="Sức chứa">{{ $table->capacity }}</td>
                                    <td data-label="Trạng thái"><span class="category-status">{{ $statuses[$table->status] ?? $table->status }}</span></td>
                                    <td data-label="Ngày tạo">{{ $table->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td data-label="Thao tác"><div class="category-actions">
                                        @foreach ($transitions[$table->status] ?? [] as $target)
                                            <form method="POST" action="{{ route('admin.tables.status', $table) }}" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái bàn này? Hệ thống sẽ kiểm tra đơn hàng và đặt bàn trước khi lưu.');">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="expected_status" value="{{ $table->status }}">
                                                <input type="hidden" name="status" value="{{ $target }}">
                                                <x-public.button type="submit" variant="outline" aria-label="Chuyển bàn {{ $table->table_code }} sang {{ $statuses[$target] }}">{{ $target === 'maintenance' ? 'Bảo trì' : ($table->status === 'occupied' ? 'Giải phóng bàn' : 'Mở lại bàn') }}</x-public.button>
                                            </form>
                                        @endforeach
                                        <x-public.button href="{{ route('admin.tables.edit', $table) }}" variant="outline" aria-label="Sửa bàn {{ $table->table_code }}">Sửa</x-public.button>
                                        <form method="POST" action="{{ route('admin.tables.destroy', $table) }}" onsubmit="return confirm('Bạn có chắc muốn xóa mềm bàn này? Chỉ xóa khi không có khách, đơn pending hoặc đặt bàn còn hiệu lực. Lịch sử được giữ nguyên.');">
                                            @csrf
                                            @method('DELETE')
                                            <x-public.button type="submit" variant="outline" class="category-delete" aria-label="Xóa bàn {{ $table->table_code }}">Xóa</x-public.button>
                                        </form>
                                    </div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($tables->hasPages()) <div class="category-pagination">{{ $tables->links() }}</div> @endif
            @endif
        </div>
    </section>
@endsection
