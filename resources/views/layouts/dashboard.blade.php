@php
    $dashboardEvent = $activePeriod ?? null;
    $isAuthenticated = auth()->check();
    $isMonitoringPage = request()->routeIs('monitoring.*');
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Dashboard admin Yudisium Fakultas Teknik Universitas Mulawarman">
    <title>@yield('title', 'Dashboard Yudisium')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/metisMenu/metisMenu.css') }}">
    <link rel="stylesheet" href="{{ asset('assets-template/assets/css/main.css') }}">

    <style>
        :root {
            --primary: #d97706;
            --primary-color: #d97706;
            --primary-color2: #b45309;
            --primary-color3: #f59e0b;
            --secondary-color: #92400e;
            --secondary-color2: #78350f;
            --link-color: #b45309;
            --primary-gradient: #d97706;
            --text: #1f2937;
            --muted: #6b7280;
            --line: rgba(15, 23, 42, 0.1);
            --line-strong: rgba(15, 23, 42, 0.18);
            --panel: #ffffff;
            --panel-soft: #f7f9fc;
            --accent: #d97706;
            --accent-strong: #b45309;
            --good: #0f766e;
            --warn: #b45309;
            --bad: #b91c1c;
            --shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
        }

        body {
            color: var(--text);
            background: #f4f6f8;
        }

        .navbar-brand a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            letter-spacing: 0;
            color: #1f2937;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .navbar-status {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: var(--accent-strong);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
        }

        .navbar-status i {
            color: var(--accent);
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .top-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: #f3f4f6;
            color: #374151;
            font-size: 13px;
            font-weight: 700;
        }

        .top-action:hover,
        .top-action:focus {
            color: var(--accent-strong);
            background: #fff7ed;
            text-decoration: none;
        }

        #main-content {
            padding-top: 0;
            background: #f4f6f8;
        }

        #main-content .container-fluid {
            padding-bottom: 36px;
        }

        .block-header {
            margin-bottom: 24px;
        }

        .block-header h2 {
            margin-bottom: 8px;
            font-size: 24px;
            font-weight: 800;
            color: var(--accent);
        }

        .page_action .btn + .btn {
            margin-left: 8px;
        }

        .yud-field label {
            margin: 0 0 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
        }

        .yud-field input,
        .yud-field textarea,
        .yud-field select {
            width: 100%;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            background: #fff;
        }

        .yud-field textarea {
            min-height: 100px;
            resize: vertical;
        }

        .yud-link-box {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .yud-link-box input {
            flex: 1;
            font-size: 12px;
            background: #f8fafc;
        }

        .badge.good { background: rgba(15,118,110,0.1); color: #0f766e; }
        .badge.warn { background: rgba(180,83,9,0.1); color: #b45309; }
        .badge.bad  { background: rgba(185,28,28,0.1); color: #b91c1c; }

        a {
            color: var(--accent);
        }

        a:hover,
        a:focus {
            color: var(--accent-strong);
        }

        .btn-primary,
        .btn-secondary,
        .btn-success {
            background: var(--accent) !important;
            border-color: var(--accent) !important;
            color: #fff !important;
            box-shadow: none !important;
        }

        .btn-primary:hover,
        .btn-primary:focus,
        .btn-secondary:hover,
        .btn-secondary:focus,
        .btn-success:hover,
        .btn-success:focus {
            background: var(--accent-strong) !important;
            border-color: var(--accent-strong) !important;
            color: #fff !important;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: none;
        }

        .card .header {
            border-bottom: 1px solid #eef0f3;
        }

        .card .header h2 {
            color: #111827;
            font-weight: 800;
            letter-spacing: 0;
        }

        .app-flash {
            margin-bottom: 18px;
        }

        .app-flash .alert {
            margin: 0;
        }

        /* Card: hindari konflik class .body & pastikan tinggi compact */
        #main-content .card {
            height: auto !important;
            min-height: 0 !important;
            margin-bottom: 1.25rem;
        }

        #main-content .card .body,
        #main-content .card .card-body {
            margin-bottom: 0 !important;
            margin-top: 0 !important;
            padding: 1rem 1.25rem !important;
        }

        #main-content .card .header + .card-body,
        #main-content .card .header + .body {
            padding-top: 0.75rem !important;
        }

        #main-content .card.number-chart .card-body,
        #main-content .card.number-chart .body,
        #main-content .card.top_widget .card-body,
        #main-content .card.top_widget .body {
            padding: 1rem 1.25rem !important;
        }

        #main-content .card.number-chart h4,
        #main-content .card.top_widget h4.number {
            font-size: 1.5rem;
            line-height: 1.2;
            margin-bottom: 0;
        }

        #main-content .card.number-chart .text-uppercase,
        #main-content .card.top_widget .text-uppercase {
            font-size: 11px;
            letter-spacing: 0.06em;
            color: #9ca3af;
            display: block;
        }

        #main-content .card.top_widget .card-body,
        #main-content .card.top_widget .body {
            position: relative;
            padding-right: 4.5rem !important;
        }

        #main-content .card.top_widget .icon {
            background: #fff7ed;
            color: var(--accent);
        }

        #main-content .row + .row {
            margin-top: 0;
        }

        #main-content .row > [class*='col-'] {
            margin-bottom: 0;
        }

        /* Fix: template metismenu pakai flex-grow sehingga item menu membesar & timbul gap */
        .sidebar .sidebar-nav .metismenu {
            display: block;
        }

        .sidebar .sidebar-nav .metismenu > li {
            flex: none !important;
            display: block !important;
            border: 0 !important;
            margin: 0;
        }

        .sidebar .sidebar-nav .metismenu li li {
            flex: none !important;
            display: block !important;
        }

        .sidebar .sidebar-nav .metismenu > li.active {
            border: 1px solid var(--accent) !important;
            border-radius: 8px;
            margin-bottom: 4px;
        }

        .sidebar .sidebar-nav .metismenu > li.active > a,
        .sidebar .sidebar-nav .metismenu > li.active > a i,
        .sidebar .sidebar-nav .metismenu a:hover,
        .sidebar .sidebar-nav .metismenu a:hover i {
            color: var(--accent) !important;
        }

        .sidebar .sidebar-nav .metismenu > li.active > ul {
            margin-bottom: 0 !important;
            padding-bottom: 4px;
        }

        .sidebar .sidebar-nav .metismenu > li > a {
            padding: 10px 16px;
        }

        .sidebar .sidebar-nav .metismenu ul a {
            padding: 5px 15px 5px 44px !important;
            line-height: 1.35;
        }

        .sidebar .sidebar-nav .metismenu ul a::before {
            top: 5px;
        }

        .sidebar .sidebar-nav .metismenu ul ul > li > a {
            padding: 5px 15px 5px 52px !important;
            font-size: 13px;
        }

        .sidebar .sidebar-nav .metismenu ul ul ul a {
            padding: 4px 15px 4px 64px !important;
            font-size: 12px;
        }

        .icon-menu-button {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            border: 0;
            background: transparent;
            color: inherit;
            padding: 0;
            cursor: pointer;
            text-align: left;
        }

        .icon-menu-form {
            margin: 0;
        }

        .sidebar .user-account {
            padding-bottom: 8px;
        }

        .sidebar .sidebar-scroll {
            padding-top: 8px;
        }

        .sidebar #left-sidebar-nav {
            padding: 0 8px;
        }

        .sidebar .user-account img.user-photo {
            border: 1px solid #fed7aa;
            box-shadow: none;
        }

        .admin-pagination {
            margin-top: 16px;
        }

        .admin-pagination nav {
            display: flex;
            justify-content: flex-end;
        }

        .admin-pagination .pagination {
            margin: 0;
            flex-wrap: wrap;
            gap: 6px;
        }

        .admin-pagination .page-item {
            margin: 0;
        }

        .admin-pagination .page-link,
        .admin-pagination .page-item span {
            min-width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px !important;
            border: 1px solid #e5e7eb;
            color: var(--text);
            font-size: 12px;
            font-weight: 700;
            box-shadow: none;
        }

        .admin-pagination .page-link:hover {
            color: var(--accent-strong);
            background: rgba(217, 119, 6, 0.08);
            border-color: rgba(217, 119, 6, 0.18);
        }

        .admin-pagination .page-item.active .page-link {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .admin-pagination .page-item.disabled .page-link,
        .admin-pagination .page-item.disabled span {
            color: var(--muted);
            background: #f8fafc;
        }

        .admin-pagination svg {
            width: 14px !important;
            height: 14px !important;
        }
    </style>
    @stack('head')
</head>

<body data-theme="light" class="font-nunito">
<div id="wrapper" class="admin-orange">
    <div class="page-loader-wrapper">
        <div class="loader">
            <div class="m-t-30">
                <img src="{{ asset('Unmul.png') }}" width="54" height="54" alt="Logo Universitas Mulawarman">
            </div>
            <p>Memuat dashboard...</p>
        </div>
    </div>

    <nav class="navbar navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-brand">
                <button type="button" class="btn-toggle-offcanvas"><i class="fa fa-bars"></i></button>
                <button type="button" class="btn-toggle-fullwidth"><i class="fa fa-bars"></i></button>
                <a href="{{ $isAuthenticated ? route('admin.dashboard') : route('checkin.form') }}">
                    <span>Yudisium FT UNMUL</span>
                </a>
            </div>

            @unless ($isMonitoringPage)
                <div class="navbar-right">
                    <div class="navbar-status">
                        <i class="fa fa-calendar-check-o" aria-hidden="true"></i>
                        <span>{{ $dashboardEvent?->archive_title ?: 'Belum ada event aktif' }}</span>
                    </div>
                    <div class="top-actions">
                        <a href="{{ route('home') }}" class="top-action">
                            <i class="fa fa-envelope-open-o"></i>
                            <span>Undangan</span>
                        </a>
                        <a href="{{ $isAuthenticated ? route('admin.checkin.manual.index') : route('checkin.form') }}" class="top-action">
                            <i class="fa fa-check-square-o"></i>
                            <span>Check-in</span>
                        </a>
                        @if ($isAuthenticated)
                            <a href="{{ route('monitoring.index') }}" class="top-action">
                                <i class="fa fa-line-chart"></i>
                                <span>Monitoring</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="top-action">
                                <i class="fa fa-lock"></i>
                                <span>Login</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endunless
        </div>
    </nav>

    <div id="left-sidebar" class="sidebar">
        <button type="button" class="btn-toggle-offcanvas"><i class="fa fa-arrow-left"></i></button>
        <div class="sidebar-scroll">
            <div class="user-account">
                <img src="{{ asset('Unmul.png') }}" class="rounded-circle user-photo" alt="Universitas Mulawarman">
                <div class="dropdown">
                    <span>Welcome,</span>
                    <a href="javascript:void(0);" class="dropdown-toggle user-name" data-toggle="dropdown">
                        <strong>{{ $isAuthenticated ? auth()->user()->email : 'Panitia Yudisium' }}</strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right account">
                        <li><a href="{{ route('home') }}"><i class="fa fa-envelope-open-o"></i>Undangan Publik</a></li>
                        <li><a href="{{ $isAuthenticated ? route('admin.checkin.manual.index') : route('checkin.form') }}"><i class="fa fa-check-square-o"></i>Check-in</a></li>
                        @if ($isAuthenticated)
                            <li><a href="{{ route('monitoring.index') }}"><i class="fa fa-line-chart"></i>Monitoring</a></li>
                            <li class="divider"></li>
                            <li>
                                <form class="icon-menu-form" method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="icon-menu-button" type="submit"><i class="fa fa-power-off"></i>Logout</button>
                                </form>
                            </li>
                        @else
                            <li><a href="{{ route('login') }}"><i class="fa fa-lock"></i>Login Admin</a></li>
                        @endif
                    </ul>
                </div>
            </div>

            @include('layouts.partials.sidebar-menu')
        </div>
    </div>

    <div id="main-content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="app-flash">
                    <div class="alert alert-success">{{ session('success') }}</div>
                </div>
            @endif

            @if (session('warning'))
                <div class="app-flash">
                    <div class="alert alert-warning">{{ session('warning') }}</div>
                </div>
            @endif

            @if (session('error'))
                <div class="app-flash">
                    <div class="alert alert-danger">{{ session('error') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="app-flash">
                    <div class="alert alert-danger">
                        <strong>Ada input yang perlu diperbaiki.</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </div>
</div>

<script src="{{ asset('assets-template/assets/bundles/libscripts.bundle.js') }}"></script>
<script src="{{ asset('assets-template/assets/bundles/vendorscripts.bundle.js') }}"></script>
<script src="{{ asset('assets-template/assets/bundles/mainscripts.bundle.js') }}"></script>
<script>
    (function () {
        var slugify = function (value) {
            return String(value || '')
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/&/g, ' dan ')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-{2,}/g, '-');
        };

        document.querySelectorAll('form').forEach(function (form) {
            var slugInput = form.querySelector('input[name="slug"]');
            if (!slugInput) return;

            var sourceInput = form.querySelector('[data-slug-source], input[name="name"], input[name="title"]');
            if (!sourceInput || sourceInput === slugInput) return;

            var manualSlug = false;
            var applySlug = function () {
                if (manualSlug) return;
                slugInput.value = slugify(sourceInput.value);
            };

            sourceInput.addEventListener('input', applySlug);
            slugInput.addEventListener('input', function () {
                manualSlug = true;
                slugInput.value = slugify(slugInput.value);
            });

            form.addEventListener('submit', function () {
                if (!slugInput.value.trim()) {
                    slugInput.value = slugify(sourceInput.value);
                } else {
                    slugInput.value = slugify(slugInput.value);
                }
            });

            if (!slugInput.value.trim()) {
                applySlug();
            }
        });
    })();
</script>
@stack('scripts')
</body>
</html>
