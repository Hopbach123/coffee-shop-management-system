@extends('layouts.internal')
@section('title', 'Thêm biến thể')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Thêm biến thể</h1>
        <p>Thiết lập kích cỡ, SKU và giá bán cho một sản phẩm.</p>
        <form method="POST" action="{{ route('admin.product-variants.store') }}" class="category-panel category-form">
            @csrf
            @include('admin.product-variants._form', ['productVariant' => null])
        </form>
    </section>
@endsection
