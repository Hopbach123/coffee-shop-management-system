@extends('layouts.internal')
@section('title', 'Lịch sử kho')
@section('content')
<section class="category-page" aria-labelledby="page-title">
    <div class="cafe-page-header">
        <div>
            <div class="cafe-breadcrumbs">
                <a href="{{ route('admin.home') }}">Dashboard</a>
                <span>›</span>
                <a href="{{ route('admin.ingredients.index') }}">Kho & Nguyên liệu</a>
                <span>›</span>
                <span>Lịch sử biến động</span>
            </div>
            <h1 id="page-title" class="cafe-page-title">Lịch sử · {{ $ingredient->name }}</h1>
            <p class="cafe-page-subtitle">
                Tồn hiện tại: <strong style="color: var(--cafe-espresso);">{{ $ingredient->current_stock }} {{ $ingredient->unit }}</strong>
            </p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            @if ($ingredient->status === 'active')
                <a href="{{ route('admin.ingredients.import.create', $ingredient) }}" class="cafe-btn-primary">
                    <x-internal.icon name="plus" size="14" /> Tạo phiếu nhập mới
                </a>
                <a href="{{ route('admin.ingredients.adjustment.create', $ingredient) }}" class="cafe-btn-secondary">
                    Điều chỉnh / kiểm kê
                </a>
            @endif
            <a href="{{ route('admin.ingredients.index') }}" class="cafe-btn-secondary">
                Về nguyên liệu
            </a>
        </div>
    </div>

    <div class="cafe-card category-panel">
        <p style="font-size: 0.8125rem; color: var(--cafe-text-secondary); margin-bottom: 20px; background: var(--cafe-cream); padding: 10px 14px; border-radius: 8px; border: 1px solid var(--cafe-border);">
            Lịch sử chỉ đọc. Không sửa tồn trực tiếp hoặc xóa log để điều chỉnh kho.
        </p>

        @if ($movements->isEmpty())
            <div style="text-align: center; padding: 48px 20px;">
                <x-internal.icon name="box" size="32" style="color: var(--cafe-caramel); margin-bottom: 10px;" />
                <p class="category-empty" style="color: var(--cafe-text-secondary); font-weight: 600; margin: 0;">Chưa có biến động kho.</p>
            </div>
        @else
            <div class="cafe-table-wrap category-table-wrap" role="region" aria-label="Lịch sử biến động kho" tabindex="0">
                <table class="cafe-table category-table">
                    <caption class="sr-only">Biến động {{ $ingredient->name }}, đơn vị {{ $ingredient->unit }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" style="width: 150px;">Thời điểm (UTC)</th>
                            <th scope="col" style="width: 150px;">Loại</th>
                            <th scope="col" style="width: 130px; text-align: right;">Số lượng</th>
                            <th scope="col" style="width: 150px; text-align: center;">Trước → Sau</th>
                            <th scope="col">Người thực hiện / ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            <tr>
                                <td data-label="Thời điểm (UTC)" style="font-size: 0.8125rem; color: var(--cafe-text-secondary);">
                                    {{ $movement->created_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <th scope="row" data-label="Loại">
                                    @php
                                        $typeBadge = match($movement->type) {
                                            'import' => 'success',
                                            'consume' => 'info',
                                            'adjustment' => 'warning',
                                            'waste' => 'danger',
                                            default => 'info',
                                        };
                                        $typeText = ['import'=>'Nhập kho','consume'=>'Tiêu hao pha chế','adjustment'=>'Điều chỉnh','waste'=>'Hao hụt'][$movement->type] ?? $movement->type;
                                    @endphp
                                    <span class="cafe-badge cafe-badge--{{ $typeBadge }}">
                                        {{ $typeText }}
                                    </span>
                                </th>
                                <td data-label="Số lượng" style="text-align: right; font-weight: 700; color: var(--cafe-espresso);">
                                    {{ $movement->quantity }} {{ $ingredient->unit }}
                                </td>
                                <td data-label="Trước → Sau" style="text-align: center; font-size: 0.8125rem;">
                                    <code>{{ $movement->before_quantity }}</code> → <code>{{ $movement->after_quantity }}</code>
                                </td>
                                <td data-label="Người thực hiện / ghi chú">
                                    <strong style="color: var(--cafe-espresso);">{{ $movement->creator?->name ?? '—' }}</strong>
                                    <p style="margin: 4px 0 0; font-size: 0.8125rem; color: var(--cafe-text-secondary);">
                                        {{ $movement->note ?: 'Không có ghi chú' }}
                                    </p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($movements->hasPages())
                <div class="category-pagination" style="margin-top: 18px;">{{ $movements->links() }}</div>
            @endif
        @endif
    </div>
</section>
@endsection
