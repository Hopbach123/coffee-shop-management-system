@extends('layouts.internal')
@section('title', 'Thêm khu vực')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Thêm khu vực</h1>
        <p>Nhập tên khu vực và mô tả tùy chọn.</p>
        <form method="POST" action="{{ route('admin.areas.store') }}" class="category-panel category-form">
            @csrf
            @if ($errors->any())
                <div class="category-notice category-notice--error" role="alert">Vui lòng kiểm tra thông tin bên dưới.</div>
                @error('area') <p class="category-field__error" role="alert">{{ $message }}</p> @enderror
            @endif
            <div class="category-fields">
                <div class="category-field">
                    <label for="name">Tên khu vực *</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="100" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name') <p class="category-field__error" id="name-error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="description">Mô tả</label>
                    <textarea id="description" name="description" rows="4" @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description') }}</textarea>
                    @error('description') <p class="category-field__error" id="description-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="category-form-actions">
                <x-public.button type="submit">Thêm khu vực</x-public.button>
                <x-public.button href="{{ route('admin.areas.index') }}" variant="outline">Hủy</x-public.button>
            </div>
        </form>
    </section>
@endsection
