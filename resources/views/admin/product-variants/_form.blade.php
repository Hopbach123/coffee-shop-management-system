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

@if ($products->isEmpty())
    <div class="sneat-alert sneat-alert--danger category-notice category-notice--error" role="alert" style="margin-bottom: 24px;">
        <p>Cần có sản phẩm trước khi lưu biến thể. <a href="{{ route('admin.products.create') }}" style="text-decoration: underline; font-weight: 600;">Thêm sản phẩm</a>.</p>
    </div>
@elseif ($productVariant?->product?->trashed())
    <div class="sneat-alert sneat-alert--danger category-notice category-notice--error" role="alert" style="margin-bottom: 24px;">
        <p>Sản phẩm hiện tại đã bị xóa. Không thể sửa hoặc chuyển biến thể sang sản phẩm khác.</p>
    </div>
@endif

<div class="cafe-form-layout">
    <div class="cafe-upload-box" style="text-align: left; padding: 20px;">
        <div style="width: 52px; height: 52px; border-radius: 12px; background: var(--cafe-cream); display: grid; place-items: center; color: var(--cafe-caramel); margin-bottom: 12px;">
            <x-internal.icon name="box" size="28" />
        </div>
        <h4 style="font-size: 0.9375rem; font-weight: 600; color: var(--cafe-espresso); margin: 0 0 6px;">Quy cách & Kích cỡ</h4>
        <p style="font-size: 0.8125rem; color: var(--cafe-text-secondary); margin: 0 0 14px; line-height: 1.5;">
            Mỗi biến thể đại diện cho một kích cỡ hoặc quy cách bán (Size S, M, L).
        </p>
        <div style="background: #ffffff; border: 1px solid var(--cafe-border); border-radius: 8px; padding: 12px; font-size: 0.75rem; color: var(--cafe-text-primary); line-height: 1.4;">
            <strong style="color: var(--cafe-caramel);">Mẹo vận hành:</strong> Sau khi lưu, bạn có thể vào mục <strong>Công thức</strong> để gắn định lượng nguyên liệu trừ kho tự động khi bán món.
        </div>
    </div>

    <div class="cafe-form-fields category-fields">
        <div class="cafe-form-group category-field">
            <label for="product_id">Sản phẩm <span class="required" aria-hidden="true">*</span></label>
            @if ($productVariant)
                <input type="hidden" name="product_id" value="{{ $productVariant->product_id }}">
            @endif
            <select id="product_id" name="product_id" required class="cafe-select" @disabled($productVariant !== null) @error('product_id') aria-invalid="true" aria-describedby="product_id-error" style="border-color: var(--cafe-danger);" @enderror>
                <option value="">Chọn sản phẩm</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected((string) $catalogOld('product_id', $productVariant?->product_id ?? '') === (string) $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
            @error('product_id') <p class="category-field__error" id="product_id-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
        </div>

        <div class="v5-form-grid">
            <div class="cafe-form-group category-field">
                <label for="name">Tên biến thể / Size <span class="required" aria-hidden="true">*</span></label>
                <input id="name" name="name" type="text" value="{{ $catalogOld('name', $productVariant?->name ?? '') }}" maxlength="50" required placeholder="VD: Size M, Size L..." class="cafe-input" @error('name') aria-invalid="true" aria-describedby="name-error" style="border-color: var(--cafe-danger);" @enderror>
                @error('name') <p class="category-field__error" id="name-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>

            <div class="cafe-form-group category-field">
                <label for="sku">Mã SKU (tùy chọn)</label>
                <input id="sku" name="sku" type="text" value="{{ $catalogOld('sku', $productVariant?->sku ?? '') }}" maxlength="50" placeholder="VD: CF-LATTE-M" class="cafe-input" @error('sku') aria-invalid="true" aria-describedby="sku-error" style="border-color: var(--cafe-danger);" @enderror>
                @error('sku') <p class="category-field__error" id="sku-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="v5-form-grid">
            <div class="cafe-form-group category-field">
                <label for="price">Giá bán (₫) <span class="required" aria-hidden="true">*</span></label>
                <input id="price" name="price" type="number" min="0" max="9999999999.99" step="0.01" value="{{ $catalogOld('price', $productVariant?->price ?? '') }}" required placeholder="0" class="cafe-input" @error('price') aria-invalid="true" aria-describedby="price-error" style="border-color: var(--cafe-danger);" @enderror>
                @error('price') <p class="category-field__error" id="price-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>

            <div class="cafe-form-group category-field">
                <label for="status">Trạng thái <span class="required" aria-hidden="true">*</span></label>
                <select id="status" name="status" required class="cafe-select" @error('status') aria-invalid="true" aria-describedby="status-error" style="border-color: var(--cafe-danger);" @enderror>
                    <option value="available" @selected($catalogOld('status', $productVariant?->status ?? 'available') === 'available')>Có sẵn</option>
                    <option value="unavailable" @selected($catalogOld('status', $productVariant?->status ?? 'available') === 'unavailable')>Tạm hết</option>
                </select>
                @error('status') <p class="category-field__error" id="status-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</div>

<div class="cafe-form-actions category-form-actions">
    <a href="{{ route('admin.product-variants.index') }}" class="cafe-btn-secondary">Hủy</a>
    <button type="submit" class="cafe-btn-primary">
        <x-internal.icon name="check" size="16" />
        <span>{{ $productVariant ? 'Cập nhật biến thể' : 'Lưu biến thể' }}</span>
    </button>
</div>
