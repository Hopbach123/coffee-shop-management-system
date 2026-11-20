@extends('layouts.internal')
@section('title', 'Sửa nguyên liệu')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <a href="{{ route('admin.ingredients.index') }}">Kho & Nguyên liệu</a>
                    <span>›</span>
                    <span>Sửa nguyên liệu</span>
                </div>
                <h1 id="page-title" class="cafe-page-title">Sửa nguyên liệu</h1>
                <p class="cafe-page-subtitle">Chỉnh sửa thông tin của &ldquo;{{ $ingredient->name }}&rdquo;.</p>
            </div>
            <div>
                <a href="{{ route('admin.ingredients.index') }}" class="cafe-btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.ingredients.update', $ingredient) }}" class="cafe-card category-panel category-form">
            @csrf
            @method('PUT')
            @include('admin.ingredients._form', ['ingredient' => $ingredient])
        </form>
    </section>
@endsection
