@extends('layouts.internal')
@section('title', 'Quản lý menu')
@section('content')
<section class="category-page" aria-labelledby="page-title">
    <h1 id="page-title">Quản lý menu</h1>
    <p>Dữ liệu điều hướng quản trị. Menu mới chưa xuất hiện trong Sidebar hiện tại.</p>
    <x-public.button href="{{ route('admin.menus.create') }}">Thêm menu</x-public.button>
    @if(count($rows))
        <section class="menu-sort category-panel" data-menu-sort data-url="{{ route('admin.menus.reorder') }}" data-token="{{ csrf_token() }}" data-revision="{{ $treeFingerprint }}" hidden>
            <h2>Sắp xếp cấu trúc menu</h2>
            <p>Kéo nút ↕ lên một menu để đặt trước nó cùng cấp; thả vào nút “Vào nhóm” để chuyển nhóm. Có thể dùng chọn cha và nút Lên/Xuống bằng bàn phím. Thay đổi chỉ được lưu khi bấm Lưu cấu trúc.</p>
            <script type="application/json" data-sort-items>@json($sortItems)</script>
            <button type="button" data-drop-root>Thả vào đây để chuyển về gốc</button>
            <ol data-sort-list></ol>
            <button type="button" data-sort-save>Lưu cấu trúc</button>
            <button type="button" data-sort-reset>Hủy thay đổi</button>
            <a href="{{ route('admin.menus.index') }}">Tải lại dữ liệu mới nhất</a>
            <p role="status" aria-live="polite" data-sort-message></p>
        </section>
    @endif
    <div class="category-table-wrap">
        <table class="category-table">
            <caption class="sr-only">Danh sách menu theo cấp và thứ tự</caption>
            <thead><tr><th>Tên / cấp</th><th>Biểu tượng</th><th>Loại</th><th>Route</th><th>Menu cha</th><th>Thứ tự</th><th>Trạng thái</th><th>Quyền tham chiếu</th><th>Thao tác</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td data-label="Tên / cấp">{{ str_repeat('↳ ', min($row['depth'] - 1, 3)) }}{{ $row['menu']->name }} <small>(Cấp {{ $row['depth'] }})</small>@if($row['invalid']) <strong>Cần kiểm tra cấu trúc</strong>@endif</td>
                    <td data-label="Biểu tượng">@if(in_array($row['menu']->icon, config('admin_menu.icons'), true))<x-internal.icon :name="$row['menu']->icon" />@endif {{ $row['menu']->icon ?? '—' }}</td>
                    <td data-label="Loại">{{ $row['menu']->type === 'group' ? 'Nhóm' : 'Liên kết' }}</td>
                    <td data-label="Route">{{ $row['menu']->route_name ?? '—' }}</td>
                    <td data-label="Menu cha">{{ $row['parent_name'] ?? 'Gốc / không có cha' }}</td>
                    <td data-label="Thứ tự">{{ $row['menu']->sort_order }}</td>
                    <td data-label="Trạng thái">{{ $row['menu']->is_active ? 'Đang bật' : 'Đang tắt' }}</td>
                    <td data-label="Quyền tham chiếu">{{ $row['menu']->permission_key ?? 'Không gán' }}</td>
                    <td data-label="Thao tác">
                        <div class="category-actions">
                            <x-public.button href="{{ route('admin.menus.edit', $row['menu']) }}" variant="outline">Sửa</x-public.button>
                            <form method="POST" action="{{ route('admin.menus.status', $row['menu']) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="expected_fingerprint" value="{{ $row['fingerprint'] }}">
                                <input type="hidden" name="is_active" value="{{ $row['menu']->is_active ? 0 : 1 }}">
                                <x-public.button type="submit" variant="outline">{{ $row['menu']->is_active ? 'Tắt' : 'Bật' }}</x-public.button>
                            </form>
                        </div>
                        <details>
                            <summary>Xóa menu</summary>
                            <form method="POST" action="{{ route('admin.menus.destroy', $row['menu']) }}">
                                @csrf @method('DELETE')
                                <input type="hidden" name="expected_fingerprint" value="{{ $row['fingerprint'] }}">
                                <p>Xóa vĩnh viễn menu “{{ $row['menu']->name }}”? Không xóa menu con hoặc menu hệ thống.</p>
                                <label><input type="checkbox" name="confirm_delete" value="1" required> Tôi xác nhận xóa menu này.</label>
                                <x-public.button type="submit" variant="outline">Xác nhận xóa</x-public.button>
                            </form>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">Chưa có Menu nào.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
