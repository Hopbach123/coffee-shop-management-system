@extends('layouts.internal')
@section('title', 'Công thức nguyên liệu')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div>
                <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
                <h1 id="page-title">Công thức nguyên liệu</h1>
                <p>{{ $productVariant->product?->name ?? 'Sản phẩm' }} · {{ $productVariant->name }} · {{ $productVariant->sku ?? 'Không có SKU' }}</p>
                <p>Định lượng cho một phần của biến thể. Lưu công thức không làm thay đổi tồn kho.</p>
            </div>
            <x-public.button href="{{ route('admin.product-variants.index') }}" variant="outline">Về biến thể</x-public.button>
        </div>
        @if (session('success'))
            <p class="category-notice category-notice--success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div class="category-notice category-notice--error" role="alert">
                <p>Chưa lưu được công thức:</p>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <div class="category-panel">
            <h2>Nguyên liệu trong công thức</h2>
            @if ($recipes->isEmpty())
                <p class="category-empty">Biến thể chưa có công thức. Thêm nguyên liệu bên dưới.</p>
            @else
                <div class="category-table-wrap" role="region" aria-label="Công thức nguyên liệu" tabindex="0">
                    <table class="category-table">
                        <caption class="sr-only">Định lượng nguyên liệu cho một phần</caption>
                        <thead><tr><th scope="col">Nguyên liệu</th><th scope="col">Đơn vị</th><th scope="col">Định lượng</th><th scope="col">Thao tác</th></tr></thead>
                        <tbody>
                        @foreach ($recipes as $recipe)
                            <tr>
                                <th scope="row" data-label="Nguyên liệu">
                                    {{ $recipe->ingredient?->name ?? 'Nguyên liệu không tồn tại' }}
                                    @if ($recipe->ingredient?->trashed()) (đã xóa)
                                    @elseif ($recipe->ingredient?->status !== 'active') (ngừng dùng) @endif
                                </th>
                                <td data-label="Đơn vị">{{ $recipe->ingredient?->unit ?? '—' }}</td>
                                <td data-label="Định lượng">
                                    <form method="POST" action="{{ route('admin.product-recipes.update', [$productVariant, $recipe]) }}" class="category-form">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="editing_recipe" value="{{ $recipe->id }}">
                                        <div class="category-field">
                                            <label for="quantity-{{ $recipe->id }}">Định lượng {{ $recipe->ingredient?->name }}</label>
                                            <input id="quantity-{{ $recipe->id }}" name="quantity" type="number" min="0.001" max="999999999.999" step="0.001" value="{{ old('editing_recipe') == $recipe->id && is_scalar(old('quantity')) ? old('quantity') : $recipe->quantity }}" required @if(old('editing_recipe') == $recipe->id && $errors->has('quantity')) aria-invalid="true" aria-describedby="quantity-error-{{ $recipe->id }}" @endif>
                                            @if(old('editing_recipe') == $recipe->id && $errors->has('quantity'))
                                                <p class="category-field__error" id="quantity-error-{{ $recipe->id }}">{{ $errors->first('quantity') }}</p>
                                            @endif
                                        </div>
                                        <x-public.button type="submit" variant="outline">Lưu định lượng</x-public.button>
                                    </form>
                                </td>
                                <td data-label="Thao tác">
                                    <form method="POST" action="{{ route('admin.product-recipes.destroy', [$productVariant, $recipe]) }}" onsubmit="return confirm('Bỏ nguyên liệu này khỏi công thức?');">
                                        @csrf @method('DELETE')
                                        <x-public.button type="submit" variant="outline" class="category-delete">Bỏ nguyên liệu</x-public.button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.product-recipes.store', $productVariant) }}" class="category-panel category-form">
            @csrf
            <h2>Thêm nguyên liệu</h2>
            @if ($ingredients->isEmpty())
                <p>Không còn nguyên liệu đang dùng để thêm. Bạn có thể tạo hoặc kích hoạt nguyên liệu trong mục Nguyên liệu.</p>
                <x-public.button href="{{ route('admin.ingredients.index') }}" variant="outline">Quản lý nguyên liệu</x-public.button>
            @else
                <div class="category-fields">
                    <div class="category-field">
                        <label for="ingredient_id">Nguyên liệu *</label>
                        <select id="ingredient_id" name="ingredient_id" required @error('ingredient_id') aria-invalid="true" aria-describedby="ingredient-error" @enderror>
                            <option value="">Chọn nguyên liệu và đơn vị</option>
                            @foreach ($ingredients as $ingredient)
                                <option value="{{ $ingredient->id }}" @selected((string) (is_scalar(old('ingredient_id')) ? old('ingredient_id') : '') === (string) $ingredient->id)>{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                            @endforeach
                        </select>
                        @error('ingredient_id')<p class="category-field__error" id="ingredient-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="category-field">
                        <label for="quantity">Định lượng cho một phần *</label>
                        <input id="quantity" name="quantity" type="number" min="0.001" max="999999999.999" step="0.001" value="{{ ! old('editing_recipe') && is_scalar(old('quantity')) ? old('quantity') : '' }}" required @if(! old('editing_recipe') && $errors->has('quantity')) aria-invalid="true" aria-describedby="quantity-error" @endif>
                        @if(! old('editing_recipe') && $errors->has('quantity'))<p class="category-field__error" id="quantity-error">{{ $errors->first('quantity') }}</p>@endif
                    </div>
                </div>
                <x-public.button type="submit">Thêm vào công thức</x-public.button>
            @endif
        </form>
    </section>
@endsection
