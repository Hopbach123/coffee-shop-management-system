@extends('layouts.internal')
@section('title', 'Thêm menu')
@section('content')
<section class="category-page category-page--form" aria-labelledby="page-title">
    <h1 id="page-title">Thêm menu</h1>
    <p>Menu được bật khi tạo. Chọn cha đang tắt vẫn lưu được; nhánh đó sẽ không hiển thị khi Sidebar động được triển khai.</p>
    <form method="POST" action="{{ route('admin.menus.store') }}" class="category-panel category-form">
        @csrf
        <div class="category-fields">
            <div class="category-field"><label for="name">Tên menu *</label><input id="name" name="name" value="{{ old('name') }}" maxlength="100" required>@error('name')<p class="category-field__error">{{ $message }}</p>@enderror</div>
            <div class="category-field"><label for="type">Loại *</label><select id="type" name="type" required><option value="group" @selected(old('type', 'group') === 'group')>Nhóm</option><option value="link" @selected(old('type') === 'link')>Liên kết</option></select>@error('type')<p class="category-field__error">{{ $message }}</p>@enderror</div>
            <div class="category-field"><label for="route_name">Route (chỉ cho liên kết)</label><select id="route_name" name="route_name"><option value="">Không chọn — dùng cho nhóm</option>@foreach($routes as $name)<option value="{{ $name }}" @selected(old('route_name') === $name)>{{ $name }}</option>@endforeach</select>@error('route_name')<p class="category-field__error">{{ $message }}</p>@enderror</div>
            <div class="category-field"><label for="parent_id">Menu cha</label><select id="parent_id" name="parent_id"><option value="">Menu gốc</option>@foreach($parents as $row)<option value="{{ $row['menu']->id }}" @selected((string) old('parent_id') === (string) $row['menu']->id)>{{ str_repeat('↳ ', $row['depth'] - 1) }}{{ $row['menu']->name }}{{ $row['menu']->is_active ? '' : ' (đang tắt)' }}</option>@endforeach</select>@error('parent_id')<p class="category-field__error">{{ $message }}</p>@enderror</div>
            <div class="category-field"><label for="icon">Biểu tượng</label><select id="icon" name="icon"><option value="">Không có</option>@foreach($icons as $icon)<option value="{{ $icon }}" @selected(old('icon') === $icon)>{{ $icon }}</option>@endforeach</select>@error('icon')<p class="category-field__error">{{ $message }}</p>@enderror</div>
            <div class="category-field"><label for="sort_order">Thứ tự (mặc định 0)</label><input id="sort_order" name="sort_order" type="number" min="0" max="4294967295" step="1" value="{{ old('sort_order', 0) }}">@error('sort_order')<p class="category-field__error">{{ $message }}</p>@enderror</div>
        </div>
        <div class="category-form-actions"><x-public.button type="submit">Thêm menu</x-public.button><x-public.button href="{{ route('admin.menus.index') }}" variant="outline">Hủy</x-public.button></div>
    </form>
</section>
@endsection
