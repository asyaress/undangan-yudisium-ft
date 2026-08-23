@php
    $isEdit = $mode === 'edit';
    $pageTitle = $isEdit ? 'Edit Kategori' : 'Tambah Kategori';
    $titleValue = old('title', $category->title);
    $recipientValue = old('recipient_label', $category->recipient_label ?: 'Tamu Undangan');
    $coverValue = old('cover_text', $category->cover_text);
    $invitationValue = old('invitation_text', $category->invitation_text);
    $closingValue = old('closing_text', $category->closing_text);
    $slugPreview = \Illuminate\Support\Str::slug($titleValue ?: 'judul-kategori');
    $periodPayload = $periods->map(fn ($period) => [
        'id' => $period->id,
        'name' => $period->name,
        'slug' => $period->slug,
        'event_year' => $period->event_year,
        'cohort_label' => $period->cohort_label,
        'period_label' => $period->period_label,
        'event_date' => $period->event_date?->format('Y-m-d'),
        'event_time' => $period->event_time,
        'location' => $period->location,
        'address' => $period->address,
        'signature_city' => $period->signature_city,
        'signer_name' => $period->signer_name,
        'signer_title' => $period->signer_title,
    ])->values();
@endphp

@extends('layouts.dashboard')

@section('title', $pageTitle)
@section('breadcrumb_parent', 'Kategori Undangan')
@section('breadcrumb_active', $pageTitle)

@section('page_actions')
    <a class="btn btn-outline-secondary" href="{{ route('admin.categories.index', ['period_id' => $selectedPeriod?->id]) }}">
        <i class="fa fa-arrow-left"></i> Kembali
    </a>
@endsection

@push('head')
    <style>
        .category-editor-grid {
            align-items: flex-start;
        }

        .category-editor-card .section-title {
            margin: 18px 0 12px;
            padding-top: 14px;
            border-top: 1px solid #eef0f3;
            color: #111827;
            font-size: 14px;
            font-weight: 800;
        }

        .category-editor-card .section-title:first-child {
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
        }

        .category-editor-card label {
            color: #4b5563;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .category-editor-card .form-control {
            border-color: #e5e7eb;
            border-radius: 6px;
            color: #111827;
        }

        .category-editor-card .form-control:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.12);
        }

        .auto-url,
        .sort-info,
        .autosave-note {
            display: block;
            margin-top: 8px;
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 12px;
            line-height: 1.6;
        }

        .auto-url {
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #92400e;
            overflow-wrap: anywhere;
        }

        .sort-info,
        .autosave-note {
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            color: #6b7280;
            font-weight: 700;
        }

        .autosave-note {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .autosave-note i {
            color: #d97706;
        }

        .preview-shell {
            position: sticky;
            top: 84px;
        }

        .preview-card {
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
        }

        .preview-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-bottom: 1px solid #eef0f3;
        }

        .preview-toolbar h2 {
            margin: 0;
            color: #111827;
            font-size: 15px;
            font-weight: 800;
        }

        .preview-toolbar span {
            color: #6b7280;
            font-size: 12px;
        }

        .preview-frame-wrap {
            background: #f3f4f6;
            padding: 14px;
        }

        .preview-frame {
            width: 100%;
            height: 720px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f3f4f6;
        }

        .category-toast-stack {
            position: fixed;
            top: 82px;
            right: 22px;
            z-index: 9999;
            display: grid;
            gap: 10px;
            width: min(360px, calc(100vw - 32px));
            pointer-events: none;
        }

        .category-toast {
            display: grid;
            grid-template-columns: 34px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            padding: 12px 14px;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #d97706;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 14px 38px rgba(15, 23, 42, 0.16);
            color: #111827;
            opacity: 0;
            transform: translateY(-8px);
            animation: categoryToastIn 180ms ease-out forwards;
        }

        .category-toast.is-success {
            border-left-color: #0f766e;
        }

        .category-toast.is-error {
            border-left-color: #b91c1c;
        }

        .category-toast-icon {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: #fff7ed;
            color: #d97706;
        }

        .category-toast.is-success .category-toast-icon {
            background: rgba(15, 118, 110, 0.1);
            color: #0f766e;
        }

        .category-toast.is-error .category-toast-icon {
            background: rgba(185, 28, 28, 0.1);
            color: #b91c1c;
        }

        .category-toast-title {
            margin: 0;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.3;
        }

        .category-toast-message {
            margin: 2px 0 0;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.45;
        }

        @keyframes categoryToastIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 991px) {
            .preview-shell {
                position: static;
            }

            .preview-frame {
                height: 680px;
            }
        }
    </style>
