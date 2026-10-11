@extends('layouts.internal')
@section('title', 'Sửa danh mục')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Sửa danh mục</h1>
        <p>Chỉnh sửa thông tin của {{ $category->name }}.</p>
        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="category-panel category-form">
            @csrf
            @method('PUT')
            @include('admin.categories._form', ['category' => $category])
        </form>
    </section>
@endsection
