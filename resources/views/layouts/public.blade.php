<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'FinAegis'))</title>

    @include('partials.favicon')
    
    @hasSection('seo')
        @yield('seo')
    @else
        @include('partials.seo', [
            'title' => config('app.name', 'FinAegis'),
            'description' => 'FinAegis - The Enterprise Financial Platform Powering the Future of Banking.',
            'keywords' => 'FinAegis, banking platform, fintech, core banking system',
        ])
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Custom Styles -->
    @stack('styles')
</head>
<body class="font-sans antialiased">
    <x-platform-banners />
    
    <!-- Navigation -->
    @include('partials.public-nav')
    
    <!-- Page Content -->
    <main>
        @yield('content')
    </main>
    
    <!-- Footer -->
    @include('partials.footer')
    
    @stack('scripts')
</body>
</html>