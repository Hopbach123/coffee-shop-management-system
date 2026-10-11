@if ($errors->any())
    <div class="category-notice category-notice--error" role="alert">
        <p>Vui lòng kiểm tra các trường được đánh dấu bên dưới.</p>
    </div>
@endif

<div class="category-fields">
    <div class="category-field">
        <label for="name">Tên danh mục <span aria-hidden="true">*</span></label>
        <input id="name" name="name" type="text" value="{{ old('name', $category?->name ?? '') }}" maxlength="255" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        @error('name') <p class="category-field__error" id="name-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="description">Mô tả</label>
        <textarea id="description" name="description" rows="4" @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description', $category?->description ?? '') }}</textarea>
        @error('description') <p class="category-field__error" id="description-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="display_order">Thứ tự hiển thị</label>
        <input id="display_order" name="display_order" type="number" step="1" min="-2147483648" max="2147483647" value="{{ old('display_order', $category?->display_order ?? 0) }}" @error('display_order') aria-invalid="true" aria-describedby="display_order-error" @enderror>
        @error('display_order') <p class="category-field__error" id="display_order-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="status">Trạng thái <span aria-hidden="true">*</span></label>
        <select id="status" name="status" required @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
            <option value="active" @selected(old('status', $category?->status ?? 'active') === 'active')>Đang hoạt động</option>
            <option value="inactive" @selected(old('status', $category?->status ?? 'active') === 'inactive')>Ngừng hoạt động</option>
        </select>
        @error('status') <p class="category-field__error" id="status-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="category-form-actions">
    <x-public.button type="submit">Lưu danh mục</x-public.button>
    <x-public.button href="{{ route('admin.categories.index') }}" variant="outline">Hủy</x-public.button>
</div>
