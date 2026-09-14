<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="A warm, independent coffeehouse experience shaped around careful craft and quiet moments.">
        <title>@yield('title', 'Maison du Café')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="coffee-page">
        <a class="skip-link" href="#main-content">Skip to content</a>
        <x-public.navbar />
        <main id="main-content">
            @yield('content')
        </main>
        <x-public.footer />
    </body>
</html>