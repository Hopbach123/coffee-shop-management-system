@extends('layouts.internal')
@section('title', 'Thêm danh mục')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Thêm danh mục</h1>
        <p>Nhập thông tin cho danh mục mới.</p>
        <form method="POST" action="{{ route('admin.categories.store') }}" class="category-panel category-form">
            @csrf
            @include('admin.categories._form', ['category' => null])
        </form>
    </section>
@endsection
