@php
    $catalogOld = static function ($field, $default = '') {
        $value = old($field, $default);
        return is_scalar($value) || $value === null ? $value : $default;
    };
@endphp

@if ($errors->any())
    <div class="category-notice category-notice--error" role="alert">
        <p>Vui lòng kiểm tra các trường được đánh dấu bên dưới.</p>
    </div>
@endif

<div class="category-fields">
    <div class="category-field">
        <label for="name">Tên topping <span aria-hidden="true">*</span></label>
        <input id="name" name="name" type="text" value="{{ $catalogOld('name', $topping?->name ?? '') }}" maxlength="255" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        @error('name') <p class="category-field__error" id="name-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="price">Giá bán (₫) <span aria-hidden="true">*</span></label>
        <input id="price" name="price" type="number" min="0" max="9999999999.99" step="0.01" value="{{ $catalogOld('price', $topping?->price ?? '0') }}" required @error('price') aria-invalid="true" aria-describedby="price-error" @enderror>
        @error('price') <p class="category-field__error" id="price-error">{{ $message }}</p> @enderror
    </div>

    <div class="category-field">
        <label for="status">Trạng thái <span aria-hidden="true">*</span></label>
        <select id="status" name="status" required @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
            <option value="available" @selected($catalogOld('status', $topping?->status ?? 'available') === 'available')>Có sẵn</option>
            <option value="unavailable" @selected($catalogOld('status', $topping?->status ?? 'available') === 'unavailable')>Tạm hết</option>
        </select>
        @error('status') <p class="category-field__error" id="status-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="category-form-actions">
    <x-public.button type="submit">Lưu topping</x-public.button>
    <x-public.button href="{{ route('admin.toppings.index') }}" variant="outline">Hủy</x-public.button>
</div>
