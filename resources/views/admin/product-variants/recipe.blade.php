@extends('layouts.internal')
@section('title', 'Công thức biến thể')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng">
            <a href="{{ route('admin.product-variants.index') }}" style="color:var(--cafe-text-secondary);text-decoration:none;">Biến thể</a>
            <span>Công thức</span>
        </nav>
        <h1 class="cafe-page-title" id="page-title">{{ $productVariant->product?->name }} · {{ $productVariant->name }}</h1>
        <p class="cafe-page-subtitle">Định lượng cho một phần; đơn vị lấy từ nguyên liệu.</p>
    </div>
    <a href="{{ route('admin.product-variants.index') }}" class="cafe-btn-secondary">
        ← Về biến thể
    </a>
</div>

<div class="cafe-card">
    @if($recipes->isEmpty())
        <div style="padding:3rem;text-align:center;color:var(--cafe-text-secondary);">
            <x-internal.icon name="recipe" size="40" style="opacity:.3;margin-bottom:.75rem;" />
            <p style="font-weight:600;margin-bottom:.25rem;">Chưa có công thức</p>
            <p style="font-size:.875rem;">Chưa có công thức định lượng cho biến thể này.</p>
        </div>
    @else
        <div class="cafe-table-wrap" role="region" aria-label="Công thức biến thể" tabindex="0">
            <table class="cafe-table">
                <caption class="sr-only">Định lượng một phần</caption>
                <thead>
                    <tr>
                        <th scope="col">Nguyên liệu</th>
                        <th scope="col">Định lượng</th>
                        <th scope="col">Tình trạng</th>
                        <th scope="col">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recipes as $recipe)
                        <tr>
                            <th scope="row" data-label="Nguyên liệu"><strong>{{ $recipe->ingredient?->name ?? 'Không còn thông tin' }}</strong></th>
                            <td data-label="Định lượng">{{ $recipe->quantity }} {{ $recipe->ingredient?->unit }}</td>
                            <td data-label="Tình trạng">
                                @php
                                    $stClass = $recipe->ingredient?->trashed() ? 'cafe-badge--danger' : ($recipe->ingredient?->status === 'active' ? 'cafe-badge--success' : 'cafe-badge--warning');
                                    $stLabel = $recipe->ingredient?->trashed() ? 'Đã xóa' : ($recipe->ingredient?->status === 'active' ? 'Đang dùng' : 'Ngừng dùng');
                                @endphp
                                <span class="cafe-badge {{ $stClass }}">{{ $stLabel }}</span>
                            </td>
                            <td data-label="Thao tác">
                                @if($recipe->ingredient && ! $recipe->ingredient->trashed())
                                    <a href="{{ route('admin.ingredients.history', $recipe->ingredient) }}" class="cafe-btn-secondary" style="font-size:.8rem;padding:.3rem .6rem;">Lịch sử kho</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    <div style="padding:1rem 1.25rem;border-top:1px solid var(--cafe-border);font-size:.85rem;color:var(--cafe-text-secondary);">
        Màn này chỉ đọc công thức. Phần chỉnh sửa công thức sẽ được tích hợp từ module quản lý công thức.
    </div>
</div>
@endsection
