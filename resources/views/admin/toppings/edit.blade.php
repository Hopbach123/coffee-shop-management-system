@extends('layouts.internal')
@section('title', 'Sửa topping')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Sửa topping</h1>
        <p>Chỉnh sửa thông tin của {{ $topping->name }}.</p>
        <form method="POST" action="{{ route('admin.toppings.update', $topping) }}" class="category-panel category-form">
            @csrf
            @method('PUT')
            @include('admin.toppings._form')
        </form>
    </section>
@endsection
