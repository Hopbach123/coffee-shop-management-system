@extends('layouts.internal')
@section('title', 'Thêm danh mục')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <a href="{{ route('admin.categories.index') }}">Danh mục</a>
                    <span>›</span>
                    <span>Thêm danh mục</span>
                </div>
                <h1 id="page-title" class="cafe-page-title">Thêm danh mục</h1>
                <p class="cafe-page-subtitle">Nhập thông tin cho danh mục thực đơn mới.</p>
            </div>
            <div>
                <a href="{{ route('admin.categories.index') }}" class="cafe-btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="cafe-card category-panel category-form">
            @csrf
            @include('admin.categories._form', ['category' => null])
        </form>
    </section>
@endsection
