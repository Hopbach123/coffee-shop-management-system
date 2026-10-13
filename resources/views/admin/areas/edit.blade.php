@extends('layouts.internal')
@section('title', 'Chỉnh sửa khu vực')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Chỉnh sửa khu vực</h1>
        <p>Cập nhật tên và mô tả khu vực. Trạng thái được quản lý riêng.</p>
        <form method="POST" action="{{ route('admin.areas.update', $area) }}" class="category-panel category-form">
            @csrf
            @method('PUT')
            @if ($errors->any())
                <div class="category-notice category-notice--error" role="alert">Vui lòng kiểm tra thông tin bên dưới.</div>
                @error('area') <p class="category-field__error" role="alert">{{ $message }}</p> @enderror
            @endif
            <div class="category-fields">
                <div class="category-field">
                    <label for="name">Tên khu vực *</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $area->name) }}" maxlength="100" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name') <p class="category-field__error" id="name-error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="description">Mô tả</label>
                    <textarea id="description" name="description" rows="4" @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description', $area->description) }}</textarea>
                    @error('description') <p class="category-field__error" id="description-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="category-form-actions">
                <x-public.button type="submit">Chỉnh sửa khu vực</x-public.button>
                <x-public.button href="{{ route('admin.areas.index') }}" variant="outline">Hủy</x-public.button>
            </div>
        </form>
    </section>
@endsection
