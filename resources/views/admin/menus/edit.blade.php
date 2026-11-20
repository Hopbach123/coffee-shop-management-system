@extends('layouts.internal')
@section('title', 'Chỉnh sửa menu')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng">
            <a href="{{ route('admin.menus.index') }}" style="color:var(--cafe-text-secondary);text-decoration:none;">Quản lý menu</a>
            <span>Chỉnh sửa</span>
        </nav>
        <h1 class="cafe-page-title" id="page-title">Chỉnh sửa menu: {{ $menu->name }}</h1>
        <p class="cafe-page-subtitle">Chỉ cập nhật thông tin điều hướng. Menu cha và thứ tự được giữ nguyên.</p>
    </div>
</div>

<div class="cafe-card" style="max-width:680px;padding:1.5rem;">
    <form method="POST" action="{{ route('admin.menus.update', $menu) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="expected_fingerprint" value="{{ $fingerprint }}">
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <div>
                <label for="name" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Tên menu *</label>
                <input id="name" class="cafe-input" name="name" maxlength="100" value="{{ old('name', $menu->name) }}" required>
                @error('name')<p class="cafe-field-error">{{ $message }}</p>@enderror
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label for="type" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Loại *</label>
                    <select id="type" class="cafe-select" name="type">
                        <option value="group" @selected(old('type', $menu->type) === 'group')>Nhóm</option>
                        <option value="link" @selected(old('type', $menu->type) === 'link')>Liên kết</option>
                    </select>
                    @error('type')<p class="cafe-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="icon" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Biểu tượng</label>
                    <select id="icon" class="cafe-select" name="icon">
                        <option value="">Không có</option>
                        @foreach($icons as $icon)
                            <option value="{{ $icon }}" @selected(old('icon', $menu->icon) === $icon)>{{ $icon }}</option>
                        @endforeach
                    </select>
                    @error('icon')<p class="cafe-field-error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="route_name" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Route (để trống với nhóm)</label>
                <select id="route_name" class="cafe-select" name="route_name">
                    <option value="">Không có</option>
                    @foreach($routes as $name)
                        <option value="{{ $name }}" @selected(old('route_name', $menu->route_name) === $name)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('route_name')<p class="cafe-field-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div style="display:flex;gap:.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--cafe-border);">
            <button type="submit" class="cafe-btn-primary">Lưu thay đổi</button>
            <a href="{{ route('admin.menus.index') }}" class="cafe-btn-secondary">Quay lại danh sách</a>
        </div>
    </form>
</div>
@endsection
