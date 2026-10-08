<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Đăng nhập — Maison du Café')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="coffee-page auth-page">
        <a class="skip-link" href="#main-content">Đến nội dung chính</a>
        <header class="auth-header coffee-container">
            <x-public.brand />
            <a href="{{ url('/') }}">Về trang chủ</a>
        </header>
        <main id="main-content" class="auth-main coffee-container">
            @yield('content')
        </main>
    </body>
</html>