@endpush

@section('content')
@include('layouts.partials.block-header')

<div class="category-toast-stack" id="categoryToastStack" aria-live="polite" aria-atomic="true"></div>

@if ($periods->isEmpty())
    <div class="alert alert-warning">Buat event yudisium terlebih dahulu sebelum menambahkan kategori undangan.</div>
@else
    <div class="row clearfix category-editor-grid">
        <div class="col-xl-5 col-lg-6 col-md-12">
            <div class="card category-editor-card">
                <div class="header"><h2>{{ $pageTitle }}</h2></div>
                <div class="card-body py-3">
                    <form method="post" action="{{ $formAction }}" id="categoryEditorForm" data-store-url="{{ route('admin.categories.store') }}" data-update-url="{{ $isEdit ? route('admin.categories.update', $category) : '' }}" data-category-id="{{ $category->id }}">
                        @csrf
                        @if ($method === 'PUT')
                            <input type="hidden" name="_method" value="PUT" data-method-field>
                        @endif

                        <h3 class="section-title">Konteks</h3>
                        @if ($isEdit)
                            <input type="hidden" name="period_id" value="{{ $category->period_id }}" data-period-field>
                            <div class="form-group">
                                <label>Event</label>
                                <input class="form-control" value="{{ $selectedPeriod?->name }}" readonly>
                            </div>
                        @else
                            <div class="form-group">
                                <label>Event</label>
                                <select class="form-control" name="period_id" data-period-select required>
                                    @foreach ($periods as $period)
                                        <option value="{{ $period->id }}" @selected($selectedPeriod?->id === $period->id)>{{ $period->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <h3 class="section-title">Identitas Kategori</h3>
                        <div class="form-group">
                            <label>Judul Kategori</label>
                            <input class="form-control" name="title" value="{{ $titleValue }}" placeholder="Contoh: Yudisiawan / Yudisiawati" required>
                            <span class="auto-url">Slug & link otomatis: <strong data-auto-url>{{ $selectedPeriod ? route('home', ['event' => $selectedPeriod->slug, 'to' => $category->slug ?: $slugPreview]) : '-' }}</strong></span>
                        </div>
                        <div class="form-group">
                            <label>Label Penerima</label>
                            <input class="form-control" name="recipient_label" value="{{ $recipientValue }}" placeholder="Tamu Undangan" required>
                        </div>
                        <div class="row">
                            <div class="col-md-7 form-group">
                                <label>Mode Akses</label>
                                <select class="form-control" name="access_mode" required>
                                    <option value="nim" @selected(old('access_mode', $category->access_mode) === 'nim')>Verifikasi NIM mahasiswa</option>
                                    <option value="private" @selected(old('access_mode', $category->access_mode ?: 'private') === 'private')>Private dengan token</option>
                                    <option value="public" @selected(old('access_mode', $category->access_mode) === 'public')>Umum tanpa token</option>
                                </select>
                            </div>
                            <div class="col-md-5 form-group d-flex align-items-end">
                                <label class="fancy-checkbox mb-2">
                                    <input type="hidden" name="rsvp_enabled" value="0">
                                    <input name="rsvp_enabled" type="checkbox" value="1" @checked((string) old('rsvp_enabled', $category->exists ? (int) $category->rsvp_enabled : 1) === '1')>
                                    <span>Konfirmasi Kehadiran</span>
                                </label>
                            </div>
                        </div>
                        <div class="sort-info">
                            Urutan tampil: {{ $category->sort_order ?: 'otomatis setelah tersimpan' }}. Urutan dipakai untuk posisi kategori di daftar/dropdown undangan.
                        </div>

                        <h3 class="section-title">Teks Undangan</h3>
                        <div class="form-group">
                            <label>Teks Cover</label>
                            <textarea class="form-control" name="cover_text" rows="3" required>{{ $coverValue }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Teks Undangan</label>
                            <textarea class="form-control" name="invitation_text" rows="5" required>{{ $invitationValue }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Teks Penutup</label>
                            <textarea class="form-control" name="closing_text" rows="3">{{ $closingValue }}</textarea>
                        </div>

                        <div class="autosave-note" data-autosave-inline>
                            <i class="fa fa-clock-o"></i>
                            <span>Perubahan akan tersimpan otomatis setelah judul terisi.</span>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-7 col-lg-6 col-md-12">
            <div class="preview-shell">
                <div class="preview-card">
                    <div class="preview-toolbar">
                        <div>
                            <h2>Live Preview Undangan</h2>
                            <span>Teks dan akses mengikuti kategori yang sedang diedit</span>
                        </div>
                        <span data-preview-status>Otomatis</span>
                    </div>
                    <div class="preview-frame-wrap">
                        <iframe class="preview-frame" id="categoryPreviewFrame" title="Preview kategori undangan"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<template id="categoryPreviewTemplate">
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('pages.partials.invitation-styles')
    <style>
        body.opened {
            padding: 12px;
        }

        body.opened .invitation-details-section {
            margin-top: 0 !important;
        }

        body.opened #invitationContent {
            padding: 0;
        }

        .admin-preview-rsvp {
            padding: 18px;
        }

        .admin-preview-rsvp[hidden] {
            display: none !important;
        }

        .admin-preview-rsvp .rsvp-steps {
            margin-bottom: 0;
        }

        .bg-video {
            opacity: 0.46;
        }
    </style>
</head>
<body>
    <div class="bg-video-layer" aria-hidden="true">
        <video class="bg-video is-ready" autoplay muted loop playsinline>
            <source src="{{ asset('video-back.mp4') }}" type="video/mp4">
        </video>
    </div>
    <div class="bg-video-overlay" aria-hidden="true"></div>
    <main>
        <section class="panel cover" id="cover">
            <p class="label">Undangan</p>
            <h1>Yudisium Fakultas Teknik</h1>
            <div class="logo-frame">
                <img class="logo" src="{{ asset('Unmul.png') }}" alt="Logo Universitas Mulawarman">
            </div>
            <p class="meta">__COVER_TEXT__</p>
            <p class="guest-label">Kepada Yth.</p>
            <p class="guest">__RECIPIENT_LABEL__</p>
            <button class="btn" id="openInvitation" type="button">Buka Undangan</button>
        </section>

        <section class="invitation invitation-layout" id="invitationContent">
            <article class="panel rsvp-card card--full admin-preview-rsvp watermark-card" __RSVP_HIDDEN__>
                <div class="rsvp-card-head">
                    <div class="rsvp-badge">&#10003;</div>
                    <div>
                        <h3>Konfirmasi Kehadiran</h3>
                        <p>__RSVP_TEXT__</p>
                    </div>
                </div>
                <div class="rsvp-steps">
                    <div class="rsvp-step is-active"><span class="rsvp-step-num">1</span><span>Verifikasi akses undangan</span></div>
                    <div class="rsvp-step"><span class="rsvp-step-num">2</span><span>Periksa data undangan</span></div>
                    <div class="rsvp-step"><span class="rsvp-step-num">3</span><span>Isi status kehadiran</span></div>
                </div>
            </article>

            <div class="invitation-details-section card--full">
                <div class="details-divider" id="invitationDetails">Detail Undangan</div>
                <div class="invitation-details" id="invitationContentDetails">
                    <article class="panel card card--white card--full">
                        <h2 class="title">Dengan Hormat</h2>
                        <p class="line">__INVITATION_TEXT__</p>
                        <div class="mini-brand">
                            <img src="{{ asset('Unmul.png') }}" alt="Logo Universitas Mulawarman">
                        </div>
                        <div class="details">
                            <div class="detail-item">
                                <span class="detail-label">Hari/Tanggal</span>
                                <span class="detail-value">__EVENT_DATE_LABEL__</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Waktu</span>
                                <span class="detail-value">__EVENT_TIME__</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tempat</span>
                                <span class="detail-value">__LOCATION__</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Alamat</span>
                                <span class="detail-value">__ADDRESS__</span>
                            </div>
                        </div>
                    </article>

                    <article class="panel card card--white watermark-card">
                        <div class="event-strip">
                            <h3>Akses Kategori</h3>
                            <ol>
                                <li>__ACCESS_TEXT__</li>
                                <li>__RSVP_STATUS_TEXT__</li>
                            </ol>
                        </div>
                    </article>

                    <article class="panel card card--white watermark-card">
                        <h3 class="title">Lokasi Acara</h3>
                        <div class="map-wrap">
                            <iframe title="Peta lokasi acara Yudisium Fakultas Teknik" src="https://maps.google.com/maps?q=__MAP_QUERY__&t=&z=16&ie=UTF8&iwloc=&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                        <p class="location-text">
                            Tempat: __LOCATION__<br>
                            __ADDRESS__
                        </p>
                        <a class="map-btn" href="https://www.google.com/maps/search/?api=1&query=__MAP_QUERY__" target="_blank" rel="noopener">Petunjuk ke lokasi</a>
                    </article>

                    <article class="panel card card--white card--full">
                        <div class="auth-stack">
                            <div class="auth-head">
                                <p class="auth-date">__SIGNATURE_DATE_LABEL__</p>
                                <p class="auth-salutation">Dekan Fakultas Teknik,</p>
                            </div>
                            <div class="signature-layer">
                                <div class="signature-box">
                                    <img class="asset-image" src="{{ asset('ttd.png') }}" alt="Tanda tangan dekan">
                                </div>
                                <div class="stamp-overlay">
                                    <img class="asset-image" src="{{ asset('stempel.png') }}" alt="Stempel Fakultas Teknik">
                                </div>
                            </div>
                            <p class="auth-name">__SIGNER_NAME__</p>
                            <p class="auth-role">__SIGNER_TITLE__</p>
                        </div>
                    </article>

                    <article class="panel card card--white card--full closing-card">
                        <p class="closing">__CLOSING_TEXT__</p>
                    </article>
                </div>
            </div>
        </section>
    </main>
    <script>
        (function () {
            var button = document.getElementById('openInvitation');
            if (!button) return;

            button.addEventListener('click', function () {
                document.body.classList.add('opening');

                window.setTimeout(function () {
                    document.body.classList.remove('opening');
                    document.body.classList.add('opened');

                    var content = document.getElementById('invitationContent');
                    if (content) {
                        content.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 360);
            });
        })();
    </script>
</body>
</html>
</template>
@endsection

@push('scripts')
    <script>
        (function () {
            var form = document.getElementById('categoryEditorForm');
            if (!form) return;

            var frame = document.getElementById('categoryPreviewFrame');
            var template = document.getElementById('categoryPreviewTemplate').innerHTML;
            var previewStatus = document.querySelector('[data-preview-status]');
            var autoUrl = document.querySelector('[data-auto-url]');
            var toastStack = document.getElementById('categoryToastStack');
            var autosaveInline = document.querySelector('[data-autosave-inline] span');
            var baseHomeUrl = @json(url('/'));
            var periods = @json($periodPayload);
            var isPersisted = Boolean(form.dataset.categoryId);

            function field(name) {
                var inputs = Array.from(form.querySelectorAll('[name="' + name + '"]'));
                var input = inputs.find(function (candidate) {
                    return candidate.type === 'checkbox';
                }) || inputs[0];

                if (!input) return '';
                if (input.type === 'checkbox') return input.checked ? input.value : '';
                return input.value.trim();
            }

            function currentPeriod() {
                var periodId = String(field('period_id'));
                return periods.find(function (period) {
                    return String(period.id) === periodId;
                }) || periods[0] || {};
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function slugify(value) {
                return String(value || '')
                    .normalize('NFKD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/&/g, ' dan ')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '')
                    .replace(/-{2,}/g, '-') || 'judul-kategori';
            }

            function formatDate(value, variant) {
                if (!value) {
                    return variant === 'short' ? '18 Juni 2026' : 'Kamis, 18 Juni 2026';
                }

                var date = new Date(value + 'T00:00:00');
                if (Number.isNaN(date.getTime())) {
                    return variant === 'short' ? '18 Juni 2026' : 'Kamis, 18 Juni 2026';
                }

                var options = variant === 'short'
                    ? { day: 'numeric', month: 'long', year: 'numeric' }
                    : { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };

                return new Intl.DateTimeFormat('id-ID', options).format(date);
            }

            function signatureDateLabel(cityOrDate, eventDate) {
                var value = String(cityOrDate || 'Samarinda').trim();

                if (value.indexOf(',') !== -1) {
                    return value;
                }

                return value + ', ' + formatDate(eventDate, 'short');
            }

            function categoryUrl(slug) {
                var period = currentPeriod();
                return baseHomeUrl + '?event=' + encodeURIComponent(period.slug || '') + '&to=' + encodeURIComponent(slug);
            }

            function accessText(mode) {
                if (mode === 'nim') {
                    return 'Kategori mahasiswa memakai verifikasi NIM sebelum undangan dan konfirmasi terbuka.';
                }

                if (mode === 'public') {
                    return 'Kategori umum bisa dibuka langsung tanpa token atau verifikasi.';
                }

                return 'Kategori private memakai link personal dengan token unik dari dashboard admin.';
            }

            function rsvpText(mode) {
                if (mode === 'nim') {
                    return 'Masukkan NIM terlebih dahulu. Setelah cocok dengan data panitia, undangan dan formulir konfirmasi akan terbuka.';
                }

                if (mode === 'private') {
                    return 'Penerima private dapat mengisi konfirmasi melalui link personal yang dibagikan panitia.';
                }

                return 'Tamu dapat mengisi konfirmasi kehadiran langsung dari halaman undangan.';
            }

            function replaceAll(source, map) {
                Object.keys(map).forEach(function (key) {
                    source = source.split(key).join(map[key]);
                });

                return source;
            }

            function renderPreview() {
                var period = currentPeriod();
                var slug = slugify(field('title'));
                var mode = field('access_mode') || 'private';
                var rsvpEnabled = Boolean(field('rsvp_enabled'));
                var location = period.location || 'Gedung Hexagon Fakultas Teknik UNMUL';
                var address = period.address || 'Jl. Sambaliung No.9, Gunung Kelua, Samarinda';
                var mapQuery = encodeURIComponent([location, address].filter(Boolean).join(', '));
                var displayUrl = categoryUrl(slug) + (mode === 'private' ? '&ref=TOKEN_UNIK' : '');

                autoUrl.textContent = displayUrl;

                frame.srcdoc = replaceAll(template, {
                    '__COVER_TEXT__': escapeHtml(field('cover_text') || 'Dengan hormat, Fakultas Teknik Universitas Mulawarman mengundang kehadiran Bapak/Ibu/Saudara(i) pada prosesi yudisium.'),
                    '__RECIPIENT_LABEL__': escapeHtml(field('recipient_label') || 'Tamu Undangan'),
                    '__INVITATION_TEXT__': escapeHtml(field('invitation_text') || 'Dengan hormat, kami mengundang Bapak/Ibu/Saudara(i) untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.'),
                    '__CLOSING_TEXT__': escapeHtml(field('closing_text') || 'Atas kehadiran Bapak/Ibu/Saudara(i), kami ucapkan terima kasih.'),
                    '__EVENT_DATE_LABEL__': escapeHtml(formatDate(period.event_date, 'long')),
                    '__EVENT_DATE_SHORT__': escapeHtml(formatDate(period.event_date, 'short')),
                    '__EVENT_TIME__': escapeHtml(period.event_time || '09.00 s/d Selesai'),
                    '__LOCATION__': escapeHtml(location),
                    '__ADDRESS__': escapeHtml(address),
                    '__MAP_QUERY__': mapQuery,
                    '__SIGNATURE_DATE_LABEL__': escapeHtml(signatureDateLabel(period.signature_city || 'Samarinda', period.event_date)),
                    '__SIGNER_NAME__': escapeHtml(period.signer_name || 'Nama Dekan / Pejabat'),
                    '__SIGNER_TITLE__': escapeHtml(period.signer_title || 'Dekan Fakultas Teknik Universitas Mulawarman'),
                    '__ACCESS_TEXT__': escapeHtml(accessText(mode)),
                    '__RSVP_STATUS_TEXT__': escapeHtml(rsvpEnabled ? 'Konfirmasi kehadiran aktif untuk kategori ini.' : 'Konfirmasi kehadiran nonaktif untuk kategori ini.'),
                    '__RSVP_TEXT__': escapeHtml(rsvpText(mode)),
                    '__RSVP_HIDDEN__': rsvpEnabled ? '' : 'hidden'
                });

                previewStatus.textContent = 'Terbaru';
            }

            function showToast(type, title, message) {
                var toast = document.createElement('div');
                toast.className = 'category-toast is-' + type;
                toast.innerHTML = [
                    '<div class="category-toast-icon"><i class="fa ' + (type === 'success' ? 'fa-check' : 'fa-exclamation') + '"></i></div>',
                    '<div>',
                    '<p class="category-toast-title">' + escapeHtml(title) + '</p>',
                    '<p class="category-toast-message">' + escapeHtml(message) + '</p>',
                    '</div>'
                ].join('');

                toastStack.prepend(toast);

                while (toastStack.children.length > 3) {
                    toastStack.lastElementChild.remove();
                }

                window.setTimeout(function () {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-8px)';
                    window.setTimeout(function () {
                        toast.remove();
                    }, 180);
                }, 2400);
            }

            function setAutosaveText(text, icon) {
                autosaveInline.textContent = text;

                var noteIcon = document.querySelector('[data-autosave-inline] i');
                if (noteIcon) {
                    noteIcon.className = 'fa ' + icon;
                }
            }

            function ensureMethodField() {
                var method = form.querySelector('[name="_method"]');
                if (!method) {
                    method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.setAttribute('data-method-field', '');
                    form.appendChild(method);
                }

                method.value = 'PUT';
            }

            function applySavedPayload(payload) {
                if (!payload || !payload.id) return;

                isPersisted = true;
                form.dataset.categoryId = payload.id;
                form.dataset.updateUrl = payload.update_url || form.dataset.updateUrl;
                form.action = form.dataset.updateUrl;
                ensureMethodField();

                if (payload.edit_url && window.location.href !== payload.edit_url) {
                    window.history.replaceState({}, '', payload.edit_url);
                }

                if (payload.display_url) {
                    autoUrl.textContent = payload.display_url;
                }
            }

            function validationMessage(errors) {
                if (!errors) return 'Periksa kembali input kategori.';

                var firstKey = Object.keys(errors)[0];
                return firstKey && errors[firstKey] && errors[firstKey][0]
                    ? errors[firstKey][0]
                    : 'Periksa kembali input kategori.';
            }

            function saveCategory() {
                if (!field('title')) {
                    setAutosaveText('Isi judul kategori supaya autosave mulai berjalan.', 'fa-info-circle');
                    return;
                }

                if (saveInFlight) {
                    pendingSave = true;
                    setAutosaveText('Menunggu autosave sebelumnya selesai...', 'fa-clock-o');
                    return;
                }

                saveInFlight = true;
                setAutosaveText('Menyimpan perubahan...', 'fa-circle-o-notch fa-spin');

                if (isPersisted) {
                    form.action = form.dataset.updateUrl || form.action;
                    ensureMethodField();
                }

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(form)
                })
                    .then(function (response) {
                        return response.json().then(function (payload) {
                            if (!response.ok) {
                                var error = new Error(payload.message || validationMessage(payload.errors));
                                error.payload = payload;
                                throw error;
                            }

                            return payload;
                        });
                    })
                    .then(function (payload) {
                        applySavedPayload(payload);
                        setAutosaveText('Semua perubahan sudah tersimpan otomatis.', 'fa-check-circle');
                        showToast('success', 'Tersimpan', payload.message || 'Perubahan kategori tersimpan otomatis.');
                    })
                    .catch(function (error) {
                        var message = validationMessage(error.payload && error.payload.errors) || error.message;
                        setAutosaveText('Autosave tertunda. Perbaiki input yang diperlukan.', 'fa-exclamation-circle');
                        showToast('error', 'Autosave gagal', message);
                    })
                    .finally(function () {
                        saveInFlight = false;

                        if (pendingSave) {
                            pendingSave = false;
                            clearTimeout(saveTimer);
                            saveTimer = setTimeout(saveCategory, 220);
                        }
                    });
            }

            var previewTimer;
            var saveTimer;
            var saveInFlight = false;
            var pendingSave = false;

            function queuePreviewAndSave() {
                previewStatus.textContent = 'Memperbarui...';
                clearTimeout(previewTimer);
                previewTimer = setTimeout(renderPreview, 120);

                setAutosaveText('Menunggu perubahan selesai diketik...', 'fa-clock-o');
                clearTimeout(saveTimer);
                saveTimer = setTimeout(saveCategory, 900);
            }

            form.addEventListener('input', queuePreviewAndSave);
            form.addEventListener('change', queuePreviewAndSave);
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                clearTimeout(saveTimer);
                saveCategory();
            });

            renderPreview();
        })();
    </script>
@endpush
