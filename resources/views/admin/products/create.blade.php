@extends('layouts.internal')
@section('title', 'Thêm sản phẩm')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Thêm sản phẩm</h1>
        <p>Nhập thông tin cho sản phẩm mới.</p>
        <form method="POST" action="{{ route('admin.products.store') }}" class="category-panel category-form">
            @csrf
            @include('admin.products._form', ['product' => null])
        </form>
    </section>
@endsection
