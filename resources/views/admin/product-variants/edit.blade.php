@extends('layouts.internal')
@section('title', 'Sửa biến thể')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <a href="{{ route('admin.product-variants.index') }}">Biến thể</a>
                    <span>›</span>
                    <span>Sửa biến thể</span>
                </div>
                <h1 id="page-title" class="cafe-page-title">Sửa biến thể</h1>
                <p class="cafe-page-subtitle">Chỉnh sửa {{ $productVariant->name }} của {{ $productVariant->product?->name ?? 'sản phẩm' }}.</p>
            </div>
            <div>
                <a href="{{ route('admin.product-variants.index') }}" class="cafe-btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.product-variants.update', $productVariant) }}" class="cafe-card category-panel category-form">
            @csrf
            @method('PUT')
            @include('admin.product-variants._form')
        </form>
    </section>
@endsection
