@extends('layouts.internal')
@section('title', 'Khu vực Admin')
@section('content')
    <section class="internal-panel" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Khu vực Admin</h1>
        <p>Chào mừng, {{ auth('web')->user()->name }}.</p>
        <p>Bạn có quyền truy cập khu vực quản trị và khu vực nhân viên.</p>
        <x-public.button href="{{ route('staff.home') }}" variant="outline">Vào khu vực Staff</x-public.button>
    </section>
@endsection
