<!doctype html>
<html lang="id">
<head>
    <title>Login Admin Yudisium</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Login admin Yudisium Fakultas Teknik Universitas Mulawarman">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/font-awesome/css/font-awesome.min.css') }}">

    <style>
        :root {
            --orange: #F5530D;
            --orange-dark: #D9450B;
            --ink: #1c1c1e;
            --muted: #636366;
            --line: rgba(60, 60, 67, 0.16);
            --soft-orange: #FFF3EE;
            --spring: cubic-bezier(0.32, 0.72, 0, 1);
            --press: 100ms ease-out;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
            font-optical-sizing: auto;
        }

        body {
            margin: 0;
            font-family: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
            background: #111827;
            color: var(--ink);
            letter-spacing: 0;
            line-height: 1.5;
            -webkit-tap-highlight-color: transparent;
        }

        .login-page {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 32px 16px;
            overflow: hidden;
        }

        .login-video {
            position: fixed;
            inset: 0;
            z-index: -3;
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: url('{{ asset('assets-template/assets/images/university_auth_bg.jpg') }}') center/cover no-repeat;
        }

        .video-shade {
            position: fixed;
            inset: 0;
            z-index: -2;
            background: rgba(17, 24, 39, 0.58);
        }

        .login-card {
            width: min(100%, 470px);
            padding: 38px 42px 42px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-top-color: rgba(255, 255, 255, 0.88);
            box-shadow: 0 22px 70px rgba(17, 24, 39, 0.28);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
        }

        .brand {
            margin-bottom: 28px;
            text-align: center;
        }

        .brand-logo {
            width: 86px;
            height: 86px;
            object-fit: contain;
            margin-bottom: 18px;
        }

        .brand-kicker {
            margin: 0 0 10px;
            color: var(--orange);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .brand h1 {
            margin: 0;
            color: #1c1c1e;
            font-size: 30px;
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -0.03em;
        }

        .brand-copy {
            margin: 20px 0 0;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.6;
        }

        .alert {
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 15px;
            line-height: 1.55;
        }

        .alert-success {
            color: #166534;
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .alert-danger {
            color: #9a3412;
            background: var(--soft-orange);
            border-color: #fed7aa;
        }

        .form-auth-small {
            margin: 0;
        }

        .field-group {
            margin-bottom: 16px;
        }

        .field-label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-size: 14px;
            font-weight: 700;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .form-control {
            height: 54px;
            padding: 0 52px 0 16px;
            color: #1c1c1e;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: none;
            font-size: 15px;
        }

        .input-wrap .form-control:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(245, 83, 13, 0.12);
            outline: none;
        }

        .input-icon {
            position: absolute;
            top: 1px;
            right: 1px;
            display: grid;
            place-items: center;
            width: 50px;
            height: 52px;
            color: #7c8175;
            background: #f8fafc;
            border-left: 1px solid var(--line);
            border-radius: 0 7px 7px 0;
            pointer-events: none;
        }

        .form-row-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-top: 18px;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: #5f6b6d;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }

        .remember input {
            width: 20px;
            height: 20px;
            margin: 0;
            accent-color: var(--orange);
        }

        .login-button {
            min-width: 120px;
            min-height: 48px;
            height: 56px;
            padding: 0 26px;
            color: #fff;
            background: var(--orange);
            border: 0;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 800;
            box-shadow: 0 10px 24px rgba(245, 83, 13, 0.26);
            transition: background-color 180ms var(--spring), transform var(--press), box-shadow 180ms var(--spring);
            touch-action: manipulation;
        }

        .login-button:hover,
        .login-button:focus {
            color: #fff;
            background: var(--orange-dark);
            box-shadow: 0 10px 22px rgba(245, 83, 13, 0.22);
        }

        .login-button:active {
            transform: scale(0.97);
        }

        @media (hover: hover) and (pointer: fine) {
            .login-button:hover,
            .login-button:focus {
                transform: translateY(-1px);
            }
        }

        .login-button:focus-visible,
        .public-link:focus-visible {
            outline: 2px solid var(--orange);
            outline-offset: 3px;
        }

        .public-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 26px;
            color: var(--orange-dark);
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }

        .public-link:hover,
        .public-link:focus {
            color: #92400e;
            text-decoration: none;
        }

        @media (max-width: 520px) {
            .login-page {
                align-items: flex-start;
                padding-top: 24px;
            }

            .login-card {
                padding: 30px 22px 32px;
                border-radius: 20px;
            }

            .brand-logo {
                width: 76px;
                height: 76px;
            }

            .brand h1 {
                font-size: 26px;
            }

            .brand-copy {
                font-size: 15px;
            }

            .form-row-actions {
                align-items: stretch;
                flex-direction: column;
                gap: 16px;
            }

            .login-button {
                width: 100%;
            }
        }

        @media (min-width: 700px) and (min-height: 700px) {
            .login-page {
                padding: 48px 24px;
            }

            .login-card {
                width: min(100%, 480px);
            }
        }

        @media (max-height: 520px) and (orientation: landscape) {
            .login-page {
                align-items: stretch;
                padding: 16px;
            }

            .login-card {
                padding: 20px 24px 24px;
            }

            .brand {
                margin-bottom: 14px;
            }

            .brand-logo {
                width: 56px;
                height: 56px;
                margin-bottom: 8px;
            }

            .brand h1 {
                font-size: 22px;
            }

            .brand-copy {
                margin-top: 8px;
                font-size: 14px;
            }

            .form-row-actions {
                flex-direction: row;
                align-items: center;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .login-button {
                transition: background-color 200ms ease;
                transform: none !important;
            }
        }

        @media (prefers-reduced-transparency: reduce) {
            .login-card {
                background: #ffffff;
                backdrop-filter: none;
                -webkit-backdrop-filter: none;
            }
        }

        @media (prefers-contrast: more) {
            .login-card {
                background: #ffffff;
                border: 2px solid #1c1c1e;
            }
        }
    </style>
</head>

<body>
    <main class="login-page">
        <video class="login-video" autoplay muted loop playsinline poster="{{ asset('assets-template/assets/images/university_auth_bg.jpg') }}">
            <source src="{{ asset('video-back.mp4') }}" type="video/mp4">
        </video>
        <div class="video-shade"></div>

        <section class="login-card" aria-labelledby="login-title">
            <div class="brand">
                <img class="brand-logo" src="{{ asset('Unmul.png') }}" alt="Universitas Mulawarman">
                <p class="brand-kicker">Yudisium FT</p>
                <h1 id="login-title">Masuk Admin</h1>
                <p class="brand-copy">Masuk ke dashboard admin undangan Yudisium Fakultas Teknik Universitas Mulawarman.</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form class="form-auth-small" method="post" action="{{ route('login.store') }}">
                @csrf
                <div class="field-group">
                    <label for="signin-email" class="field-label">Email</label>
                    <div class="input-wrap">
                        <input type="email" class="form-control" id="signin-email" name="email" value="{{ old('email') }}" placeholder="Masukkan email admin" autocomplete="email" required>
                        <span class="input-icon" aria-hidden="true"><i class="fa fa-envelope"></i></span>
                    </div>
                </div>

                <div class="field-group">
                    <label for="signin-password" class="field-label">Password</label>
                    <div class="input-wrap">
                        <input type="password" class="form-control" id="signin-password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                        <span class="input-icon" aria-hidden="true"><i class="fa fa-lock"></i></span>
                    </div>
                </div>

                <div class="form-row-actions">
                    <label class="remember">
                        <input type="checkbox" name="remember" value="1">
                        <span>Ingat saya</span>
                    </label>
                    <button type="submit" class="login-button">Login</button>
                </div>

                <a class="public-link" href="{{ route('home') }}">
                    <i class="fa fa-arrow-left" aria-hidden="true"></i>
                    <span>Kembali ke undangan publik</span>
                </a>
            </form>
        </section>
    </main>
</body>
</html>
