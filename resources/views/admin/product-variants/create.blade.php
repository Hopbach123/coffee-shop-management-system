@extends('layouts.internal')
@section('title', 'Thêm biến thể')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <a href="{{ route('admin.product-variants.index') }}">Biến thể</a>
                    <span>›</span>
                    <span>Thêm biến thể</span>
                </div>
                <h1 id="page-title" class="cafe-page-title">Thêm biến thể</h1>
                <p class="cafe-page-subtitle">Thiết lập kích cỡ (Size S/M/L), mã SKU và giá bán cho sản phẩm.</p>
            </div>
            <div>
                <a href="{{ route('admin.product-variants.index') }}" class="cafe-btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.product-variants.store') }}" class="cafe-card category-panel category-form">
            @csrf
            @include('admin.product-variants._form', ['productVariant' => null])
        </form>
    </section>
@endsection
