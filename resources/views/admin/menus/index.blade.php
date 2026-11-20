@extends('layouts.internal')
@section('title', 'Quản lý menu')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng"><span>Quản trị</span><span>Quản lý menu</span></nav>
        <h1 class="cafe-page-title">Quản lý menu</h1>
        <p class="cafe-page-subtitle">Dữ liệu menu cũ. Sidebar hiện dùng menu cố định theo chức năng; thay đổi tại đây không ảnh hưởng điều hướng Admin.</p>
    </div>
    <a href="{{ route('admin.menus.create') }}" class="cafe-btn-primary">
        <x-internal.icon name="plus" size="16" /> Thêm menu
    </a>
</div>

@if(count($rows))
    <section class="cafe-card" data-menu-sort data-url="{{ route('admin.menus.reorder') }}" data-token="{{ csrf_token() }}" data-revision="{{ $treeFingerprint }}" hidden style="padding:1.25rem;margin-bottom:1rem;">
        <h2 style="font-size:1rem;font-weight:700;color:var(--cafe-text-primary);margin-bottom:.5rem;">Sắp xếp cấu trúc menu</h2>
        <p style="font-size:.875rem;color:var(--cafe-text-secondary);margin-bottom:.75rem;">Kéo nút ↕ lên một menu để đặt trước nó cùng cấp; thả vào nút "Vào nhóm" để chuyển nhóm. Có thể dùng chọn cha và nút Lên/Xuống bằng bàn phím. Thay đổi chỉ được lưu khi bấm Lưu cấu trúc.</p>
        <script type="application/json" data-sort-items>@json($sortItems)</script>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem;">
            <button type="button" data-drop-root class="cafe-btn-secondary">Thả vào đây để chuyển về gốc</button>
            <button type="button" data-sort-save class="cafe-btn-primary">Lưu cấu trúc</button>
            <button type="button" data-sort-reset class="cafe-btn-secondary">Hủy thay đổi</button>
            <a href="{{ route('admin.menus.index') }}" class="cafe-btn-secondary">Tải lại dữ liệu mới nhất</a>
        </div>
        <ol data-sort-list></ol>
        <p role="status" aria-live="polite" data-sort-message style="font-size:.875rem;color:var(--cafe-text-secondary);"></p>
    </section>
@endif

<div class="cafe-card">
    <div class="cafe-table-wrap">
        <table class="cafe-table">
            <caption class="sr-only">Danh sách menu theo cấp và thứ tự</caption>
            <thead>
                <tr>
                    <th scope="col">Tên / cấp</th>
                    <th scope="col">Biểu tượng</th>
                    <th scope="col">Loại</th>
                    <th scope="col">Route</th>
                    <th scope="col">Menu cha</th>
                    <th scope="col">Thứ tự</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Quyền tham chiếu</th>
                    <th scope="col">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td data-label="Tên / cấp">{{ str_repeat('↳ ', min($row['depth'] - 1, 3)) }}{{ $row['menu']->name }} <small>(Cấp {{ $row['depth'] }})</small>@if($row['invalid']) <span style="color:var(--cafe-danger);font-weight:600;"> Cần kiểm tra cấu trúc</span>@endif</td>
                        <td data-label="Biểu tượng">
                            @if(in_array($row['menu']->icon, config('admin_menu.icons'), true))
                                <x-internal.icon :name="$row['menu']->icon" />
                            @endif
                            {{ $row['menu']->icon ?? '—' }}
                        </td>
                        <td data-label="Loại">{{ $row['menu']->type === 'group' ? 'Nhóm' : 'Liên kết' }}</td>
                        <td data-label="Route"><code style="font-size:.8rem;">{{ $row['menu']->route_name ?? '—' }}</code></td>
                        <td data-label="Menu cha">{{ $row['parent_name'] ?? 'Gốc / không có cha' }}</td>
                        <td data-label="Thứ tự">{{ $row['menu']->sort_order }}</td>
                        <td data-label="Trạng thái">
                            <span class="cafe-badge {{ $row['menu']->is_active ? 'cafe-badge--success' : 'cafe-badge--warning' }}">
                                {{ $row['menu']->is_active ? 'Đang bật' : 'Đang tắt' }}
                            </span>
                        </td>
                        <td data-label="Quyền tham chiếu"><code style="font-size:.8rem;">{{ $row['menu']->permission_key ?? 'Không gán' }}</code></td>
                        <td data-label="Thao tác">
                            <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.5rem;">
                                <a href="{{ route('admin.menus.edit', $row['menu']) }}" class="cafe-btn-icon" aria-label="Sửa menu {{ $row['menu']->name }}">
                                    <x-internal.icon name="edit" size="15" />
                                </a>
                                <form method="POST" action="{{ route('admin.menus.status', $row['menu']) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="expected_fingerprint" value="{{ $row['fingerprint'] }}">
                                    <input type="hidden" name="is_active" value="{{ $row['menu']->is_active ? 0 : 1 }}">
                                    <button type="submit" class="cafe-btn-secondary" style="font-size:.8rem;padding:.3rem .6rem;">
                                        {{ $row['menu']->is_active ? 'Tắt' : 'Bật' }}
                                    </button>
                                </form>
                            </div>
                            <details style="font-size:.85rem;">
                                <summary style="cursor:pointer;color:var(--cafe-danger);font-weight:600;">Xóa menu</summary>
                                <form method="POST" action="{{ route('admin.menus.destroy', $row['menu']) }}" style="margin-top:.5rem;padding:.75rem;background:var(--cafe-surface-alt);border-radius:8px;border:1px solid var(--cafe-danger-border);">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="expected_fingerprint" value="{{ $row['fingerprint'] }}">
                                    <p style="font-size:.85rem;margin-bottom:.5rem;color:var(--cafe-text-primary);">Xóa vĩnh viễn menu "{{ $row['menu']->name }}"? Không xóa menu con hoặc menu hệ thống.</p>
                                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;margin-bottom:.5rem;">
                                        <input type="checkbox" name="confirm_delete" value="1" required> Tôi xác nhận xóa menu này.
                                    </label>
                                    <button type="submit" class="cafe-btn-secondary" style="font-size:.8rem;padding:.3rem .6rem;border-color:var(--cafe-danger);color:var(--cafe-danger);">Xác nhận xóa</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:2.5rem;color:var(--cafe-text-secondary);">Chưa có Menu nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
