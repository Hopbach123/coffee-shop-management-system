@extends('layouts.internal')
@section('title', 'Thêm topping')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <a href="{{ route('admin.toppings.index') }}">Topping</a>
                    <span>›</span>
                    <span>Thêm topping</span>
                </div>
                <h1 id="page-title" class="cafe-page-title">Thêm topping</h1>
                <p class="cafe-page-subtitle">Nhập tên, giá bán và trạng thái cho món topping mới.</p>
            </div>
            <div>
                <a href="{{ route('admin.toppings.index') }}" class="cafe-btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.toppings.store') }}" class="cafe-card category-panel category-form">
            @csrf
            @include('admin.toppings._form', ['topping' => null])
        </form>
    </section>
@endsection
