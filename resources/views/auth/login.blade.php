<!doctype html>
<html lang="id">
<head>
    <title>Login Admin Yudisium</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Login admin Yudisium Fakultas Teknik Universitas Mulawarman">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/font-awesome/css/font-awesome.min.css') }}">

    <style>
        :root {
            --orange: #d97706;
            --orange-dark: #b85f00;
            --ink: #1f2933;
            --muted: #6b7280;
            --line: #e5e7eb;
            --soft-orange: #fff7ed;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            font-family: "Nunito Sans", "Nunito", Arial, sans-serif;
            background: #111827;
            color: var(--ink);
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
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 22px 70px rgba(17, 24, 39, 0.36);
            backdrop-filter: blur(10px);
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
            color: #17211d;
            font-size: 30px;
            font-weight: 800;
            line-height: 1.18;
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
            color: #111827;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: none;
            font-size: 15px;
        }

        .input-wrap .form-control:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.12);
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
            height: 56px;
            padding: 0 26px;
            color: #fff;
            background: var(--orange);
            border: 0;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 800;
            box-shadow: 0 14px 26px rgba(217, 119, 6, 0.26);
            transition: background-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
        }

        .login-button:hover,
        .login-button:focus {
            color: #fff;
            background: var(--orange-dark);
            box-shadow: 0 12px 22px rgba(217, 119, 6, 0.22);
            transform: translateY(-1px);
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
                border-radius: 8px;
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
