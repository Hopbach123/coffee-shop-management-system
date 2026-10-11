@extends('layouts.internal')
@section('title', 'Sửa biến thể')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Sửa biến thể</h1>
        <p>Chỉnh sửa {{ $productVariant->name }} của {{ $productVariant->product?->name ?? 'sản phẩm' }}.</p>
        <form method="POST" action="{{ route('admin.product-variants.update', $productVariant) }}" class="category-panel category-form">
            @csrf
            @method('PUT')
            @include('admin.product-variants._form')
        </form>
    </section>
@endsection
