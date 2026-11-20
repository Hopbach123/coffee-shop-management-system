@extends('layouts.internal')
@section('title', 'Nhập kho nguyên liệu')
@section('content')
<section class="category-page" aria-labelledby="page-title">
    <div class="cafe-page-header">
        <div>
            <div class="cafe-breadcrumbs">
                <a href="{{ route('admin.home') }}">Dashboard</a>
                <span>›</span>
                <a href="{{ route('admin.ingredients.index') }}">Kho & Nguyên liệu</a>
                <span>›</span>
                <span>Nhập kho</span>
            </div>
            <h1 id="page-title" class="cafe-page-title">Nhập kho · {{ $ingredient->name }}</h1>
            <p class="cafe-page-subtitle">
                Tồn hiện tại: <strong style="color: var(--cafe-espresso);">{{ $ingredient->current_stock }} {{ $ingredient->unit }}</strong>. Nhập theo đơn vị này, không tự quy đổi.
            </p>
        </div>
        <div>
            <a href="{{ route('admin.ingredients.history', $ingredient) }}" class="cafe-btn-secondary">
                Lịch sử kho
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.ingredients.import.store', $ingredient) }}" class="cafe-card category-panel category-form" style="max-width: 680px;">
        @csrf
        <input type="hidden" name="operation_token" value="{{ is_string(old('operation_token')) ? old('operation_token') : $operationToken }}">
        @error('operation_token')
            <div class="sneat-alert sneat-alert--danger cafe-notice" role="alert" style="margin-bottom: 20px;">
                <p class="category-field__error">{{ $message }}</p>
                <a href="{{ route('admin.ingredients.import.create', $ingredient) }}" style="text-decoration: underline; font-weight: 600;">Mở phiếu mới</a>
            </div>
        @enderror

        <div class="cafe-form-fields category-fields">
            <div class="cafe-form-group category-field">
                <label for="quantity">Số lượng nhập ({{ $ingredient->unit }}) <span class="required" aria-hidden="true">*</span></label>
                <input id="quantity" name="quantity" type="number" min="0.001" max="999999999.999" step="0.001" required placeholder="VD: 5, 10, 20..." value="{{ is_scalar(old('quantity')) ? old('quantity') : '' }}" class="cafe-input" @error('quantity') aria-invalid="true" aria-describedby="quantity-error" style="border-color: var(--cafe-danger);" @enderror>
                @error('quantity')<p class="category-field__error" id="quantity-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p>@enderror
            </div>

            <div class="cafe-form-group category-field">
                <label for="note">Ghi chú / lý do nhập</label>
                <textarea id="note" name="note" rows="3" maxlength="1000" placeholder="VD: Nhập thêm từ nhà cung cấp theo hóa đơn #123..." class="cafe-textarea" @error('note') aria-invalid="true" aria-describedby="note-error" style="border-color: var(--cafe-danger);" @enderror>{{ is_scalar(old('note')) ? old('note') : '' }}</textarea>
                @error('note')<p class="category-field__error" id="note-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p>@enderror
            </div>
        </div>

        <div style="background: var(--cafe-cream); border: 1px solid var(--cafe-border); border-radius: 8px; padding: 12px 14px; margin-top: 20px; font-size: 0.8125rem; color: var(--cafe-text-secondary); line-height: 1.4;">
            Kiểm tra số lượng trước khi lưu. Phiếu đã ghi nhận chỉ được xem trong lịch sử; việc điều chỉnh phải có chứng từ riêng.
        </div>

        <div class="cafe-form-actions category-form-actions">
            <a href="{{ route('admin.ingredients.index') }}" class="cafe-btn-secondary">Hủy</a>
            <button type="submit" class="cafe-btn-primary">
                <x-internal.icon name="check" size="16" />
                <span>Ghi nhận nhập kho</span>
            </button>
        </div>
    </form>
</section>
@endsection
