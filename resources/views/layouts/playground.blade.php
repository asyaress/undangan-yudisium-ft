<!DOCTYPE html>
<html lang="id" class="app-booting" data-app-boot="formal">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <meta name="color-scheme" content="light">
    @include('partials.social-share-meta')
    <title>{{ $pageTitle ?? 'Playground Undangan Yudisium FT UNMUL' }}</title>
    <link rel="preload" href="{{ asset('Unmul.png') }}" as="image">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"></noscript>
    @stack('head')
    @include('partials.app-boot-inline')
</head>
<body>
    @include('partials.app-boot-screen')
    @yield('content')
    @stack('scripts')
</body>
</html>
