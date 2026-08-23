@extends('layouts.app')

@push('styles')
<style>
    :root {
        --invite-accent: #F5530D;
        --invite-accent-dark: #d9470a;
        --invite-ink: #101828;
        --invite-muted: #667085;
        --invite-soft: #fffaf7;
        --invite-line: rgba(16, 24, 40, 0.08);
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        font-family: "Manrope", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: var(--invite-ink);
        background: #fff;
    }

    .page-wrapper {
        overflow: clip;
        background:
            radial-gradient(circle at top left, rgba(245, 83, 13, 0.06), transparent 28%),
            radial-gradient(circle at 85% 12%, rgba(245, 83, 13, 0.08), transparent 24%),
            #fff;
    }

    .site-header.header-style-1 {
        position: absolute;
        inset: 0 0 auto 0;
        z-index: 30;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(255, 255, 255, 0));
    }

    .site-header .navbar-default {
        margin: 0;
        border: 0;
        background: transparent;
        box-shadow: none;
    }

    .invite-brand {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 18px 0;
        color: var(--invite-ink) !important;
        text-transform: none;
    }

    .invite-brand img {
        width: 44px;
        height: 44px;
        object-fit: contain;
        flex: 0 0 auto;
    }

    .invite-brand span {
        display: flex;
        flex-direction: column;
        line-height: 1.05;
    }

    .invite-brand strong {
        font-size: 0.92rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .invite-brand small {
        font-size: 0.72rem;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: var(--invite-muted);
    }

    .invite-nav > li > a {
        color: var(--invite-ink) !important;
        font-weight: 700;
        letter-spacing: 0.03em;
    }

    .invite-nav > li > a:hover {
        color: var(--invite-accent) !important;
    }

    .intro-hero {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        padding: 110px 0 80px;
        isolation: isolate;
    }

    .intro-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(145deg, rgba(245, 83, 13, 0.05), transparent 26%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.24), rgba(255, 255, 255, 0.58));
        z-index: -2;
    }

    .intro-hero::after {
        content: "";
        position: absolute;
        inset: auto -10% -12% auto;
        width: 420px;
        height: 420px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(245, 83, 13, 0.12), transparent 68%);
        filter: blur(2px);
        z-index: -1;
    }

    .intro-hero__inner {
        display: grid;
        grid-template-columns: 1.05fr 0.95fr;
        gap: 36px;
        align-items: center;
    }

    .intro-hero__copy {
        max-width: 640px;
    }

    .hero-kicker {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        color: var(--invite-accent);
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.24em;
        text-transform: uppercase;
    }

    .hero-kicker::before {
        content: "";
        width: 34px;
        height: 2px;
        border-radius: 999px;
        background: var(--invite-accent);
    }

    .intro-hero__copy h1 {
        margin: 0;
        max-width: 9.5ch;
        font-size: clamp(3rem, 7.2vw, 6rem);
        line-height: 0.94;
        letter-spacing: -0.06em;
        font-weight: 800;
        color: var(--invite-ink);
    }

    .intro-hero__copy p {
        margin: 18px 0 0;
        max-width: 56ch;
        font-size: 1.06rem;
        line-height: 1.85;
        color: var(--invite-muted);
    }

    .hero-meta {
        margin-top: 28px;
        padding-top: 24px;
        border-top: 1px solid var(--invite-line);
    }

    .hero-meta__label {
        color: var(--invite-muted);
        font-size: 0.9rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .hero-meta__value {
        margin-top: 8px;
        color: var(--invite-ink);
        font-size: clamp(1.15rem, 2vw, 1.7rem);
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .hero-scroll {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-top: 28px;
        color: var(--invite-ink);
        font-weight: 800;
        letter-spacing: 0.03em;
    }

    .hero-scroll span {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: rgba(245, 83, 13, 0.12);
        position: relative;
        box-shadow: inset 0 0 0 1px rgba(245, 83, 13, 0.16);
    }

    .hero-scroll span::after {
        content: "";
        position: absolute;
        inset: 13px 16px auto 16px;
        width: 10px;
        height: 10px;
        border-right: 2px solid var(--invite-accent);
        border-bottom: 2px solid var(--invite-accent);
        transform: rotate(45deg);
        animation: arrow-bob 1.6s ease-in-out infinite;
    }

    .intro-hero__visual {
        position: relative;
        display: flex;
        justify-content: center;
    }

    .hero-frame {
        position: relative;
        width: min(100%, 460px);
        aspect-ratio: 0.78 / 1;
        border-radius: 34px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 250, 247, 0.94));
        border: 1px solid rgba(16, 24, 40, 0.08);
        box-shadow: 0 25px 90px rgba(16, 24, 40, 0.12);
        overflow: hidden;
    }

    .hero-frame video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: saturate(0.9) contrast(1.05);
    }

    .hero-frame__veil {
        position: absolute;
        inset: 0;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0.42) 30%, rgba(255, 255, 255, 0.72));
    }

    .hero-frame__ornament {
        position: absolute;
        inset: auto auto 24px 24px;
        width: 86px;
        height: 86px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(245, 83, 13, 0.12), transparent 62%);
        animation: floaty 6s ease-in-out infinite;
    }

    .gear-stage {
        position: absolute;
        inset: 50% auto auto 50%;
        width: 282px;
        height: 282px;
        transform: translate(-50%, -50%);
        display: grid;
        place-items: center;
        pointer-events: none;
    }

    .gear-stage__gear {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        color: rgba(245, 83, 13, 0.17);
        animation: spin 22s linear infinite;
        transform-origin: center;
    }

    .gear-stage__gear--small {
        width: 56%;
        height: 56%;
        color: rgba(255, 255, 255, 0.7);
        animation-direction: reverse;
        animation-duration: 16s;
    }

    .gear-stage__gear circle.outer {
        fill: rgba(255, 255, 255, 0.08);
        stroke: currentColor;
        stroke-width: 10;
    }

    .gear-stage__gear circle.core {
        fill: rgba(255, 255, 255, 0.86);
        stroke: rgba(245, 83, 13, 0.2);
        stroke-width: 4;
    }

    .gear-stage__gear rect {
        fill: currentColor;
    }

    .seal {
        position: relative;
        z-index: 1;
        width: 148px;
        aspect-ratio: 1;
        border-radius: 50%;
        display: grid;
        place-items: center;
        text-align: center;
        background: radial-gradient(circle at 35% 35%, #fff 0%, #fbf5ef 62%, #efe4d8 100%);
        box-shadow:
            inset 0 0 0 1px rgba(255, 255, 255, 0.85),
            0 12px 36px rgba(16, 24, 40, 0.12);
        transform: scale(1);
        animation: floaty 5s ease-in-out infinite;
    }

    .seal img {
        width: 76px;
        height: 76px;
        object-fit: contain;
        margin: 0 auto;
    }

    .seal__line {
        font-size: 0.72rem;
        line-height: 1.2;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: var(--invite-ink);
        font-weight: 800;
    }

    .seal__line--small {
        color: var(--invite-muted);
        font-weight: 700;
        letter-spacing: 0.16em;
    }

    .hero-frame__tag {
        position: absolute;
        left: 22px;
        bottom: 20px;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(245, 83, 13, 0.16);
        color: var(--invite-ink);
        font-size: 0.82rem;
        font-weight: 800;
        backdrop-filter: blur(12px);
    }

    .hero-frame__tag::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--invite-accent);
        box-shadow: 0 0 0 6px rgba(245, 83, 13, 0.12);
    }

    .invite-section {
        padding: 104px 0;
        background: #fff;
    }

    .invite-section--soft {
        background: var(--invite-soft);
    }

    .section-title {
        max-width: 860px;
        margin: 0 auto 42px;
        text-align: center;
    }

    .section-title__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: var(--invite-accent);
        font-size: 0.8rem;
        font-weight: 800;
        letter-spacing: 0.28em;
        text-transform: uppercase;
    }

    .section-title__eyebrow::before,
    .section-title__eyebrow::after {
        content: "";
        width: 18px;
        height: 2px;
        border-radius: 999px;
        background: rgba(245, 83, 13, 0.22);
    }

    .section-title h2 {
        margin: 14px 0 0;
        font-size: clamp(1.9rem, 4vw, 3rem);
        line-height: 1.08;
        letter-spacing: -0.04em;
    }

    .section-title p {
        margin: 14px auto 0;
        max-width: 720px;
        color: var(--invite-muted);
        line-height: 1.85;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0;
        border-top: 1px solid var(--invite-line);
        border-bottom: 1px solid var(--invite-line);
    }

    .detail-item {
        padding: 28px 24px;
        border-right: 1px solid var(--invite-line);
    }

    .detail-item:last-child {
        border-right: 0;
    }

    .detail-item__index {
        color: var(--invite-accent);
        font-size: 0.8rem;
        font-weight: 800;
        letter-spacing: 0.24em;
    }

    .detail-item h3 {
        margin: 12px 0 0;
        font-size: 1.2rem;
        letter-spacing: -0.02em;
    }

    .detail-item p {
        margin: 10px 0 0;
        color: var(--invite-muted);
        line-height: 1.85;
    }

    .timeline {
        display: grid;
        gap: 12px;
        max-width: 920px;
        margin: 0 auto;
    }

    .timeline-row {
        display: grid;
        grid-template-columns: 110px 1fr;
        gap: 20px;
        align-items: start;
        padding: 18px 0;
        border-bottom: 1px solid var(--invite-line);
    }

    .timeline-row:last-child {
        border-bottom: 0;
    }

    .timeline-row__time {
        color: var(--invite-accent);
        font-size: 1rem;
        font-weight: 800;
        letter-spacing: 0.08em;
    }

    .timeline-row h3 {
        margin: 0;
        font-size: 1.15rem;
        letter-spacing: -0.02em;
    }

    .timeline-row p {
        margin: 8px 0 0;
        color: var(--invite-muted);
        line-height: 1.8;
    }

    .venue-layout {
        display: grid;
        grid-template-columns: 1fr 0.92fr;
        gap: 28px;
        align-items: stretch;
        max-width: 1180px;
        margin: 0 auto;
    }

    .venue-panel {
        padding: 34px;
        border: 1px solid var(--invite-line);
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.82);
        backdrop-filter: blur(14px);
    }

    .venue-panel h3 {
        margin: 0;
        font-size: clamp(1.5rem, 3vw, 2.2rem);
        letter-spacing: -0.03em;
    }

    .venue-panel p {
        margin: 16px 0 0;
        color: var(--invite-muted);
        line-height: 1.9;
    }

    .venue-list {
        margin: 24px 0 0;
        display: grid;
        gap: 12px;
    }

    .venue-list li {
        display: flex;
        align-items: start;
        gap: 12px;
        color: var(--invite-ink);
        line-height: 1.75;
    }

    .venue-list i {
        color: var(--invite-accent);
        margin-top: 4px;
    }

    .venue-cta {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-top: 26px;
        padding: 14px 18px;
        border-radius: 14px;
        background: var(--invite-accent);
        color: #fff;
        font-weight: 800;
        box-shadow: 0 18px 34px rgba(245, 83, 13, 0.22);
    }

    .venue-map {
        position: relative;
        min-height: 380px;
        border-radius: 28px;
        overflow: hidden;
        border: 1px solid var(--invite-line);
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.72)),
            url({{ asset('assets/images/page-title.jpg') }}) center/cover no-repeat;
    }

    .venue-map__veil {
        position: absolute;
        inset: 0;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.22), rgba(255, 255, 255, 0.78)),
            radial-gradient(circle at 30% 30%, rgba(245, 83, 13, 0.12), transparent 36%);
    }

    .venue-map__label {
        position: absolute;
        left: 24px;
        right: 24px;
        bottom: 24px;
        padding: 18px 20px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid rgba(16, 24, 40, 0.08);
        backdrop-filter: blur(12px);
    }

    .venue-map__label span {
        display: inline-block;
        color: var(--invite-accent);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 16px;
    }

    .gallery-item {
        position: relative;
        overflow: hidden;
        border-radius: 22px;
        min-height: 220px;
        background: #f3f4f6;
        border: 1px solid var(--invite-line);
        transform: translateY(0);
        transition: transform 0.35s ease, box-shadow 0.35s ease;
    }

    .gallery-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 34px rgba(16, 24, 40, 0.08);
    }

    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .gallery-item--wide {
        grid-column: span 6;
    }

    .gallery-item--tall {
        grid-column: span 4;
        min-height: 280px;
    }

    .closing-note {
        max-width: 820px;
        margin: 40px auto 0;
        text-align: center;
        padding: 34px 24px 0;
        border-top: 1px solid var(--invite-line);
    }

    .closing-note p {
        margin: 0;
        color: var(--invite-muted);
        line-height: 1.9;
    }

    .footer-section {
        position: relative;
        padding: 48px 0 68px;
        background: #fff;
        border-top: 1px solid var(--invite-line);
    }

    .footer-section .footer-area {
        text-align: center;
    }

    .footer-section .footer-area h2,
    .footer-section .footer-area h3,
    .footer-section .footer-area p {
        margin: 0;
    }

    .footer-section .footer-area h2 {
        font-size: 1.35rem;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .footer-section .footer-area h3 {
        margin-top: 12px;
        font-size: 1rem;
        font-weight: 700;
        color: var(--invite-muted);
    }

    .footer-section .footer-area p {
        margin-top: 8px;
        color: var(--invite-muted);
    }

    .reveal {
        opacity: 0;
        transform: translateY(18px);
        transition: opacity 0.8s ease, transform 0.8s ease;
    }

    .reveal.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .reveal-delay-1 {
        transition-delay: 0.08s;
    }

    .reveal-delay-2 {
        transition-delay: 0.16s;
    }

    .reveal-delay-3 {
        transition-delay: 0.24s;
    }

    .floaty {
        animation: floaty 6s ease-in-out infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    @keyframes floaty {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }

    @keyframes arrow-bob {
        0%, 100% { transform: translateY(0) rotate(45deg); }
        50% { transform: translateY(3px) rotate(45deg); }
    }

    @media (max-width: 991px) {
        .intro-hero__inner,
        .venue-layout {
            grid-template-columns: 1fr;
        }

        .intro-hero {
            padding-top: 120px;
        }

        .gallery-item--wide,
        .gallery-item--tall {
            grid-column: span 6;
        }
    }

    @media (max-width: 767px) {
        .intro-hero {
            padding: 108px 0 64px;
        }

        .intro-hero__copy h1 {
            max-width: 12ch;
        }

        .hero-frame {
            width: min(100%, 360px);
        }

        .gear-stage {
            width: 220px;
            height: 220px;
        }

        .detail-grid {
            grid-template-columns: 1fr;
        }

        .detail-item {
            border-right: 0;
            border-bottom: 1px solid var(--invite-line);
        }

        .detail-item:last-child {
            border-bottom: 0;
        }

        .timeline-row {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .gallery-item--wide,
        .gallery-item--tall {
            grid-column: span 12;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const targets = document.querySelectorAll('.reveal');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.18 });

  targets.forEach((target, index) => {
    target.style.transitionDelay = `${Math.min(index * 90, 360)}ms`;
    observer.observe(target);
  });
});
</script>
@endpush

@section('content')
  @include('partials.hero')
  @include('partials.primary-sections')
  @include('partials.secondary-sections')
@endsection
