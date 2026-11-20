@extends('layouts.internal')
@section('title', 'Sửa danh mục')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <a href="{{ route('admin.categories.index') }}">Danh mục</a>
                    <span>›</span>
                    <span>Sửa danh mục</span>
                </div>
                <h1 id="page-title" class="cafe-page-title">Sửa danh mục</h1>
                <p class="cafe-page-subtitle">Chỉnh sửa thông tin của &ldquo;{{ $category->name }}&rdquo;.</p>
            </div>
            <div>
                <a href="{{ route('admin.categories.index') }}" class="cafe-btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="cafe-card category-panel category-form">
            @csrf
            @method('PUT')
            @include('admin.categories._form', ['category' => $category])
        </form>
    </section>
@endsection
