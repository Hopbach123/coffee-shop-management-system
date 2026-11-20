@extends('layouts.internal')
@section('title', 'Sửa topping')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <div class="cafe-page-header">
            <div>
                <div class="cafe-breadcrumbs">
                    <a href="{{ route('admin.home') }}">Dashboard</a>
                    <span>›</span>
                    <a href="{{ route('admin.toppings.index') }}">Topping</a>
                    <span>›</span>
                    <span>Sửa topping</span>
                </div>
                <h1 id="page-title" class="cafe-page-title">Sửa topping</h1>
                <p class="cafe-page-subtitle">Chỉnh sửa thông tin của &ldquo;{{ $topping->name }}&rdquo;.</p>
            </div>
            <div>
                <a href="{{ route('admin.toppings.index') }}" class="cafe-btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.toppings.update', $topping) }}" class="cafe-card category-panel category-form">
            @csrf
            @method('PUT')
            @include('admin.toppings._form', ['topping' => $topping])
        </form>
    </section>
@endsection
