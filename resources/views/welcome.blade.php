@extends('layouts.app')

@push('styles')
<style>
    :root {
        --invite-accent: #F5530D;
        --invite-accent-dark: #dd4608;
        --invite-ink: #111827;
        --invite-muted: #6b7280;
        --invite-cream: #fff6f1;
        --invite-line: rgba(245, 83, 13, 0.16);
    }

    body {
        background: #f7f3ef;
        color: var(--invite-ink);
    }

    .page-wrapper {
        overflow: hidden;
    }

    .invite-header {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 20;
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.72), rgba(17, 24, 39, 0));
    }

    .invite-header .navigation {
        background: transparent;
        border: 0;
        box-shadow: none;
        padding: 18px 0 0;
    }

    .invite-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #fff !important;
        text-transform: none;
    }

    .invite-brand img {
        width: 46px;
        height: 46px;
        object-fit: contain;
        filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.2));
    }

    .invite-brand span {
        display: flex;
        flex-direction: column;
        line-height: 1.05;
    }

    .invite-brand strong {
        font-size: 0.98rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .invite-brand small {
        font-size: 0.72rem;
        opacity: 0.82;
        letter-spacing: 0.18em;
        text-transform: uppercase;
    }

    .invite-nav > li > a {
        color: #fff !important;
        font-weight: 600;
        letter-spacing: 0.04em;
    }

    .invite-hero {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: stretch;
        background: #0f172a;
    }

    .invite-hero__video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: saturate(0.92) contrast(1.05);
    }

    .invite-hero__overlay {
        position: absolute;
        inset: 0;
        background:
            linear-gradient(135deg, rgba(17, 24, 39, 0.9) 0%, rgba(17, 24, 39, 0.7) 45%, rgba(245, 83, 13, 0.18) 100%),
            radial-gradient(circle at 20% 20%, rgba(245, 83, 13, 0.22), transparent 34%);
    }

    .invite-hero__content {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 140px 20px 86px;
        display: grid;
        align-items: center;
        gap: 34px;
    }

    .invite-hero__panel {
        max-width: 760px;
        color: #fff;
        padding: 42px 40px 36px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 28px;
        background: rgba(17, 24, 39, 0.34);
        backdrop-filter: blur(16px);
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.22);
    }

    .invite-kicker {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        color: #fff7f2;
        font-size: 0.82rem;
        letter-spacing: 0.22em;
        text-transform: uppercase;
    }

    .invite-kicker::before {
        content: "";
        width: 42px;
        height: 2px;
        border-radius: 999px;
        background: var(--invite-accent);
        box-shadow: 0 0 0 5px rgba(245, 83, 13, 0.12);
    }

    .invite-hero__panel h1 {
        margin: 0;
        font-size: clamp(2.5rem, 7vw, 5.5rem);
        line-height: 0.98;
        letter-spacing: -0.04em;
        text-transform: uppercase;
        text-wrap: balance;
    }

    .invite-hero__panel h2 {
        margin: 14px 0 0;
        font-size: clamp(1.1rem, 2vw, 1.75rem);
        font-weight: 600;
        color: #ffe8df;
    }

    .invite-hero__panel p {
        margin: 16px 0 0;
        max-width: 64ch;
        color: rgba(255, 255, 255, 0.82);
        font-size: 1.02rem;
        line-height: 1.8;
    }

    .invite-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 26px;
    }

    .invite-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
        font-size: 0.88rem;
        font-weight: 600;
    }

    .invite-pill--accent {
        background: var(--invite-accent);
        border-color: transparent;
        box-shadow: 0 14px 30px rgba(245, 83, 13, 0.28);
    }

    .invite-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 30px;
    }

    .invite-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 52px;
        padding: 0 20px;
        border-radius: 14px;
        font-weight: 700;
        transition: transform 0.25s ease, background-color 0.25s ease, color 0.25s ease;
    }

    .invite-btn:hover {
        transform: translateY(-1px);
    }

    .invite-btn--primary {
        background: var(--invite-accent);
        color: #fff;
        box-shadow: 0 18px 35px rgba(245, 83, 13, 0.3);
    }

    .invite-btn--secondary {
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.04);
    }

    .invite-hero__aside {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-self: end;
    }

    .invite-card {
        padding: 24px;
        border-radius: 22px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(14px);
        color: #fff;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
    }

    .invite-card strong {
        display: block;
        color: #fff7f2;
        margin-bottom: 8px;
        font-size: 0.88rem;
        letter-spacing: 0.18em;
        text-transform: uppercase;
    }

    .invite-card h3 {
        margin: 0;
        font-size: 1.15rem;
        line-height: 1.45;
    }

    .invite-card p {
        margin: 10px 0 0;
        color: rgba(255, 255, 255, 0.82);
        line-height: 1.7;
    }

    .invite-section {
        padding: 110px 0;
        background:
            linear-gradient(180deg, #fff 0%, #fffaf7 100%);
    }

    .invite-section--alt {
        background: var(--invite-cream);
    }

    .invite-section__head {
        max-width: 760px;
        margin: 0 auto 44px;
        text-align: center;
    }

    .invite-section__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: var(--invite-accent);
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.22em;
        text-transform: uppercase;
    }

    .invite-section__eyebrow::before,
    .invite-section__eyebrow::after {
        content: "";
        width: 24px;
        height: 2px;
        border-radius: 999px;
        background: var(--invite-line);
    }

    .invite-section__head h2 {
        margin: 14px 0 0;
        font-size: clamp(1.9rem, 4vw, 3rem);
        letter-spacing: -0.03em;
        color: var(--invite-ink);
    }

    .invite-section__head p {
        margin: 14px auto 0;
        max-width: 680px;
        color: var(--invite-muted);
        line-height: 1.85;
        font-size: 1rem;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 22px;
    }

    .detail-card {
        padding: 28px;
        border-radius: 24px;
        background: #fff;
        border: 1px solid rgba(17, 24, 39, 0.06);
        box-shadow: 0 18px 50px rgba(17, 24, 39, 0.06);
        height: 100%;
    }

    .detail-card__icon {
        width: 54px;
        height: 54px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, var(--invite-accent) 0%, var(--invite-accent-dark) 100%);
        margin-bottom: 18px;
        box-shadow: 0 18px 30px rgba(245, 83, 13, 0.22);
    }

    .detail-card h3 {
        margin: 0;
        font-size: 1.18rem;
        color: var(--invite-ink);
    }

    .detail-card p {
        margin: 12px 0 0;
        color: var(--invite-muted);
        line-height: 1.8;
    }

    .timeline {
        margin-top: 26px;
        display: grid;
        gap: 14px;
    }

    .timeline-item {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        padding: 18px 20px;
        border-radius: 18px;
        background: #fff;
        border: 1px solid rgba(17, 24, 39, 0.06);
    }

    .timeline-item span {
        min-width: 90px;
        color: var(--invite-accent);
        font-weight: 800;
        letter-spacing: 0.06em;
    }

    .timeline-item strong {
        display: block;
        font-size: 1rem;
        color: var(--invite-ink);
    }

    .timeline-item p {
        margin: 6px 0 0;
        color: var(--invite-muted);
    }

    .location-layout {
        display: grid;
        grid-template-columns: 1.05fr 0.95fr;
        gap: 24px;
        align-items: stretch;
    }

    .location-panel {
        border-radius: 28px;
        padding: 34px;
        background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
        color: #fff;
        box-shadow: 0 24px 60px rgba(17, 24, 39, 0.18);
    }

    .location-panel h3 {
        margin: 0;
        font-size: 1.8rem;
        letter-spacing: -0.03em;
    }

    .location-panel p {
        margin: 16px 0 0;
        color: rgba(255, 255, 255, 0.82);
        line-height: 1.9;
    }

    .location-list {
        margin: 26px 0 0;
        display: grid;
        gap: 14px;
    }

    .location-list li {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        color: rgba(255, 255, 255, 0.92);
    }

    .location-list i {
        margin-top: 4px;
        color: var(--invite-accent);
    }

    .location-cta {
        margin-top: 28px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 18px;
        border-radius: 14px;
        background: var(--invite-accent);
        color: #fff;
        font-weight: 700;
        box-shadow: 0 18px 36px rgba(245, 83, 13, 0.28);
    }

    .location-side {
        padding: 28px;
        border-radius: 28px;
        border: 1px solid rgba(245, 83, 13, 0.14);
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.96)),
            url({{ asset('assets/images/page-title.jpg') }}) center/cover no-repeat;
        min-height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: end;
    }

    .location-side__label {
        color: var(--invite-accent);
        font-weight: 800;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        font-size: 0.78rem;
    }

    .location-side h4 {
        margin: 10px 0 0;
        font-size: 1.55rem;
        letter-spacing: -0.02em;
    }

    .location-side p {
        margin: 10px 0 0;
        color: var(--invite-muted);
        line-height: 1.8;
    }

    .invite-footer {
        padding: 56px 0 72px;
        background: #111827;
        color: #fff;
    }

    .invite-footer__area {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 14px;
    }

    .invite-footer__logo {
        width: 68px;
        height: 68px;
        object-fit: contain;
    }

    .invite-footer__eyebrow {
        margin: 0;
        color: #fff7f2;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        font-size: 0.8rem;
    }

    .invite-footer h3 {
        margin: 0;
        font-size: 1.5rem;
        letter-spacing: -0.02em;
    }

    .invite-footer p {
        margin: 0;
        color: rgba(255, 255, 255, 0.72);
    }

    @media (max-width: 991px) {
        .invite-header {
            background: rgba(17, 24, 39, 0.92);
        }

        .invite-hero__content {
            padding-top: 120px;
        }

        .invite-hero__aside,
        .detail-grid,
        .location-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        .invite-header .navigation {
            padding-top: 12px;
        }

        .invite-brand strong {
            font-size: 0.84rem;
        }

        .invite-brand small {
            letter-spacing: 0.12em;
        }

        .invite-hero__panel,
        .location-panel,
        .location-side {
            padding: 24px;
            border-radius: 22px;
        }

        .invite-section {
            padding: 82px 0;
        }

        .timeline-item {
            flex-direction: column;
            gap: 8px;
        }
    }
