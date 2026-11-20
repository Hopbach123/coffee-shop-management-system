@extends('layouts.internal')
@section('title', 'Thêm sản phẩm')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <a href="{{ route('admin.products.index') }}">Sản phẩm</a>
                    <span>›</span>
                    <span>Thêm sản phẩm</span>
                </div>
                <h1 id="page-title" class="cafe-page-title">Thêm sản phẩm</h1>
                <p class="cafe-page-subtitle">Nhập thông tin sản phẩm mới vào thực đơn.</p>
            </div>
            <div>
                <a href="{{ route('admin.products.index') }}" class="cafe-btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.products.store') }}" class="cafe-card category-panel category-form" enctype="multipart/form-data">
            @csrf
            @include('admin.products._form', ['product' => null])
        </form>
    </section>
@endsection
