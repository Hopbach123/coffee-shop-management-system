@extends('layouts.internal')
@section('title', 'Khu vực Staff')
@section('content')
    <section class="internal-panel" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Nhân viên</p>
        <h1 id="page-title">Khu vực Staff</h1>
        <p>Chào mừng, {{ auth('web')->user()->name }}.</p>
        <p>Bạn đang ở khu vực làm việc của nhân viên.</p>
    </section>
@endsection
