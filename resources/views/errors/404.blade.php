<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Halaman Tidak Ditemukan - Undangan Yudisium FT UNMUL</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #F5530D;
      --primary-deep: #D9450B;
      --text: #1c1c1e;
      --muted: #636366;
      --line: rgba(60, 60, 67, 0.16);
      --surface: rgba(255, 255, 255, 0.78);
      --press: 100ms ease-out;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      min-height: 100%;
      font-optical-sizing: auto;
    }

    body {
      min-height: 100vh;
      min-height: 100dvh;
      font-family: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
      color: var(--text);
      background: #f3f4f6;
      display: grid;
      place-items: center;
      padding: 18px;
      overflow-x: hidden;
      letter-spacing: 0;
      line-height: 1.5;
      -webkit-tap-highlight-color: transparent;
    }

    .bg-video-layer,
    .bg-video-overlay {
      position: fixed;
      inset: 0;
      pointer-events: none;
    }

    .bg-video-layer {
      z-index: 0;
      overflow: hidden;
      background: #f3f4f6;
    }

    .bg-video-layer video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0.34;
      filter: brightness(1.08) saturate(0.85) contrast(0.95);
    }

    .bg-video-overlay {
      z-index: 1;
      background: rgba(255, 255, 255, 0.72);
    }

    .not-found-card {
      position: relative;
      z-index: 2;
      width: min(100%, 520px);
      padding: 34px 28px 30px;
      border-radius: 24px;
      border: 1px solid rgba(255, 255, 255, 0.55);
      border-top-color: rgba(255, 255, 255, 0.88);
      background: var(--surface);
      backdrop-filter: blur(24px) saturate(180%);
      -webkit-backdrop-filter: blur(24px) saturate(180%);
      box-shadow: 0 20px 56px rgba(15, 23, 42, 0.12);
      text-align: center;
    }

    .logo-frame {
      width: 82px;
      height: 82px;
      margin: 0 auto 18px;
      border-radius: 999px;
      border: 1px solid var(--line);
      background: #fff;
      display: grid;
      place-items: center;
      padding: 10px;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }

    .logo-frame img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .code {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 72px;
      height: 34px;
      padding: 0 14px;
      border-radius: 999px;
      border: 1px solid rgba(245, 83, 13, 0.24);
      background: #fff3ee;
      color: var(--primary-deep);
      font-weight: 800;
      letter-spacing: 0.12em;
      margin-bottom: 14px;
    }

    h1 {
      font-size: clamp(1.8rem, 5vw, 2.35rem);
      line-height: 1.05;
      letter-spacing: -0.03em;
      margin-bottom: 12px;
      font-weight: 800;
    }

    p {
      color: var(--muted);
      line-height: 1.7;
      font-size: 0.98rem;
      margin: 0 auto 22px;
      max-width: 36rem;
    }

    .actions {
      display: flex;
      justify-content: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .btn {
      min-height: 44px;
      border-radius: 14px;
      padding: 12px 18px;
      border: 1px solid transparent;
      background: var(--primary);
      color: #fff;
      font-weight: 800;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 22px rgba(245, 83, 13, 0.18);
      touch-action: manipulation;
      transition: transform var(--press), background 180ms ease;
    }

    .btn:active {
      transform: scale(0.97);
    }

    .btn:focus-visible {
      outline: 2px solid var(--primary);
      outline-offset: 3px;
    }

    .btn.secondary {
      background: #fff;
      color: var(--text);
      border-color: var(--line);
      box-shadow: none;
    }

    @media (max-width: 480px) {
      body {
        padding: 14px;
      }

      .not-found-card {
        padding: 28px 20px 24px;
      }

      .actions {
        display: grid;
      }
    }

    @media (min-width: 700px) and (min-height: 700px) {
      .not-found-card {
        width: min(100%, 560px);
        padding: 40px 36px 34px;
      }
    }

    @media (max-height: 520px) and (orientation: landscape) {
      body {
        padding: 12px;
      }

      .not-found-card {
        padding: 22px 24px 20px;
      }

      h1 {
        font-size: 1.5rem;
        margin-bottom: 8px;
      }

      p {
        margin-bottom: 14px;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .btn {
        transition: background 200ms ease;
        transform: none !important;
      }

      .bg-video-layer video {
        display: none;
      }
    }

    @media (prefers-reduced-transparency: reduce) {
      .not-found-card {
        background: #ffffff;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
      }
    }
  </style>
</head>
<body>
  <div class="bg-video-layer" aria-hidden="true">
    <video autoplay muted loop playsinline preload="auto">
      <source src="{{ asset('video-back.mp4') }}" type="video/mp4">
    </video>
  </div>
  <div class="bg-video-overlay" aria-hidden="true"></div>

  <main class="not-found-card">
    <div class="logo-frame">
      <img src="{{ asset('Unmul.png') }}" alt="Logo Universitas Mulawarman">
    </div>
    <span class="code">404</span>
    <h1>Link undangan tidak valid</h1>
    <p>
      Pastikan link yang dibuka sudah lengkap dan berasal dari panitia.
    </p>
    <div class="actions">
      <a class="btn" href="{{ route('home') }}">Buka Arsip Yudisium</a>
      <a class="btn secondary" href="javascript:history.back()">Kembali</a>
    </div>
  </main>
</body>
</html>
