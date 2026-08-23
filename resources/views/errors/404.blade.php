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
      --text: #111827;
      --muted: #6b7280;
      --line: #e5e7eb;
      --surface: rgba(255, 255, 255, 0.94);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      min-height: 100%;
    }

    body {
      min-height: 100vh;
      min-height: 100dvh;
      font-family: "Manrope", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color: var(--text);
      background: #f3f4f6;
      display: grid;
      place-items: center;
      padding: 18px;
      overflow-x: hidden;
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
      border-radius: 22px;
      border: 1px solid rgba(255, 255, 255, 0.9);
      background: var(--surface);
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
      line-height: 1.1;
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
      min-height: 46px;
      border-radius: 12px;
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
