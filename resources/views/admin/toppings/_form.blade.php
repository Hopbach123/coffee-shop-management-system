@php
    $catalogOld = static function ($field, $default = '') {
        $value = old($field, $default);
        return is_scalar($value) || $value === null ? $value : $default;
    };
@endphp

@if ($errors->any())
    <div class="sneat-alert sneat-alert--danger category-notice category-notice--error" role="alert" style="margin-bottom: 24px;">
        <strong>Vui lòng kiểm tra các trường được đánh dấu bên dưới:</strong>
        <ul style="margin: 8px 0 0 18px; padding: 0;">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="cafe-form-layout">
    <div class="cafe-upload-box" style="text-align: left; padding: 20px;">
        <div style="width: 52px; height: 52px; border-radius: 12px; background: var(--cafe-cream); display: grid; place-items: center; color: var(--cafe-caramel); margin-bottom: 12px;">
            <x-internal.icon name="tag" size="28" />
        </div>
        <h4 style="font-size: 0.9375rem; font-weight: 600; color: var(--cafe-espresso); margin: 0 0 6px;">Topping & Món thêm</h4>
        <p style="font-size: 0.8125rem; color: var(--cafe-text-secondary); margin: 0 0 14px; line-height: 1.5;">
            Topping là các thành phần thêm tự chọn (Trân châu trắng, Thạch cà phê, Kem cheese...) có thể áp dụng cho các món đồ uống trên POS và Menu trực tuyến.
        </p>
    </div>

    <div class="cafe-form-fields category-fields">
        <div class="cafe-form-group category-field">
            <label for="name">Tên topping <span class="required" aria-hidden="true">*</span></label>
            <input id="name" name="name" type="text" value="{{ $catalogOld('name', $topping?->name ?? '') }}" maxlength="255" required placeholder="VD: Trân châu đường đen, Thạch phô mai..." class="cafe-input" @error('name') aria-invalid="true" aria-describedby="name-error" style="border-color: var(--cafe-danger);" @enderror>
            @error('name') <p class="category-field__error" id="name-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
        </div>

        <div class="v5-form-grid">
            <div class="cafe-form-group category-field">
                <label for="price">Giá bán (₫) <span class="required" aria-hidden="true">*</span></label>
                <input id="price" name="price" type="number" min="0" max="9999999999.99" step="0.01" value="{{ $catalogOld('price', $topping?->price ?? '0') }}" required placeholder="0" class="cafe-input" @error('price') aria-invalid="true" aria-describedby="price-error" style="border-color: var(--cafe-danger);" @enderror>
                @error('price') <p class="category-field__error" id="price-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>

            <div class="cafe-form-group category-field">
                <label for="status">Trạng thái <span class="required" aria-hidden="true">*</span></label>
                <select id="status" name="status" required class="cafe-select" @error('status') aria-invalid="true" aria-describedby="status-error" style="border-color: var(--cafe-danger);" @enderror>
                    <option value="available" @selected($catalogOld('status', $topping?->status ?? 'available') === 'available')>Có sẵn</option>
                    <option value="unavailable" @selected($catalogOld('status', $topping?->status ?? 'available') === 'unavailable')>Tạm hết</option>
                </select>
                @error('status') <p class="category-field__error" id="status-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</div>

<div class="cafe-form-actions category-form-actions">
    <a href="{{ route('admin.toppings.index') }}" class="cafe-btn-secondary">Hủy</a>
    <button type="submit" class="cafe-btn-primary">
        <x-internal.icon name="check" size="16" />
        <span>{{ $topping ? 'Cập nhật topping' : 'Lưu topping' }}</span>
    </button>
</div>
