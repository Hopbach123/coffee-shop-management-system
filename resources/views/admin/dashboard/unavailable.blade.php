@extends('layouts.internal')
@section('title', 'Dashboard · Chưa thể tải số liệu')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng"><span>Quản trị</span><span>Dashboard</span></nav>
        <h1 class="cafe-page-title" id="page-title">Dashboard</h1>
        <p class="cafe-page-subtitle">Hệ thống ghi nhận trạng thái tải dữ liệu.</p>
    </div>
</div>

<div class="cafe-card" style="padding:2.5rem;text-align:center;max-width:540px;margin:2rem auto;">
    <div class="cafe-notice cafe-notice--error" role="alert" style="margin-bottom:1rem;">
        Chưa thể tải số liệu Dashboard.
    </div>
    <p style="color:var(--cafe-text-secondary);font-size:.9rem;margin-bottom:1.25rem;">Vui lòng thử lại sau. Số liệu chưa được xác định.</p>
    <a href="{{ route('admin.dashboard.index') }}" class="cafe-btn-secondary">Thử lại</a>
</div>
@endsection
