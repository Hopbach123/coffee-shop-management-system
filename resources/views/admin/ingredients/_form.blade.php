@if ($errors->any())
    <div class="category-notice category-notice--error" role="alert">Vui lòng kiểm tra các trường được đánh dấu bên dưới.</div>
@endif
<p>Tồn kho được cập nhật qua nghiệp vụ kho và ghi lịch sử; không chỉnh sửa tại đây.</p>
@if ($ingredient)
    <p>Tồn hiện tại: <strong>{{ $ingredient->current_stock }} {{ $ingredient->unit }}</strong></p>
@endif
<div class="category-fields">
    <div class="category-field">
        <label for="name">Tên nguyên liệu *</label>
        <input id="name" name="name" value="{{ is_scalar(old('name')) ? old('name') : ($ingredient?->name ?? '') }}" maxlength="255" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        @error('name') <p class="category-field__error" id="name-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="unit">Đơn vị tính *</label>
        @php
            $selectedUnit = old('unit', $ingredient?->unit ?? '');
            $isCustomUnit = is_string($selectedUnit) && $selectedUnit !== '' && ! array_key_exists($selectedUnit, config('inventory.units'));
        @endphp
        <select id="unit" name="unit" required aria-describedby="unit-help @error('unit') unit-error @enderror" @error('unit') aria-invalid="true" @enderror>
            <option value="">Chọn đơn vị tính</option>
            @foreach (config('inventory.units') as $value => $label)
                <option value="{{ $value }}" @selected(old('unit', $ingredient?->unit ?? '') === $value)>{{ $label }}</option>
            @endforeach
            <option value="other" @selected($isCustomUnit)>Khác (nhập bên dưới)</option>
        </select>
        <p id="unit-help">Số lượng và giá vốn đều tính theo đơn vị này. Không đổi đơn vị khi đã có tồn kho, công thức hoặc lịch sử kho; không tự động quy đổi.</p>
        @error('unit') <p class="category-field__error" id="unit-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="custom_unit">Tên đơn vị khác</label>
        <input id="custom_unit" name="custom_unit" maxlength="50" value="{{ is_scalar(old('custom_unit')) ? old('custom_unit') : ($isCustomUnit ? $selectedUnit : '') }}" aria-describedby="custom-unit-help">
        <p id="custom-unit-help">Chỉ nhập khi chọn Khác, ví dụ: chai, túi, hộp. Tối đa 50 ký tự. Hệ thống bỏ khoảng trắng thừa và lưu chữ thường; đơn vị đã dùng vẫn không được đổi.</p>
    </div>
    <div class="category-field">
        <label for="minimum_stock">Ngưỡng tồn tối thiểu *</label>
        <input id="minimum_stock" name="minimum_stock" type="number" min="0" max="999999999.999" step="0.001" value="{{ is_scalar(old('minimum_stock')) ? old('minimum_stock') : ($ingredient?->minimum_stock ?? '0') }}" required @error('minimum_stock') aria-invalid="true" aria-describedby="minimum_stock-error" @enderror>
        @error('minimum_stock') <p class="category-field__error" id="minimum_stock-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="cost_price">Giá vốn theo đơn vị tính (₫) *</label>
        <input id="cost_price" name="cost_price" type="number" min="0" max="9999999999.99" step="0.01" value="{{ is_scalar(old('cost_price')) ? old('cost_price') : ($ingredient?->cost_price ?? '0') }}" required @error('cost_price') aria-invalid="true" aria-describedby="cost_price-error" @enderror>
        @error('cost_price') <p class="category-field__error" id="cost_price-error">{{ $message }}</p> @enderror
    </div>
    <div class="category-field">
        <label for="status">Trạng thái *</label>
        <select id="status" name="status" required @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
            <option value="active" @selected(old('status', $ingredient?->status ?? 'active') === 'active')>Đang dùng</option>
            <option value="inactive" @selected(old('status', $ingredient?->status ?? 'active') === 'inactive')>Ngừng dùng</option>
        </select>
        @error('status') <p class="category-field__error" id="status-error">{{ $message }}</p> @enderror
    </div>
</div>
<div class="category-form-actions">
    <x-public.button type="submit">Lưu nguyên liệu</x-public.button>
    <x-public.button href="{{ route('admin.ingredients.index') }}" variant="outline">Hủy</x-public.button>
</div>
