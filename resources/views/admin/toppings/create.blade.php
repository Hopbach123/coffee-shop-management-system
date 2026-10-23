@extends('layouts.internal')
@section('title', 'Thêm topping')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Thêm topping</h1>
        <p>Nhập tên, giá bán và trạng thái cho topping mới.</p>
        <form method="POST" action="{{ route('admin.toppings.store') }}" class="category-panel category-form">
            @csrf
            @include('admin.toppings._form', ['topping' => null])
        </form>
    </section>
@endsection
