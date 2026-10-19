@extends('layouts.internal')
@section('title', 'Dashboard · Chưa thể tải số liệu')
@section('content')
<section class="category-page" aria-labelledby="page-title">
    <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
    <h1 id="page-title">Dashboard</h1>
    <div class="category-panel">
        <p class="category-notice category-notice--error" role="alert">Chưa thể tải số liệu Dashboard.</p>
        <p>Vui lòng thử lại sau. Số liệu chưa được xác định.</p>
        <x-public.button href="{{ route('admin.dashboard.index') }}" variant="outline">Thử lại</x-public.button>
    </div>
</section>
@endsection
