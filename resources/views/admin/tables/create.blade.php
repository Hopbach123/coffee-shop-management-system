@extends('layouts.internal')
@section('title', 'Thêm bàn')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng">
            <a href="{{ route('admin.tables.index') }}" style="color:var(--cafe-text-secondary);text-decoration:none;">Quản lý bàn</a>
            <span>Thêm mới</span>
        </nav>
        <h1 class="cafe-page-title" id="page-title">Thêm bàn</h1>
        <p class="cafe-page-subtitle">Bàn mới có trạng thái Bàn trống. Chọn khu vực đang hoạt động.</p>
    </div>
</div>

<div class="cafe-card" style="max-width:680px;padding:1.5rem;">
    @if ($areas->isEmpty())
        <div class="cafe-notice cafe-notice--error" role="alert">Chưa có khu vực đang hoạt động. Hãy tạo hoặc kích hoạt khu vực trước khi thêm bàn.</div>
    @endif
    <form method="POST" action="{{ route('admin.tables.store') }}">
        @csrf
        @if ($errors->any())
            <div class="cafe-notice cafe-notice--error" role="alert">Vui lòng kiểm tra thông tin bên dưới.</div>
            @error('table') <p class="cafe-field-error" role="alert">{{ $message }}</p> @enderror
        @endif
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <div>
                <label for="area_id" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Khu vực *</label>
                <select id="area_id" class="cafe-select" name="area_id" required @error('area_id') aria-invalid="true" aria-describedby="area-error" @enderror>
                    <option value="">Chọn khu vực</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected((string) old('area_id') === (string) $area->id)>{{ $area->name }}</option>
                    @endforeach
                </select>
                @error('area_id') <p id="area-error" class="cafe-field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="table_code" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Mã bàn *</label>
                <input id="table_code" class="cafe-input" name="table_code" type="text" value="{{ old('table_code') }}" maxlength="20" required @error('table_code') aria-invalid="true" aria-describedby="code-error" @enderror placeholder="T01, B02…">
                @error('table_code') <p id="code-error" class="cafe-field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="table_name" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Tên bàn</label>
                <input id="table_name" class="cafe-input" name="table_name" type="text" value="{{ old('table_name') }}" maxlength="100" @error('table_name') aria-invalid="true" aria-describedby="table-name-error" @enderror placeholder="Bàn 1 gần cửa sổ">
                @error('table_name') <p id="table-name-error" class="cafe-field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="capacity" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Sức chứa *</label>
                <input id="capacity" class="cafe-input" name="capacity" type="number" step="1" min="1" max="2147483647" value="{{ old('capacity', 4) }}" required @error('capacity') aria-invalid="true" aria-describedby="capacity-error" @enderror>
                @error('capacity') <p id="capacity-error" class="cafe-field-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div style="display:flex;gap:.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--cafe-border);">
            <button type="submit" class="cafe-btn-primary" @disabled($areas->isEmpty())>Thêm bàn</button>
            <a href="{{ route('admin.tables.index') }}" class="cafe-btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
