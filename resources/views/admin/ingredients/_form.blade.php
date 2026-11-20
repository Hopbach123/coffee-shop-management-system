@if ($errors->any())
    <div class="sneat-alert sneat-alert--danger category-notice category-notice--error cafe-notice" role="alert" style="margin-bottom: 24px;">
        Vui lòng kiểm tra các trường được đánh dấu bên dưới.
    </div>
@endif

<div style="background: var(--cafe-cream); border: 1px solid var(--cafe-border); border-radius: 10px; padding: 12px 16px; margin-bottom: 24px; font-size: 0.8125rem; color: var(--cafe-text-secondary); line-height: 1.5;">
    <p style="margin: 0;">Tồn kho được cập nhật qua nghiệp vụ kho và ghi lịch sử; không chỉnh sửa tại đây.</p>
    @if ($ingredient)
        <p style="margin: 6px 0 0; color: var(--cafe-espresso);">Tồn hiện tại: <strong>{{ $ingredient->current_stock }} {{ $ingredient->unit }}</strong></p>
    @endif
</div>

<div class="cafe-form-layout">
    <aside class="v5-context"><x-internal.icon name="box" /><div><strong>Thông tin nguyên liệu</strong><p>Đơn vị tính dùng thống nhất cho giá vốn, công thức và các phiếu kho. Ảnh không thuộc dữ liệu nguyên liệu hiện tại.</p></div></aside>
    <div class="cafe-form-fields category-fields">
        <div class="cafe-form-group category-field">
            <label for="name">Tên nguyên liệu <span class="required" aria-hidden="true">*</span></label>
            <input id="name" name="name" value="{{ is_scalar(old('name')) ? old('name') : ($ingredient?->name ?? '') }}" maxlength="255" required placeholder="Nhập tên nguyên liệu..." class="cafe-input" @error('name') aria-invalid="true" aria-describedby="name-error" style="border-color: var(--cafe-danger);" @enderror>
            @error('name') <p class="category-field__error" id="name-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
        </div>

        <div class="v5-form-grid">
            <div class="cafe-form-group category-field">
                <label for="unit">Đơn vị tính <span class="required" aria-hidden="true">*</span></label>
                @php
                    $selectedUnit = old('unit', $ingredient?->unit ?? '');
                    $isCustomUnit = is_string($selectedUnit) && $selectedUnit !== '' && ! array_key_exists($selectedUnit, config('inventory.units'));
                @endphp
                <select id="unit" name="unit" required aria-describedby="unit-help @error('unit') unit-error @enderror" class="cafe-select" @error('unit') aria-invalid="true" style="border-color: var(--cafe-danger);" @enderror>
                    <option value="">Chọn đơn vị tính</option>
                    @foreach (config('inventory.units') as $value => $label)
                        <option value="{{ $value }}" @selected(old('unit', $ingredient?->unit ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                    <option value="other" @selected($isCustomUnit)>Khác (nhập bên dưới)</option>
                </select>
                <p id="unit-help" style="font-size: 0.75rem; color: var(--cafe-text-secondary); margin-top: 4px;">Số lượng và giá vốn đều tính theo đơn vị này. Không đổi đơn vị khi đã có tồn kho, công thức hoặc lịch sử kho; không tự động quy đổi.</p>
                @error('unit') <p class="category-field__error" id="unit-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>

            <div class="cafe-form-group category-field">
                <label for="custom_unit">Tên đơn vị khác</label>
                <input id="custom_unit" name="custom_unit" maxlength="50" placeholder="VD: chai, gói, quả..." value="{{ is_scalar(old('custom_unit')) ? old('custom_unit') : ($isCustomUnit ? $selectedUnit : '') }}" aria-describedby="custom-unit-help" class="cafe-input">
                <p id="custom-unit-help" style="font-size: 0.75rem; color: var(--cafe-text-secondary); margin-top: 4px;">Chỉ nhập khi chọn Khác, ví dụ: chai, túi, hộp. Tối đa 50 ký tự. Hệ thống bỏ khoảng trắng thừa và lưu chữ thường; đơn vị đã dùng vẫn không được đổi.</p>
            </div>
        </div>

        <div class="v5-form-grid">
            <div class="cafe-form-group category-field">
                <label for="cost_price">Giá vốn theo đơn vị tính (₫) <span class="required" aria-hidden="true">*</span></label>
                <input id="cost_price" name="cost_price" type="number" min="0" max="9999999999.99" step="0.01" value="{{ is_scalar(old('cost_price')) ? old('cost_price') : ($ingredient?->cost_price ?? '0') }}" required placeholder="0" class="cafe-input" @error('cost_price') aria-invalid="true" aria-describedby="cost_price-error" style="border-color: var(--cafe-danger);" @enderror>
                @error('cost_price') <p class="category-field__error" id="cost_price-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>

            <div class="cafe-form-group category-field">
                <label for="minimum_stock">Ngưỡng tồn tối thiểu <span class="required" aria-hidden="true">*</span></label>
                <input id="minimum_stock" name="minimum_stock" type="number" min="0" max="999999999.999" step="0.001" value="{{ is_scalar(old('minimum_stock')) ? old('minimum_stock') : ($ingredient?->minimum_stock ?? '0') }}" required placeholder="0" class="cafe-input" @error('minimum_stock') aria-invalid="true" aria-describedby="minimum_stock-error" style="border-color: var(--cafe-danger);" @enderror>
                @error('minimum_stock') <p class="category-field__error" id="minimum_stock-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="cafe-form-group category-field">
            <label for="status">Trạng thái <span class="required" aria-hidden="true">*</span></label>
            <select id="status" name="status" required class="cafe-select" @error('status') aria-invalid="true" aria-describedby="status-error" style="border-color: var(--cafe-danger);" @enderror>
                <option value="active" @selected(old('status', $ingredient?->status ?? 'active') === 'active')>Đang dùng</option>
                <option value="inactive" @selected(old('status', $ingredient?->status ?? 'active') === 'inactive')>Ngừng dùng</option>
            </select>
            @error('status') <p class="category-field__error" id="status-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="cafe-form-actions category-form-actions">
    <a href="{{ route('admin.ingredients.index') }}" class="cafe-btn-secondary">Hủy</a>
    <button type="submit" class="cafe-btn-primary">
        <x-internal.icon name="check" size="16" />
        <span>{{ $ingredient ? 'Cập nhật nguyên liệu' : 'Lưu nguyên liệu' }}</span>
    </button>
</div>