</style>
@endpush

@section('content')
    <section class="invite-hero">
        <video class="invite-hero__video" autoplay muted playsinline preload="auto" poster="{{ asset('assets/images/page-title.jpg') }}">
            <source src="{{ asset('video-back.mp4') }}" type="video/mp4">
        </video>
        <div class="invite-hero__overlay"></div>

        <div class="invite-hero__content">
            <div class="invite-hero__panel">
                <div class="invite-kicker">Universitas Mulawarman</div>
                <h1>Undangan Yudisium</h1>
                <h2>Fakultas Teknik</h2>
                <p>Program Sarjana Angkatan 82 Periode 2 Tahun 2026. Sebuah undangan resmi yang dirancang dengan tampilan modern, tegas, dan elegan untuk menyambut momen penting para lulusan Fakultas Teknik.</p>
                <div class="invite-meta">
                    <span class="invite-pill">Logo Universitas Mulawarman</span>
                    <span class="invite-pill invite-pill--accent">#F5530D</span>
                </div>
                <div class="invite-actions">
                    <a class="invite-btn invite-btn--primary" href="#detail">Lihat Detail</a>
                    <a class="invite-btn invite-btn--secondary" href="#lokasi">Lokasi Acara</a>
                </div>
            </div>

            <div class="invite-hero__aside">
                <div class="invite-card">
                    <strong>Acara</strong>
                    <h3>Yudisium Program Sarjana</h3>
                    <p>Rangkaian prosesi resmi untuk Angkatan 82 Periode 2 Tahun 2026.</p>
                </div>
                <div class="invite-card">
                    <strong>Identitas</strong>
                    <h3>Fakultas Teknik</h3>
                    <p>Disampaikan dengan nuansa formal, profesional, dan mudah disesuaikan.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="invite-section" id="detail">
        <div class="container">
            <div class="invite-section__head">
                <span class="invite-section__eyebrow">Detail Undangan</span>
                <h2>Informasi utama untuk tamu undangan</h2>
                <p>Bagian ini dirancang sebagai ringkasan undangan yudisium. Nanti tinggal kita sesuaikan lagi untuk tanggal pasti, nama acara, atau detail teknis lain tanpa mengubah struktur besar halaman.</p>
            </div>

            <div class="detail-grid">
                <div class="detail-card">
                    <div class="detail-card__icon">
                        <i class="ti-calendar"></i>
                    </div>
                    <h3>Tanggal Acara</h3>
                    <p>Periode 2 Tahun 2026. Silakan isi tanggal pelaksanaan final ketika jadwal resmi sudah ditetapkan.</p>
                </div>

                <div class="detail-card">
                    <div class="detail-card__icon">
                        <i class="ti-bag"></i>
                    </div>
                    <h3>Agenda</h3>
                    <p>Prosesi yudisium, penyerahan kelulusan, sesi dokumentasi, dan penutupan acara secara resmi.</p>
                </div>

                <div class="detail-card">
                    <div class="detail-card__icon">
                        <i class="ti-user"></i>
                    </div>
                    <h3>Penyelenggara</h3>
                    <p>Fakultas Teknik Universitas Mulawarman bersama program sarjana angkatan 82.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="invite-section invite-section--alt" id="jadwal">
        <div class="container">
            <div class="invite-section__head">
                <span class="invite-section__eyebrow">Rangkaian Acara</span>
                <h2>Susunan acara yang ringkas dan jelas</h2>
                <p>Format ini sengaja dibuat clean supaya mudah dibaca di layar ponsel maupun desktop, sambil tetap mempertahankan kesan resmi untuk undangan Fakultas Teknik.</p>
            </div>

            <div class="timeline">
                <div class="timeline-item">
                    <span>08.00</span>
                    <div>
                        <strong>Registrasi dan Persiapan</strong>
                        <p>Peserta dan tamu undangan mulai memasuki area acara dengan suasana formal.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <span>09.00</span>
                    <div>
                        <strong>Prosesi Yudisium</strong>
                        <p>Pembukaan acara, prosesi utama, serta penyampaian pengukuhan kelulusan.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <span>10.30</span>
                    <div>
                        <strong>Dokumentasi dan Penutupan</strong>
                        <p>Sesi foto bersama dan penutupan acara sebagai penanda momen kelulusan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="invite-section" id="lokasi">
        <div class="container">
            <div class="invite-section__head">
                <span class="invite-section__eyebrow">Lokasi Acara</span>
                <h2>Lokasi dan arah undangan</h2>
                <p>Bagian lokasi dibuat sebagai panel informatif, sehingga nanti kamu tinggal ganti alamat final atau tautan peta tanpa perlu ubah layout utama.</p>
            </div>

            <div class="location-layout">
                <div class="location-panel">
                    <h3>Fakultas Teknik Universitas Mulawarman</h3>
                    <p>Undangan yudisium ini disiapkan dengan tampilan visual yang lebih modern, profesional, dan hangat. Aksen warna utama menggunakan <strong>#F5530D</strong> supaya identitasnya lebih kuat dan tidak terasa seperti template wedding lagi.</p>

                    <ul class="location-list">
                        <li>
                            <i class="ti-location-pin"></i>
                            <span>Lokasi acara akan disesuaikan dengan ruang resmi yang ditetapkan panitia.</span>
                        </li>
                        <li>
                            <i class="ti-time"></i>
                            <span>Waktu pelaksanaan mengikuti jadwal yudisium periode 2 tahun 2026.</span>
                        </li>
                        <li>
                            <i class="ti-book"></i>
                            <span>Format ini siap disesuaikan lagi untuk menampilkan nama ruangan, tanggal, dan rundown final.</span>
                        </li>
                    </ul>

                    <a class="location-cta" href="https://www.google.com/maps/search/?api=1&query=Universitas+Mulawarman+Fakultas+Teknik" target="_blank" rel="noopener">
                        Buka Google Maps
                        <i class="ti-arrow-right"></i>
                    </a>
                </div>

                <div class="location-side">
                    <div class="location-side__label">Universitas Mulawarman</div>
                    <h4>Undangan resmi yang bersih, tegas, dan mudah dikembangkan.</h4>
                    <p>Kita bisa lanjut ganti satu per satu setelah ini, mulai dari foto, nama, jadwal, sampai detail lokasi final, tanpa mengubah struktur dasar halaman.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
