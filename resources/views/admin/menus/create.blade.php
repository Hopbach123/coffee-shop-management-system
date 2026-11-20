@extends('layouts.internal')
@section('title', 'Thêm menu')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng">
            <a href="{{ route('admin.menus.index') }}" style="color:var(--cafe-text-secondary);text-decoration:none;">Quản lý menu</a>
            <span>Thêm mới</span>
        </nav>
        <h1 class="cafe-page-title" id="page-title">Thêm menu</h1>
        <p class="cafe-page-subtitle">Menu được bật khi tạo. Chọn cha đang tắt vẫn lưu được; nhánh đó sẽ không hiển thị khi Sidebar động được triển khai.</p>
    </div>
</div>

<div class="cafe-card" style="max-width:680px;padding:1.5rem;">
    <form method="POST" action="{{ route('admin.menus.store') }}">
        @csrf
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <div>
                <label for="name" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Tên menu *</label>
                <input id="name" class="cafe-input" name="name" value="{{ old('name') }}" maxlength="100" required placeholder="Tên hiển thị menu…">
                @error('name')<p class="cafe-field-error">{{ $message }}</p>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label for="type" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Loại *</label>
                    <select id="type" class="cafe-select" name="type" required>
                        <option value="group" @selected(old('type', 'group') === 'group')>Nhóm</option>
                        <option value="link" @selected(old('type') === 'link')>Liên kết</option>
                    </select>
                    @error('type')<p class="cafe-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="parent_id" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Menu cha</label>
                    <select id="parent_id" class="cafe-select" name="parent_id">
                        <option value="">Menu gốc</option>
                        @foreach($parents as $row)
                            <option value="{{ $row['menu']->id }}" @selected((string) old('parent_id') === (string) $row['menu']->id)>{{ str_repeat('↳ ', $row['depth'] - 1) }}{{ $row['menu']->name }}{{ $row['menu']->is_active ? '' : ' (đang tắt)' }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')<p class="cafe-field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="route_name" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Route (chỉ cho liên kết)</label>
                <select id="route_name" class="cafe-select" name="route_name">
                    <option value="">Không chọn — dùng cho nhóm</option>
                    @foreach($routes as $name)
                        <option value="{{ $name }}" @selected(old('route_name') === $name)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('route_name')<p class="cafe-field-error">{{ $message }}</p>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label for="icon" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Biểu tượng</label>
                    <select id="icon" class="cafe-select" name="icon">
                        <option value="">Không có</option>
                        @foreach($icons as $icon)
                            <option value="{{ $icon }}" @selected(old('icon') === $icon)>{{ $icon }}</option>
                        @endforeach
                    </select>
                    @error('icon')<p class="cafe-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sort_order" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Thứ tự (mặc định 0)</label>
                    <input id="sort_order" class="cafe-input" name="sort_order" type="number" min="0" max="4294967295" step="1" value="{{ old('sort_order', 0) }}">
                    @error('sort_order')<p class="cafe-field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
        <div style="display:flex;gap:.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--cafe-border);">
            <button type="submit" class="cafe-btn-primary">Thêm menu</button>
            <a href="{{ route('admin.menus.index') }}" class="cafe-btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
