@extends('layouts.internal')
@section('title', 'Sửa khuyến mãi')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Sửa khuyến mãi</h1>
        <p>Thay đổi này không cập nhật các đơn đã dùng mã.</p>
        <form method="POST" action="{{ route('admin.promotions.update', $promotion) }}" class="category-panel category-form">
            @csrf
            @method('PUT')
            @include('admin.promotions._form')
        </form>
    </section>
@endsection
