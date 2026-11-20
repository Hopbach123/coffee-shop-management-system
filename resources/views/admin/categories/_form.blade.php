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
    <div class="cafe-upload-box">
        <div class="cafe-upload-preview">
            @if ($category?->image)
                <img src="{{ asset($category->image) }}" alt="{{ $category->name }}">
            @else
                <x-internal.icon name="coffee" size="44" style="color: var(--cafe-caramel);" />
            @endif
        </div>
        <p class="cafe-upload-help">Thông tin danh mục</p>
    </div>

    <div class="cafe-form-fields category-fields">
        <div class="cafe-form-group category-field">
            <label for="name">Tên danh mục <span class="required" aria-hidden="true">*</span></label>
            <input id="name" name="name" type="text" value="{{ $catalogOld('name', $category?->name ?? '') }}" maxlength="255" required placeholder="Nhập tên danh mục..." class="cafe-input" @error('name') aria-invalid="true" aria-describedby="name-error" style="border-color: var(--cafe-danger);" @enderror>
            @error('name') <p class="category-field__error" id="name-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
        </div>

        <div class="cafe-form-group category-field">
            <label for="description">Mô tả</label>
            <textarea id="description" name="description" rows="3" placeholder="Nhập mô tả danh mục..." class="cafe-textarea" @error('description') aria-invalid="true" aria-describedby="description-error" style="border-color: var(--cafe-danger);" @enderror>{{ $catalogOld('description', $category?->description ?? '') }}</textarea>
            @error('description') <p class="category-field__error" id="description-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
        </div>

        <div class="v5-form-grid">
            <div class="cafe-form-group category-field">
                <label for="display_order">Thứ tự hiển thị</label>
                <input id="display_order" name="display_order" type="number" step="1" min="-2147483648" max="2147483647" value="{{ $catalogOld('display_order', $category?->display_order ?? 0) }}" class="cafe-input" @error('display_order') aria-invalid="true" aria-describedby="display_order-error" style="border-color: var(--cafe-danger);" @enderror>
                @error('display_order') <p class="category-field__error" id="display_order-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>

            <div class="cafe-form-group category-field">
                <label for="status">Trạng thái <span class="required" aria-hidden="true">*</span></label>
                <select id="status" name="status" required class="cafe-select" @error('status') aria-invalid="true" aria-describedby="status-error" style="border-color: var(--cafe-danger);" @enderror>
                    <option value="active" @selected($catalogOld('status', $category?->status ?? 'active') === 'active')>Đang hoạt động</option>
                    <option value="inactive" @selected($catalogOld('status', $category?->status ?? 'active') === 'inactive')>Ngừng hoạt động</option>
                </select>
                @error('status') <p class="category-field__error" id="status-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</div>

<div class="cafe-form-actions category-form-actions">
    <a href="{{ route('admin.categories.index') }}" class="cafe-btn-secondary">Hủy</a>
    <button type="submit" class="cafe-btn-primary">
        <x-internal.icon name="check" size="16" />
        <span>{{ $category ? 'Cập nhật danh mục' : 'Lưu danh mục' }}</span>
    </button>
</div>
