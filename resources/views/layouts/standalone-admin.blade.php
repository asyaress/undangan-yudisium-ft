<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="@yield('description', 'Halaman admin Yudisium Fakultas Teknik Universitas Mulawarman')">
    <title>@yield('title', 'Admin Yudisium')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/font-awesome/css/font-awesome.min.css') }}">
    @stack('head')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
