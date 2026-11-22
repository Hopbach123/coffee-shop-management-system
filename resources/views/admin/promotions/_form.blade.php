@if ($errors->any())
    <div class="cafe-notice cafe-notice--error" role="alert">Vui lòng kiểm tra các trường được đánh dấu bên dưới.</div>
@endif

<div style="display:flex;flex-direction:column;gap:1.25rem;">
    <div class="v5-form-grid">
        <div>
            <label for="code" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Mã khuyến mãi *</label>
            <input id="code" class="cafe-input" name="code" type="text" value="{{ old('code', $promotion?->code ?? '') }}" maxlength="50" required @error('code') aria-invalid="true" aria-describedby="code-error" @enderror placeholder="SUMMER2026">
            @error('code') <p class="cafe-field-error" id="code-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="name" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Tên khuyến mãi *</label>
            <input id="name" class="cafe-input" name="name" type="text" value="{{ old('name', $promotion?->name ?? '') }}" maxlength="255" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror placeholder="Giảm giá chào hè">
            @error('name') <p class="cafe-field-error" id="name-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="description" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Mô tả</label>
        <textarea id="description" class="cafe-textarea" name="description" rows="3" @error('description') aria-invalid="true" aria-describedby="description-error" @enderror placeholder="Chi tiết điều kiện áp dụng…">{{ old('description', $promotion?->description ?? '') }}</textarea>
        @error('description') <p class="cafe-field-error" id="description-error">{{ $message }}</p> @enderror
    </div>

    <div class="promotion-image-field">
        <label for="image">Ảnh banner</label>
        <input id="image" class="cafe-input" name="image" type="file" accept="image/jpeg,image/png,image/webp" @error('image') aria-invalid="true" aria-describedby="image-error" @enderror>
        <p class="promotion-image-help">Tùy chọn. JPG, PNG hoặc WebP, tối đa 5 MB. Ảnh mới chỉ thay thế ảnh cũ sau khi lưu thành công.</p>
        @error('image') <p class="cafe-field-error" id="image-error">{{ $message }}</p> @enderror
        @if ($promotion?->imageUrl())
            <img class="promotion-image-preview" src="{{ $promotion->imageUrl() }}" alt="" aria-hidden="true">
        @endif
    </div>

    <div class="v5-form-grid">
        <div>
            <label for="discount_type" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Loại giảm giá *</label>
            <select id="discount_type" class="cafe-select" name="discount_type" data-promotion-discount-type required @error('discount_type') aria-invalid="true" aria-describedby="discount_type-error" @enderror>
                <option value="percentage" @selected(old('discount_type', $promotion?->discount_type ?? 'percentage') === 'percentage')>Phần trăm</option>
                <option value="fixed" @selected(old('discount_type', $promotion?->discount_type ?? '') === 'fixed')>Số tiền cố định</option>
            </select>
            @error('discount_type') <p class="cafe-field-error" id="discount_type-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="discount_value" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Giá trị giảm *</label>
            <input id="discount_value" class="cafe-input" name="discount_value" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('discount_value', $promotion?->discount_value ?? '') }}" required @error('discount_value') aria-invalid="true" aria-describedby="discount_value-error" @enderror>
            @error('discount_value') <p class="cafe-field-error" id="discount_value-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="v5-form-grid">
        <div data-promotion-max-discount>
            <label for="max_discount" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Giảm tối đa (₫, chỉ cho %)</label>
            <input id="max_discount" class="cafe-input" name="max_discount" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('max_discount', $promotion?->max_discount ?? '') }}" @error('max_discount') aria-invalid="true" aria-describedby="max_discount-error" @enderror>
            @error('max_discount') <p class="cafe-field-error" id="max_discount-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="minimum_order" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Giá trị đơn tối thiểu (₫) *</label>
            <input id="minimum_order" class="cafe-input" name="minimum_order" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('minimum_order', $promotion?->minimum_order ?? '0') }}" required @error('minimum_order') aria-invalid="true" aria-describedby="minimum_order-error" @enderror>
            @error('minimum_order') <p class="cafe-field-error" id="minimum_order-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="v5-form-grid">
        <div>
            <label for="start_at" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Bắt đầu *</label>
            <input id="start_at" class="cafe-input" name="start_at" type="datetime-local" value="{{ old('start_at', $promotion?->start_at?->format('Y-m-d\TH:i') ?? '') }}" required @error('start_at') aria-invalid="true" aria-describedby="start_at-error" @enderror>
            @error('start_at') <p class="cafe-field-error" id="start_at-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="end_at" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Kết thúc *</label>
            <input id="end_at" class="cafe-input" name="end_at" type="datetime-local" value="{{ old('end_at', $promotion?->end_at?->format('Y-m-d\TH:i') ?? '') }}" required @error('end_at') aria-invalid="true" aria-describedby="end_at-error" @enderror>
            @error('end_at') <p class="cafe-field-error" id="end_at-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="v5-form-grid">
        <div>
            <label for="usage_limit" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Giới hạn lượt dùng</label>
            <input id="usage_limit" class="cafe-input" name="usage_limit" type="number" min="1" step="1" value="{{ old('usage_limit', $promotion?->usage_limit ?? '') }}" @error('usage_limit') aria-invalid="true" aria-describedby="usage_limit-error" @enderror placeholder="Để trống nếu không giới hạn">
            @error('usage_limit') <p class="cafe-field-error" id="usage_limit-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="status" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Trạng thái *</label>
            <select id="status" class="cafe-select" name="status" required @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                <option value="active" @selected(old('status', $promotion?->status ?? 'active') === 'active')>Hoạt động</option>
                <option value="inactive" @selected(old('status', $promotion?->status ?? '') === 'inactive')>Tạm dừng</option>
            </select>
            @error('status') <p class="cafe-field-error" id="status-error">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div style="display:flex;gap:.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--cafe-border);">
    <button type="submit" class="cafe-btn-primary">Lưu khuyến mãi</button>
    <a href="{{ route('admin.promotions.index') }}" class="cafe-btn-secondary">Hủy</a>
</div>
