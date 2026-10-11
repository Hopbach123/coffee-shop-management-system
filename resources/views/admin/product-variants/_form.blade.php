@if ($errors->any())
    <div class="category-notice category-notice--error" role="alert">
        <p>Vui lòng kiểm tra các trường được đánh dấu bên dưới.</p>
    </div>
@endif

@if ($products->isEmpty())
    <div class="category-notice category-notice--error" role="alert">
        <p>Cần có sản phẩm trước khi lưu biến thể. <a href="{{ route('admin.products.create') }}">Thêm sản phẩm</a>.</p>
    </div>
@elseif ($productVariant?->product?->trashed())
    <div class="category-notice category-notice--error" role="alert">
        <p>Sản phẩm hiện tại đã bị xóa. Vui lòng chọn sản phẩm khác trước khi lưu.</p>
    </div>
@endif

<div class="category-fields">
    <div class="category-field">
        <label for="product_id">Sản phẩm <span aria-hidden="true">*</span></label>
        <select id="product_id" name="product_id" required @error('product_id') aria-invalid="true" aria-describedby="product_id-error" @enderror>
            <option value="">Chọn sản phẩm</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected((string) old('product_id', $productVariant?->product_id ?? '') === (string) $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        @error('product_id') <p class="category-field__error" id="product_id-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="name">Tên biến thể <span aria-hidden="true">*</span></label>
        <input id="name" name="name" type="text" value="{{ old('name', $productVariant?->name ?? '') }}" maxlength="50" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        @error('name') <p class="category-field__error" id="name-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="sku">SKU (tùy chọn)</label>
        <input id="sku" name="sku" type="text" value="{{ old('sku', $productVariant?->sku ?? '') }}" maxlength="50" @error('sku') aria-invalid="true" aria-describedby="sku-error" @enderror>
        @error('sku') <p class="category-field__error" id="sku-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="price">Giá bán (₫) <span aria-hidden="true">*</span></label>
        <input id="price" name="price" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('price', $productVariant?->price ?? '') }}" required @error('price') aria-invalid="true" aria-describedby="price-error" @enderror>
        @error('price') <p class="category-field__error" id="price-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="status">Trạng thái <span aria-hidden="true">*</span></label>
        <select id="status" name="status" required @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
            <option value="available" @selected(old('status', $productVariant?->status ?? 'available') === 'available')>Có sẵn</option>
            <option value="unavailable" @selected(old('status', $productVariant?->status ?? 'available') === 'unavailable')>Tạm hết</option>
        </select>
        @error('status') <p class="category-field__error" id="status-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="category-form-actions">
    <x-public.button type="submit">Lưu biến thể</x-public.button>
    <x-public.button href="{{ route('admin.product-variants.index') }}" variant="outline">Hủy</x-public.button>
</div>
