@extends('layouts.internal')
@section('title', 'Điều chỉnh và kiểm kê kho')
@section('content')
<section class="category-page" aria-labelledby="page-title">
    <div class="cafe-page-header">
        <div>
            <div class="cafe-breadcrumbs">
                <a href="{{ route('admin.home') }}">Dashboard</a>
                <span>›</span>
                <a href="{{ route('admin.ingredients.index') }}">Kho & Nguyên liệu</a>
                <span>›</span>
                <span>Điều chỉnh / Kiểm kê</span>
            </div>
            <h1 id="page-title" class="cafe-page-title">Điều chỉnh / kiểm kê · {{ $ingredient->name }}</h1>
            <p class="cafe-page-subtitle">
                Tồn hệ thống: <strong style="color: var(--cafe-espresso);">{{ $ingredient->current_stock }} {{ $ingredient->unit }}</strong>. Không quy đổi đơn vị.
            </p>
        </div>
        <div>
            <a href="{{ route('admin.ingredients.history', $ingredient) }}" class="cafe-btn-secondary">
                Lịch sử kho
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.ingredients.adjustment.store', $ingredient) }}" class="cafe-card category-panel category-form" onsubmit="return confirm('Xác nhận ghi phiếu? Tồn kho sẽ thay đổi và lịch sử không được sửa/xóa.');" style="max-width: 720px;">
        @csrf
        <input type="hidden" name="operation_token" value="{{ is_string(old('operation_token')) ? old('operation_token') : $operationToken }}">
        @error('operation_token')
            <div class="sneat-alert sneat-alert--danger cafe-notice" role="alert" style="margin-bottom: 20px;">
                <p class="category-field__error">{{ $message }}</p>
                <a href="{{ route('admin.ingredients.adjustment.create', $ingredient) }}" style="text-decoration: underline; font-weight: 600;">Mở phiếu mới / cập nhật tồn để kiểm kê lại</a>
            </div>
        @else
            <p style="margin-bottom: 20px; font-size: 0.8125rem;">
                <a href="{{ route('admin.ingredients.adjustment.create', $ingredient) }}" style="color: var(--cafe-caramel); font-weight: 600; text-decoration: underline;">Mở phiếu mới / cập nhật tồn để kiểm kê lại</a>
            </p>
        @enderror

        <div class="cafe-form-fields category-fields">
            <div class="cafe-form-group category-field">
                <label for="mode">Nghiệp vụ <span class="required" aria-hidden="true">*</span></label>
                <select id="mode" name="mode" required aria-describedby="mode-help @error('mode') mode-error @enderror" class="cafe-select" @error('mode') aria-invalid="true" style="border-color: var(--cafe-danger);" @enderror>
                    <option value="">Chọn nghiệp vụ</option>
                    @foreach (['increase' => 'Điều chỉnh tăng', 'decrease' => 'Điều chỉnh giảm', 'waste' => 'Hao hụt / hư hỏng', 'stocktake' => 'Kiểm kê — nhập tồn thực tế'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('mode') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p id="mode-help" style="font-size: 0.75rem; color: var(--cafe-text-secondary); margin-top: 4px; line-height: 1.4;">
                    Tăng/giảm/hao hụt: nhập lượng biến động lớn hơn 0. Kiểm kê: nhập tổng tồn thực tế (được bằng 0); hệ thống tính chênh lệch. Tồn thay đổi trong lúc kiểm kê thì phải mở lại phiếu.
                </p>
                @error('mode')<p id="mode-error" class="category-field__error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p>@enderror
            </div>

            <div class="cafe-form-group category-field">
                <label for="quantity">Số lượng theo nghiệp vụ ({{ $ingredient->unit }}) <span class="required" aria-hidden="true">*</span></label>
                <input id="quantity" name="quantity" type="number" min="0" max="999999999.999" step="0.001" required value="{{ is_scalar(old('quantity')) ? old('quantity') : '' }}" placeholder="0" class="cafe-input" @error('quantity') aria-invalid="true" aria-describedby="quantity-error" style="border-color: var(--cafe-danger);" @enderror>
                @error('quantity')<p id="quantity-error" class="category-field__error" role="alert" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p>@enderror
            </div>

            <div class="cafe-form-group category-field">
                <label for="note">Lý do / kết quả kiểm kê <span class="required" aria-hidden="true">*</span></label>
                <textarea id="note" name="note" rows="3" maxlength="1000" required placeholder="Ghi rõ lý do điều chỉnh hoặc biên bản kiểm kê..." class="cafe-textarea" @error('note') aria-invalid="true" aria-describedby="note-error" style="border-color: var(--cafe-danger);" @enderror>{{ is_scalar(old('note')) ? old('note') : '' }}</textarea>
                @error('note')<p id="note-error" class="category-field__error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p>@enderror
            </div>
        </div>

        <div style="background: var(--cafe-cream); border: 1px solid var(--cafe-border); border-radius: 8px; padding: 12px 14px; margin-top: 20px; font-size: 0.8125rem; color: var(--cafe-text-secondary); line-height: 1.4;">
            Admin ghi phiếu là người xác nhận. Phiếu đã ghi chỉ xem lại trong lịch sử; nếu sai, lập phiếu điều chỉnh mới có lý do.
        </div>

        <div class="cafe-form-actions category-form-actions">
            <a href="{{ route('admin.ingredients.index') }}" class="cafe-btn-secondary">Hủy</a>
            <button type="submit" class="cafe-btn-primary">
                <x-internal.icon name="check" size="16" />
                <span>Xác nhận ghi phiếu</span>
            </button>
        </div>
    </form>
</section>
@endsection
