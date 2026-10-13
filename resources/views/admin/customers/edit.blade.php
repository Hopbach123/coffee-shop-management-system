@extends('layouts.internal')
@section('title', 'Chỉnh sửa khách hàng')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Chỉnh sửa khách hàng</h1>
        <p>Cập nhật thông tin hồ sơ khách hàng. Giữ ít nhất họ tên, số điện thoại hoặc email.</p>
        <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="category-panel category-form">
            @csrf
            @method('PUT')
            @if ($errors->any())
                <div class="category-notice category-notice--error" role="alert">Vui lòng kiểm tra các trường được đánh dấu bên dưới.</div>
            @endif
            <div class="category-fields">
                <div class="category-field">
                    <label for="name">Họ tên</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $customer->name) }}" maxlength="255" autocomplete="name" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name') <p class="category-field__error" id="name-error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="phone">Số điện thoại</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone', $customer->phone) }}" maxlength="20" autocomplete="tel" aria-describedby="phone-help @error('phone') phone-error @enderror" @error('phone') aria-invalid="true" @enderror>
                    <p id="phone-help">Tối đa 20 ký tự: chữ số, dấu +, khoảng trắng, dấu ngoặc và dấu -.</p>
                    @error('phone') <p class="category-field__error" id="phone-error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}" maxlength="255" autocomplete="email" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email') <p class="category-field__error" id="email-error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="birthday">Ngày sinh</label>
                    <input id="birthday" name="birthday" type="date" value="{{ old('birthday', $customer->birthday?->format('Y-m-d')) }}" autocomplete="bday" @error('birthday') aria-invalid="true" aria-describedby="birthday-error" @enderror>
                    @error('birthday') <p class="category-field__error" id="birthday-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="category-form-actions">
                <x-public.button type="submit">Chỉnh sửa khách hàng</x-public.button>
                <x-public.button href="{{ route('admin.customers.index') }}" variant="outline">Hủy</x-public.button>
            </div>
        </form>
    </section>
@endsection
