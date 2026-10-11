@extends('layouts.internal')
@section('title', 'Sửa sản phẩm')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Sửa sản phẩm</h1>
        <p>Chỉnh sửa thông tin của {{ $product->name }}.</p>
        <form method="POST" action="{{ route('admin.products.update', $product) }}" class="category-panel category-form">
            @csrf
            @method('PUT')
            @include('admin.products._form', ['product' => $product])
        </form>
    </section>
@endsection
