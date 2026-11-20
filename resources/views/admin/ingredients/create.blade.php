@extends('layouts.internal')
@section('title', 'Thêm nguyên liệu')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <a href="{{ route('admin.ingredients.index') }}">Kho & Nguyên liệu</a>
                    <span>›</span>
                    <span>Thêm nguyên liệu</span>
                </div>
                <h1 id="page-title" class="cafe-page-title">Thêm nguyên liệu</h1>
                <p class="cafe-page-subtitle">Nhập thông tin nguyên liệu mới. Tồn kho ban đầu bằng 0.</p>
            </div>
            <div>
                <a href="{{ route('admin.ingredients.index') }}" class="cafe-btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.ingredients.store') }}" class="cafe-card category-panel category-form">
            @csrf
            @include('admin.ingredients._form', ['ingredient' => null])
        </form>
    </section>
@endsection
