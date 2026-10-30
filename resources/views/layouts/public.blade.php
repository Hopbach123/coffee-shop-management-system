<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="A warm, independent coffeehouse experience shaped around careful craft and quiet moments.">
        <title>@yield('title', 'Maison du Café')</title>
        @if (is_file(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/css/public/storefront.css', 'resources/js/app.js'])
        @else
            <link rel="stylesheet" href="{{ asset('assets/css/app.bundle.css') }}">
            <link rel="stylesheet" href="{{ asset('assets/css/storefront.bundle.css') }}">
            <script src="{{ asset('assets/js/app.bundle.js') }}" defer></script>
        @endif
    </head>
    <body class="coffee-page">
        <a class="skip-link" href="#main-content">Đến nội dung chính</a>
        <x-public.navbar />
        <main id="main-content">
            @yield('content')
        </main>
        <x-public.footer />
        <x-public.chatbox />
    </body>
</html>