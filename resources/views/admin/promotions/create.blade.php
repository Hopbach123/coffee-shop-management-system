@extends('layouts.internal')
@section('title', 'Thêm khuyến mãi')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Thêm khuyến mãi</h1>
        <p>Mã được chuẩn hóa thành chữ in hoa. Lượt dùng chỉ tăng sau thanh toán thành công.</p>
        <form method="POST" action="{{ route('admin.promotions.store') }}" class="category-panel category-form">
            @csrf
            @include('admin.promotions._form', ['promotion' => null])
        </form>
    </section>
@endsection
