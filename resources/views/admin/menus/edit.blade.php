@extends('layouts.internal')
@section('title', 'Chỉnh sửa menu')
@section('content')
<section class="category-page category-page--form" aria-labelledby="page-title">
    <h1 id="page-title">Chỉnh sửa menu: {{ $menu->name }}</h1>
    <p>Chỉ cập nhật thông tin điều hướng. Menu cha và thứ tự được giữ nguyên.</p>
    <form method="POST" action="{{ route('admin.menus.update', $menu) }}" class="category-panel category-form">
        @csrf
        @method('PUT')
        <input type="hidden" name="expected_fingerprint" value="{{ $fingerprint }}">
        <div class="category-fields">
            <div class="category-field"><label for="name">Tên menu *</label><input id="name" name="name" maxlength="100" value="{{ old('name', $menu->name) }}" required>@error('name')<p class="category-field__error">{{ $message }}</p>@enderror</div>
            <div class="category-field"><label for="type">Loại *</label><select id="type" name="type"><option value="group" @selected(old('type', $menu->type) === 'group')>Nhóm</option><option value="link" @selected(old('type', $menu->type) === 'link')>Liên kết</option></select>@error('type')<p class="category-field__error">{{ $message }}</p>@enderror</div>
            <div class="category-field"><label for="route_name">Route (để trống với nhóm)</label><select id="route_name" name="route_name"><option value="">Không có</option>@foreach($routes as $name)<option value="{{ $name }}" @selected(old('route_name', $menu->route_name) === $name)>{{ $name }}</option>@endforeach</select>@error('route_name')<p class="category-field__error">{{ $message }}</p>@enderror</div>
            <div class="category-field"><label for="icon">Biểu tượng</label><select id="icon" name="icon"><option value="">Không có</option>@foreach($icons as $icon)<option value="{{ $icon }}" @selected(old('icon', $menu->icon) === $icon)>{{ $icon }}</option>@endforeach</select>@error('icon')<p class="category-field__error">{{ $message }}</p>@enderror</div>
        </div>
        <div class="category-form-actions"><x-public.button type="submit">Lưu thay đổi</x-public.button><x-public.button href="{{ route('admin.menus.index') }}" variant="outline">Quay lại danh sách</x-public.button></div>
    </form>
</section>
@endsection
