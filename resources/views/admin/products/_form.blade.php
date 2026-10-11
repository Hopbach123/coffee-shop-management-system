@if ($errors->any())
    <div class="category-notice category-notice--error" role="alert">
        <p>Vui lòng kiểm tra các trường được đánh dấu bên dưới.</p>
    </div>
@endif

@if ($categories->isEmpty())
    <div class="category-notice category-notice--error" role="alert">
        <p>Cần có danh mục trước khi lưu sản phẩm. <a href="{{ route('admin.categories.create') }}">Thêm danh mục</a>.</p>
    </div>
@elseif ($product?->category?->trashed())
    <div class="category-notice category-notice--error" role="alert">
        <p>Danh mục hiện tại đã bị xóa. Vui lòng chọn danh mục khác trước khi lưu.</p>
    </div>
@endif

<div class="category-fields">
    <div class="category-field">
        <label for="category_id">Danh mục <span aria-hidden="true">*</span></label>
        <select id="category_id" name="category_id" required @error('category_id') aria-invalid="true" aria-describedby="category_id-error" @enderror>
            <option value="">Chọn danh mục</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id') <p class="category-field__error" id="category_id-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="name">Tên sản phẩm <span aria-hidden="true">*</span></label>
        <input id="name" name="name" type="text" value="{{ old('name', $product?->name ?? '') }}" maxlength="255" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        @error('name') <p class="category-field__error" id="name-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="description">Mô tả</label>
        <textarea id="description" name="description" rows="4" @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description', $product?->description ?? '') }}</textarea>
        @error('description') <p class="category-field__error" id="description-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="status">Trạng thái <span aria-hidden="true">*</span></label>
        <select id="status" name="status" required @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
            <option value="available" @selected(old('status', $product?->status ?? 'available') === 'available')>Có sẵn</option>
            <option value="unavailable" @selected(old('status', $product?->status ?? 'available') === 'unavailable')>Tạm hết</option>
        </select>
        @error('status') <p class="category-field__error" id="status-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="is_featured">Nổi bật <span aria-hidden="true">*</span></label>
        <select id="is_featured" name="is_featured" required @error('is_featured') aria-invalid="true" aria-describedby="is_featured-error" @enderror>
            <option value="0" @selected((string) old('is_featured', $product?->is_featured ? '1' : '0') === '0')>Không</option>
            <option value="1" @selected((string) old('is_featured', $product?->is_featured ? '1' : '0') === '1')>Có</option>
        </select>
        @error('is_featured') <p class="category-field__error" id="is_featured-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="category-form-actions">
    <x-public.button type="submit">Lưu sản phẩm</x-public.button>
    <x-public.button href="{{ route('admin.products.index') }}" variant="outline">Hủy</x-public.button>
</div>
