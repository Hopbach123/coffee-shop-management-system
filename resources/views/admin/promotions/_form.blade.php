@if ($errors->any())
    <div class="category-notice category-notice--error" role="alert">Vui lòng kiểm tra các trường được đánh dấu bên dưới.</div>
@endif

<div class="category-fields">
    <div class="category-field">
        <label for="code">Mã khuyến mãi *</label>
        <input id="code" name="code" type="text" value="{{ old('code', $promotion?->code ?? '') }}" maxlength="50" required @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
        @error('code') <p class="category-field__error" id="code-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="name">Tên khuyến mãi *</label>
        <input id="name" name="name" type="text" value="{{ old('name', $promotion?->name ?? '') }}" maxlength="255" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        @error('name') <p class="category-field__error" id="name-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="description">Mô tả</label>
        <textarea id="description" name="description" rows="3" @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description', $promotion?->description ?? '') }}</textarea>
        @error('description') <p class="category-field__error" id="description-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="discount_type">Loại giảm giá *</label>
        <select id="discount_type" name="discount_type" data-promotion-discount-type required @error('discount_type') aria-invalid="true" aria-describedby="discount_type-error" @enderror>
            <option value="percentage" @selected(old('discount_type', $promotion?->discount_type ?? 'percentage') === 'percentage')>Phần trăm</option>
            <option value="fixed" @selected(old('discount_type', $promotion?->discount_type ?? '') === 'fixed')>Số tiền cố định</option>
        </select>
        @error('discount_type') <p class="category-field__error" id="discount_type-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="discount_value">Giá trị giảm *</label>
        <input id="discount_value" name="discount_value" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('discount_value', $promotion?->discount_value ?? '') }}" required @error('discount_value') aria-invalid="true" aria-describedby="discount_value-error" @enderror>
        @error('discount_value') <p class="category-field__error" id="discount_value-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field" data-promotion-max-discount>
        <label for="max_discount">Giảm tối đa (₫, chỉ áp dụng cho phần trăm)</label>
        <input id="max_discount" name="max_discount" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('max_discount', $promotion?->max_discount ?? '') }}" @error('max_discount') aria-invalid="true" aria-describedby="max_discount-error" @enderror>
        @error('max_discount') <p class="category-field__error" id="max_discount-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="minimum_order">Giá trị đơn tối thiểu (₫) *</label>
        <input id="minimum_order" name="minimum_order" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('minimum_order', $promotion?->minimum_order ?? '0') }}" required @error('minimum_order') aria-invalid="true" aria-describedby="minimum_order-error" @enderror>
        @error('minimum_order') <p class="category-field__error" id="minimum_order-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="start_at">Bắt đầu *</label>
        <input id="start_at" name="start_at" type="datetime-local" value="{{ old('start_at', $promotion?->start_at?->format('Y-m-d\TH:i') ?? '') }}" required @error('start_at') aria-invalid="true" aria-describedby="start_at-error" @enderror>
        @error('start_at') <p class="category-field__error" id="start_at-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="end_at">Kết thúc *</label>
        <input id="end_at" name="end_at" type="datetime-local" value="{{ old('end_at', $promotion?->end_at?->format('Y-m-d\TH:i') ?? '') }}" required @error('end_at') aria-invalid="true" aria-describedby="end_at-error" @enderror>
        @error('end_at') <p class="category-field__error" id="end_at-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="usage_limit">Giới hạn lượt dùng (để trống nếu không giới hạn)</label>
        <input id="usage_limit" name="usage_limit" type="number" min="1" step="1" value="{{ old('usage_limit', $promotion?->usage_limit ?? '') }}" @error('usage_limit') aria-invalid="true" aria-describedby="usage_limit-error" @enderror>
        @error('usage_limit') <p class="category-field__error" id="usage_limit-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="status">Trạng thái *</label>
        <select id="status" name="status" required @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
            <option value="active" @selected(old('status', $promotion?->status ?? 'active') === 'active')>Hoạt động</option>
            <option value="inactive" @selected(old('status', $promotion?->status ?? '') === 'inactive')>Tạm dừng</option>
        </select>
        @error('status') <p class="category-field__error" id="status-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="category-form-actions">
    <x-public.button type="submit">Lưu khuyến mãi</x-public.button>
    <x-public.button href="{{ route('admin.promotions.index') }}" variant="outline">Hủy</x-public.button>
</div>
