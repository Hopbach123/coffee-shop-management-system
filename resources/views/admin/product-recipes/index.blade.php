@extends('layouts.internal')
@section('title', 'Công thức nguyên liệu')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <a href="{{ route('admin.product-variants.index') }}">Biến thể</a>
                    <span>›</span>
                    <span>Công thức nguyên liệu</span>
                </div>
                <p class="v5-eyebrow">MAISON DU CAFÉ · QUẢN TRỊ</p>
        <h1 id="page-title" class="cafe-page-title">Công thức nguyên liệu</h1>
                <p class="cafe-page-subtitle">
                    {{ $productVariant->product?->name ?? 'Sản phẩm' }} · <span class="cafe-badge cafe-badge--info">{{ $productVariant->name }}</span> · SKU: <code style="font-family: monospace;">{{ $productVariant->sku ?? 'Không có SKU' }}</code>
                </p>
                <p style="font-size: 0.8125rem; color: var(--cafe-text-secondary); margin-top: 4px;">
                    Định lượng cho một phần của biến thể. Lưu công thức không làm thay đổi tồn kho.
                </p>
            </div>
            <div>
                <a href="{{ route('admin.product-variants.index') }}" class="cafe-btn-secondary">
                    Về biến thể
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="sneat-alert sneat-alert--success cafe-notice" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="sneat-alert sneat-alert--danger category-notice category-notice--error cafe-notice" role="alert">
                <p style="font-weight: 600; margin-bottom: 6px;">Chưa lưu được công thức:</p>
                <ul style="margin: 0 0 0 18px; padding: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="cafe-card category-panel" style="margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--cafe-border); padding-bottom: 12px;">
                <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--cafe-espresso); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <x-internal.icon name="chef" size="20" style="color: var(--cafe-caramel);" />
                    Nguyên liệu trong công thức
                </h2>
                <span style="font-size: 0.8125rem; color: var(--cafe-text-secondary);">
                    {{ $recipes->count() }} thành phần
                </span>
            </div>

            @if ($recipes->isEmpty())
                <div style="text-align: center; padding: 36px 16px; background: var(--cafe-cream); border-radius: 12px; border: 1px dashed var(--cafe-border);">
                    <x-internal.icon name="chef" size="36" style="color: var(--cafe-caramel); margin-bottom: 10px;" />
                    <p class="category-empty" style="font-weight: 600; color: var(--cafe-espresso); margin: 0 0 6px;">Biến thể chưa có công thức. Thêm nguyên liệu bên dưới.</p>
                    <p style="font-size: 0.8125rem; color: var(--cafe-text-secondary); margin: 0; max-width: 500px; margin: 0 auto;">Chưa có định lượng nguyên liệu. Hệ thống chặn bắt đầu pha cho đến khi Admin cấu hình công thức hợp lệ và đủ tồn kho.</p>
                </div>
            @else
                <div class="cafe-table-wrap category-table-wrap" role="region" aria-label="Công thức nguyên liệu" tabindex="0">
                    <table class="cafe-table category-table">
                        <caption class="sr-only">Định lượng nguyên liệu cho một phần</caption>
                        <thead>
                            <tr>
                                <th scope="col" style="width: 60px; text-align: center;">STT</th>
                                <th scope="col">Nguyên liệu</th>
                                <th scope="col" style="width: 100px;">Đơn vị</th>
                                <th scope="col" style="width: 320px;">Định lượng</th>
                                <th scope="col" style="width: 180px; text-align: right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recipes as $recipe)
                                <tr>
                                    <td style="text-align: center; color: var(--cafe-text-secondary);">{{ $loop->iteration }}</td>
                                    <th scope="row" data-label="Nguyên liệu" style="font-weight: 600; color: var(--cafe-espresso);">
                                        {{ $recipe->ingredient?->name ?? 'Nguyên liệu không tồn tại' }}
                                        @if ($recipe->ingredient?->trashed())
                                            <span style="color: var(--cafe-danger); font-size: 0.75rem;">(đã xóa)</span>
                                        @elseif ($recipe->ingredient?->status !== 'active')
                                            <span style="color: var(--cafe-warning); font-size: 0.75rem;">(ngừng dùng)</span>
                                        @endif
                                    </th>
                                    <td data-label="Đơn vị">
                                        <span class="cafe-badge cafe-badge--info">{{ $recipe->ingredient?->unit ?? '—' }}</span>
                                    </td>
                                    <td data-label="Định lượng">
                                        <p style="margin: 0 0 6px; font-weight: 600; color: var(--cafe-espresso);">{{ number_format((float) $recipe->quantity, 3, ',', '.') }} {{ $recipe->ingredient?->unit }}</p>
                                        <form method="POST" action="{{ route('admin.product-recipes.update', [$productVariant, $recipe]) }}" class="category-form" style="display: flex; gap: 8px; align-items: flex-start;">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="editing_recipe" value="{{ $recipe->id }}">
                                            <div class="category-field" style="flex: 1;">
                                                <label for="quantity-{{ $recipe->id }}" class="sr-only">Định lượng {{ $recipe->ingredient?->name }}</label>
                                                <input id="quantity-{{ $recipe->id }}" name="quantity" type="number" min="0.001" max="999999999.999" step="0.001" value="{{ (is_scalar(old('editing_recipe')) ? old('editing_recipe') : null) == $recipe->id && is_scalar(old('quantity')) ? old('quantity') : $recipe->quantity }}" required class="cafe-input" style="min-height: 36px; padding: 6px 10px;" @if((is_scalar(old('editing_recipe')) ? old('editing_recipe') : null) == $recipe->id && $errors->has('quantity')) aria-invalid="true" aria-describedby="quantity-error-{{ $recipe->id }}" style="border-color: var(--cafe-danger);" @endif>
                                                @if((is_scalar(old('editing_recipe')) ? old('editing_recipe') : null) == $recipe->id && $errors->has('quantity'))
                                                    <p class="category-field__error" id="quantity-error-{{ $recipe->id }}" style="color: var(--cafe-danger); font-size: 0.75rem; margin-top: 2px;">{{ $errors->first('quantity') }}</p>
                                                @endif
                                            </div>
                                            <button type="submit" class="cafe-btn-secondary">
                                                Lưu định lượng
                                            </button>
                                        </form>
                                    </td>
                                    <td data-label="Thao tác" style="text-align: right;">
                                        <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end;">
                                            @if ($recipe->ingredient && ! $recipe->ingredient->trashed())
                                                <a href="{{ route('admin.ingredients.history', $recipe->ingredient) }}" class="cafe-btn-secondary" style="padding: 4px 8px; font-size: 0.75rem;" title="Xem lịch sử nhập xuất">
                                                    Lịch sử kho
                                                </a>
                                            @endif
                                            <form method="POST" action="{{ route('admin.product-recipes.destroy', [$productVariant, $recipe]) }}" onsubmit="return confirm('Bỏ nguyên liệu này khỏi công thức?');" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="cafe-btn-icon cafe-btn-icon--danger category-delete" title="Bỏ nguyên liệu">
                                                    <x-internal.icon name="trash" size="14" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="cafe-card category-panel">
            <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--cafe-espresso); margin: 0 0 16px; display: flex; align-items: center; gap: 8px;">
                <x-internal.icon name="plus" size="18" style="color: var(--cafe-caramel);" />
                Thêm nguyên liệu vào công thức
            </h2>
            @if ($ingredients->isEmpty())
                <div style="padding: 16px; background: var(--cafe-cream); border-radius: 10px; border: 1px solid var(--cafe-border);">
                    <p style="color: var(--cafe-text-secondary); margin: 0 0 12px; font-size: 0.875rem;">Không còn nguyên liệu đang dùng để thêm. Bạn có thể tạo hoặc kích hoạt nguyên liệu trong mục Nguyên liệu.</p>
                    <a href="{{ route('admin.ingredients.index') }}" class="cafe-btn-secondary">
                        Quản lý nguyên liệu
                    </a>
                </div>
            @else
                <form method="POST" action="{{ route('admin.product-recipes.store', $productVariant) }}" class="category-form">
                    @csrf
                    <div class="cafe-form-layout" style="grid-template-columns: 1fr;">
                        <div class="cafe-form-fields category-fields">
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                                <div class="cafe-form-group category-field">
                                    <label for="ingredient_id">Nguyên liệu <span class="required" aria-hidden="true">*</span></label>
                                    <select id="ingredient_id" name="ingredient_id" required class="cafe-select" @error('ingredient_id') aria-invalid="true" aria-describedby="ingredient-error" style="border-color: var(--cafe-danger);" @enderror>
                                        <option value="">Chọn nguyên liệu và đơn vị</option>
                                        @foreach ($ingredients as $ingredient)
                                            <option value="{{ $ingredient->id }}" @selected((string) (is_scalar(old('ingredient_id')) ? old('ingredient_id') : '') === (string) $ingredient->id)>{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                                        @endforeach
                                    </select>
                                    @error('ingredient_id')<p class="category-field__error" id="ingredient-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $message }}</p>@enderror
                                </div>
                                <div class="cafe-form-group category-field">
                                    <label for="quantity">Định lượng cho một phần <span class="required" aria-hidden="true">*</span></label>
                                    <input id="quantity" name="quantity" type="number" min="0.001" max="999999999.999" step="0.001" placeholder="VD: 20 hoặc 150..." value="{{ ! (is_scalar(old('editing_recipe')) ? old('editing_recipe') : null) && is_scalar(old('quantity')) ? old('quantity') : '' }}" required class="cafe-input" @if(! (is_scalar(old('editing_recipe')) ? old('editing_recipe') : null) && $errors->has('quantity')) aria-invalid="true" aria-describedby="quantity-error" style="border-color: var(--cafe-danger);" @endif>
                                    @if(! (is_scalar(old('editing_recipe')) ? old('editing_recipe') : null) && $errors->has('quantity'))<p class="category-field__error" id="quantity-error" style="color: var(--cafe-danger); font-size: 0.8125rem; margin-top: 4px;">{{ $errors->first('quantity') }}</p>@endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="cafe-form-actions category-form-actions" style="margin-top: 16px;">
                        <button type="submit" class="cafe-btn-primary">
                            <x-internal.icon name="plus" size="16" />
                            <span>Thêm vào công thức</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </section>
@endsection
