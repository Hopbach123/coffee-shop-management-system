@extends('layouts.internal')
@section('title','Sơ đồ bàn')
@section('content')<div class="sneat-page-title"><div><p>Vận hành</p><h1>Sơ đồ bàn</h1><span>Theo dõi trạng thái bàn từ dữ liệu hệ thống</span></div><a href="{{ route('staff.home') }}" class="sneat-primary">Đến POS →</a></div>
<div class="table-map">@forelse($tables as $table)<article class="sneat-card table-map-item"><div class="table-map-icon"><x-internal.icon name="table" size="28" /></div><h2>{{ $table->table_name ?: $table->table_code }}</h2><p>{{ $table->area?->name }} · {{ $table->capacity }} khách</p><span class="sneat-pill {{ $table->status === 'available' ? 'is-success' : ($table->status === 'occupied' ? 'is-warning' : 'is-danger') }}">{{ ['available'=>'Đang trống','occupied'=>'Đang phục vụ','maintenance'=>'Bảo trì'][$table->status] ?? $table->status }}</span></article>@empty<div class="sneat-card sneat-empty">Chưa thiết lập bàn.</div>@endforelse</div>
@endsection
