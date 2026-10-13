@extends('layouts.internal')
@section('title', 'Thêm nguyên liệu')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Thêm nguyên liệu</h1>
        <p>Nhập thông tin nguyên liệu mới. Tồn kho ban đầu bằng 0.</p>
        <form method="POST" action="{{ route('admin.ingredients.store') }}" class="category-panel category-form">
            @csrf
            @include('admin.ingredients._form', ['ingredient' => null])
        </form>
    </section>
@endsection
