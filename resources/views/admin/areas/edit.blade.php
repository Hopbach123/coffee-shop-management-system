@extends('layouts.internal')
@section('title', 'Chỉnh sửa khu vực')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng">
            <a href="{{ route('admin.areas.index') }}" style="color:var(--cafe-text-secondary);text-decoration:none;">Khu vực</a>
            <span>Chỉnh sửa</span>
        </nav>
        <h1 class="cafe-page-title" id="page-title">Chỉnh sửa khu vực</h1>
        <p class="cafe-page-subtitle">Cập nhật tên và mô tả khu vực. Trạng thái được quản lý riêng.</p>
    </div>
</div>

<div class="cafe-card" style="max-width:680px;padding:1.5rem;">
    <form method="POST" action="{{ route('admin.areas.update', $area) }}">
        @csrf
        @method('PUT')
        @if ($errors->any())
            <div class="cafe-notice cafe-notice--error" role="alert">Vui lòng kiểm tra thông tin bên dưới.</div>
            @error('area') <p class="cafe-field-error" role="alert">{{ $message }}</p> @enderror
        @endif
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <div>
                <label for="name" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Tên khu vực *</label>
                <input id="name" class="cafe-input" name="name" type="text" value="{{ old('name', $area->name) }}" maxlength="100" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name') <p class="cafe-field-error" id="name-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="description" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Mô tả</label>
                <textarea id="description" class="cafe-textarea" name="description" rows="4" @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description', $area->description) }}</textarea>
                @error('description') <p class="cafe-field-error" id="description-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div style="display:flex;gap:.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--cafe-border);">
            <button type="submit" class="cafe-btn-primary">Chỉnh sửa khu vực</button>
            <a href="{{ route('admin.areas.index') }}" class="cafe-btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
