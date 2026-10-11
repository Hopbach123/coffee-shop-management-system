@extends('layouts.auth')

@section('content')
    <section class="auth-panel" aria-labelledby="login-title">
        <p class="section-heading__eyebrow">Hệ thống quản lý nội bộ</p>
        <h1 id="login-title">Đăng nhập</h1>
        <p class="auth-intro">Chào mừng trở lại. Sử dụng tài khoản được quản lý cấp để tiếp tục.</p>

        @error('authentication')
            <div class="auth-alert" role="alert">
                <strong>Đăng nhập không thành công</strong>
                <p>{{ $message }}</p>
            </div>
        @enderror

        <form class="auth-form" method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="auth-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')
                    <p class="auth-error" id="email-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="auth-field">
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password')
                    <p class="auth-error" id="password-error">{{ $message }}</p>
                @enderror
            </div>
            <x-public.button type="submit" class="auth-submit">Đăng nhập</x-public.button>
        </form>
    </section>
@endsection
