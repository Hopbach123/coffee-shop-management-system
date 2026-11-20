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

@if ($categories->isEmpty())
    <div class="sneat-alert sneat-alert--danger category-notice category-notice--error" role="alert" style="margin-bottom: 24px;">
        <p>Cần có danh mục trước khi lưu sản phẩm. <a href="{{ route('admin.categories.create') }}" style="text-decoration: underline; font-weight: 600;">Thêm danh mục ngay</a>.</p>
    </div>
@elseif ($product?->category?->trashed())
    <div class="sneat-alert sneat-alert--danger category-notice category-notice--error" role="alert" style="margin-bottom: 24px;">
        <p>Danh mục hiện tại đã bị xóa. Vui lòng chọn danh mục khác trước khi lưu.</p>
    </div>
@endif

<div class="cafe-form-layout">
    <div class="cafe-upload-box">
        <div class="cafe-upload-preview" id="product-preview-box">
            @if ($product && $imageUrl)
                <img src="{{ $imageUrl }}" alt="Ảnh của {{ $product->name }}">
            @else
                <x-internal.icon name="coffee" size="44" style="color: var(--cafe-caramel);" />
            @endif
        </div>
        <label for="image" class="cafe-upload-btn" style="cursor: pointer;">
            <x-internal.icon name="upload" size="15" />
            <span>{{ $product && $imageUrl ? 'Đổi ảnh khác' : 'Tải ảnh lên' }}</span>
        </label>
        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="v5-upload-input" onchange="if(this.files[0]){ const r=new FileReader(); r.onload=e=>{ document.getElementById('product-preview-box').innerHTML='<img src=\''+e.target.result+'\' style=\'width:100%;height:100%;object-fit:cover;\'>'; }; r.readAsDataURL(this.files[0]); }" @error('image') aria-invalid="true" aria-describedby="image-error" @enderror>
        <p class="cafe-upload-help product-image-help">JPG, PNG hoặc WebP • Tối đa 5MB</p>
        @error('image') <p class="category-field__error" id="image-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
    </div>

    <div class="cafe-form-fields category-fields">
        <div class="cafe-form-group category-field">
            <label for="name">Tên sản phẩm <span class="required" aria-hidden="true">*</span></label>
            <input id="name" name="name" type="text" value="{{ $catalogOld('name', $product?->name ?? '') }}" maxlength="255" required placeholder="Nhập tên sản phẩm..." class="cafe-input" @error('name') aria-invalid="true" aria-describedby="name-error" style="border-color: var(--cafe-danger);" @enderror>
            @error('name') <p class="category-field__error" id="name-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
        </div>

        <div class="cafe-form-group category-field">
            <label for="category_id">Danh mục <span class="required" aria-hidden="true">*</span></label>
            <select id="category_id" name="category_id" required class="cafe-select" @error('category_id') aria-invalid="true" aria-describedby="category_id-error" style="border-color: var(--cafe-danger);" @enderror>
                <option value="">Chọn danh mục</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $catalogOld('category_id', $product?->category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id') <p class="category-field__error" id="category_id-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
        </div>

        <div class="cafe-form-group category-field">
            <label for="description">Mô tả sản phẩm</label>
            <textarea id="description" name="description" rows="3" placeholder="Nhập mô tả chi tiết về hương vị, thành phần..." class="cafe-textarea" @error('description') aria-invalid="true" aria-describedby="description-error" style="border-color: var(--cafe-danger);" @enderror>{{ $catalogOld('description', $product?->description ?? '') }}</textarea>
            @error('description') <p class="category-field__error" id="description-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
        </div>

        <div class="v5-form-grid">
            <div class="cafe-form-group category-field">
                <label for="status">Trạng thái <span class="required" aria-hidden="true">*</span></label>
                <select id="status" name="status" required class="cafe-select" @error('status') aria-invalid="true" aria-describedby="status-error" style="border-color: var(--cafe-danger);" @enderror>
                    <option value="available" @selected($catalogOld('status', $product?->status ?? 'available') === 'available')>Có sẵn</option>
                    <option value="unavailable" @selected($catalogOld('status', $product?->status ?? 'available') === 'unavailable')>Tạm hết</option>
                </select>
                @error('status') <p class="category-field__error" id="status-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>

            <div class="cafe-form-group category-field">
                <label for="is_featured">Sản phẩm nổi bật <span class="required" aria-hidden="true">*</span></label>
                <select id="is_featured" name="is_featured" required class="cafe-select" @error('is_featured') aria-invalid="true" aria-describedby="is_featured-error" style="border-color: var(--cafe-danger);" @enderror>
                    <option value="0" @selected((string) $catalogOld('is_featured', $product?->is_featured ? '1' : '0') === '0')>Không</option>
                    <option value="1" @selected((string) $catalogOld('is_featured', $product?->is_featured ? '1' : '0') === '1')>Có (Hiển thị đầu menu)</option>
                </select>
                @error('is_featured') <p class="category-field__error" id="is_featured-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</div>

<div class="cafe-form-actions category-form-actions">
    <a href="{{ route('admin.products.index') }}" class="cafe-btn-secondary">Hủy</a>
    <button type="submit" class="cafe-btn-primary">
        <x-internal.icon name="check" size="16" />
        <span>{{ $product ? 'Cập nhật sản phẩm' : 'Lưu sản phẩm' }}</span>
    </button>
</div>
