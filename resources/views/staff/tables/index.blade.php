@extends('layouts.internal')
@section('title', 'Sơ đồ bàn')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng"><span>Vận hành</span><span>Sơ đồ bàn</span></nav>
        <h1 class="cafe-page-title">Sơ đồ bàn</h1>
        <p class="cafe-page-subtitle">Theo dõi trạng thái bàn từ dữ liệu hệ thống.</p>
    </div>
    <a href="{{ route('staff.home') }}" class="cafe-btn-primary">
        <x-internal.icon name="pos" size="16" /> Đến POS →
    </a>
</div>

@if($tables->isEmpty())
    <div class="cafe-card" style="padding:3rem;text-align:center;color:var(--cafe-text-secondary);">
        <x-internal.icon name="table" size="48" style="opacity:.25;margin-bottom:1rem;" />
        <p style="font-weight:600;font-size:1rem;">Chưa thiết lập bàn.</p>
    </div>
@else
    {{-- Status legend --}}
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem;">
        <span class="cafe-badge cafe-badge--success">Đang trống</span>
        <span class="cafe-badge cafe-badge--warning">Đang phục vụ</span>
        <span class="cafe-badge cafe-badge--danger">Bảo trì</span>
    </div>

    @foreach($tables->groupBy(fn ($table) => $table->area_id ?? 'unassigned') as $areaTables)
    <section class="cafe-table-area" aria-labelledby="area-heading-{{ $loop->index }}">
        <header class="cafe-table-area-heading">
            <h2 id="area-heading-{{ $loop->index }}">{{ $areaTables->first()->area?->name ?? 'Chưa phân khu vực' }}</h2>
            <span>{{ $areaTables->count() }} bàn</span>
        </header>
        <div class="cafe-table-area-grid">
        @foreach($areaTables as $table)
            @php
                $statusClass = match($table->status) {
                    'available'   => 'cafe-badge--success',
                    'occupied'    => 'cafe-badge--warning',
                    'maintenance' => 'cafe-badge--danger',
                    default       => 'cafe-badge--info',
                };
                $statusLabel = ['available'=>'Đang trống','occupied'=>'Đang phục vụ','maintenance'=>'Bảo trì'][$table->status] ?? $table->status;
                $borderColor = match($table->status) {
                    'available'   => 'var(--cafe-success)',
                    'occupied'    => 'var(--cafe-warning)',
                    'maintenance' => 'var(--cafe-danger)',
                    default       => 'var(--cafe-border)',
                };
            @endphp
            <article class="cafe-card cafe-table-card cafe-table-card--{{ $table->status }}">
                <header class="cafe-table-card-header">
                    <x-internal.icon name="table" size="24" />
                    <span class="cafe-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </header>
                <h3 class="cafe-table-name">{{ $table->table_name ?: $table->table_code }}</h3>
                <p class="cafe-table-card-area">{{ $table->area?->name ?? 'Chưa phân khu vực' }}</p>
                <footer class="cafe-table-card-footer">Sức chứa <strong>{{ $table->capacity }} khách</strong></footer>
            </article>
        @endforeach
        </div>
    </section>
    @endforeach
@endif
@endsection
