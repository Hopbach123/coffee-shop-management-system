@extends('layouts.internal')
@section('title', 'Chỉnh sửa bàn')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Chỉnh sửa bàn</h1>
        <p>Giữ nguyên trạng thái và QR. Giữ khu vực hiện tại hoặc chọn khu vực đang hoạt động khi chuyển bàn.</p>
        @if ($areas->isEmpty())
            <p class="category-notice category-notice--error" role="alert">Không có khu vực hợp lệ để cập nhật bàn.</p>
        @endif
        <form method="POST" action="{{ route('admin.tables.update', $table) }}" class="category-panel category-form">
            @csrf
            @method('PUT')
            @if ($errors->any())
                <div class="category-notice category-notice--error" role="alert">Vui lòng kiểm tra thông tin bên dưới.</div>
                @error('table') <p class="category-field__error" role="alert">{{ $message }}</p> @enderror
            @endif
            <div class="category-fields">
                <div class="category-field">
                    <label for="area_id">Khu vực *</label>
                    <select id="area_id" name="area_id" required @error('area_id') aria-invalid="true" aria-describedby="area-error" @enderror>
                        <option value="">Chọn khu vực</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" @selected((string) old('area_id', $table->area_id) === (string) $area->id)>{{ $area->name }}</option>
                        @endforeach
                    </select>
                    @error('area_id') <p id="area-error" class="category-field__error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="table_code">Mã bàn *</label>
                    <input id="table_code" name="table_code" type="text" value="{{ old('table_code', $table->table_code) }}" maxlength="20" required @error('table_code') aria-invalid="true" aria-describedby="code-error" @enderror>
                    @error('table_code') <p id="code-error" class="category-field__error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="table_name">Tên bàn</label>
                    <input id="table_name" name="table_name" type="text" value="{{ old('table_name', $table->table_name) }}" maxlength="100" @error('table_name') aria-invalid="true" aria-describedby="table-name-error" @enderror>
                    @error('table_name') <p id="table-name-error" class="category-field__error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="capacity">Sức chứa *</label>
                    <input id="capacity" name="capacity" type="number" step="1" min="1" max="2147483647" value="{{ old('capacity', $table->capacity) }}" required @error('capacity') aria-invalid="true" aria-describedby="capacity-error" @enderror>
                    @error('capacity') <p id="capacity-error" class="category-field__error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="category-form-actions">
                <x-public.button type="submit" :disabled="$areas->isEmpty()">Chỉnh sửa bàn</x-public.button>
                <x-public.button href="{{ route('admin.tables.index') }}" variant="outline">Hủy</x-public.button>
            </div>
        </form>
    </section>
@endsection
