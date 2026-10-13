@extends('layouts.internal')
@section('title', 'Sửa nguyên liệu')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Sửa nguyên liệu</h1>
        <p>Chỉnh sửa thông tin của {{ $ingredient->name }}.</p>
        <form method="POST" action="{{ route('admin.ingredients.update', $ingredient) }}" class="category-panel category-form">
            @csrf
            @method('PUT')
            @include('admin.ingredients._form')
        </form>
    </section>
@endsection
