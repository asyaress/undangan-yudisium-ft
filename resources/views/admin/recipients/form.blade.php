@php
    $isEdit = $mode === 'edit';
    $pageTitle = $isEdit ? 'Edit Penerima' : 'Tambah Penerima';
    $recipientName = old('name', $recipient->name);
    $salutation = old('salutation', $recipient->salutation);
    $identifier = old('identifier', $recipient->identifier);
    $position = old('position', $recipient->position);
    $contextNote = old('context_note', $recipient->context_note);
    $invitationName = trim(collect([$salutation, $recipientName])->filter()->implode(' ')) ?: 'Nama Penerima';
    $tokenPreview = $recipient->token ?: 'TOKEN_OTOMATIS';
    $invitationUrl = $selectedPeriod
        ? route('home', ['event' => $selectedPeriod->slug, 'to' => $category->slug]).($category->usesPrivateAccess() ? '&ref='.$tokenPreview : '')
        : '#';
    $eventPreviewData = [
        'date' => $selectedPeriod?->event_date?->format('Y-m-d'),
        'time' => $selectedPeriod?->event_time,
        'location' => $selectedPeriod?->location,
        'address' => $selectedPeriod?->address,
        'signature_city' => $selectedPeriod?->signature_city,
        'signer_name' => $selectedPeriod?->signer_name,
        'signer_title' => $selectedPeriod?->signer_title,
    ];
    $categoryPreviewData = [
        'cover_text' => $category->cover_text,
        'invitation_text' => $category->invitation_text,
        'closing_text' => $category->closing_text,
        'rsvp_enabled' => $category->requiresRsvp(),
    ];
@endphp

@extends('layouts.dashboard')

@section('title', $pageTitle)
@section('breadcrumb_parent', 'Penerima Undangan')
@section('breadcrumb_active', $category->title.' - '.$pageTitle)

@section('page_actions')
    @if ($isEdit)
        <a class="btn btn-outline-secondary" href="{{ $invitationUrl }}" target="_blank" rel="noopener">
            <i class="fa fa-eye"></i> Preview Publik
        </a>
    @endif
    <a class="btn btn-outline-secondary" href="{{ route('admin.recipients.index', ['categorySlug' => $category->slug, 'period_id' => $selectedPeriod?->id]) }}">
        <i class="fa fa-arrow-left"></i> Kembali
    </a>
@endsection

@push('head')
    <style>
        .recipient-editor-grid {
            align-items: flex-start;
        }

        .recipient-editor-card .section-title {
            margin: 18px 0 12px;
            padding-top: 14px;
            border-top: 1px solid #eef0f3;
            color: #111827;
            font-size: 14px;
            font-weight: 800;
        }

        .recipient-editor-card .section-title:first-child {
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
        }

        .recipient-editor-card label {
            color: #4b5563;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .recipient-editor-card .form-control {
            border-color: #e5e7eb;
            border-radius: 6px;
            color: #111827;
        }

        .recipient-editor-card .form-control:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.12);
        }

        .auto-link,
        .autosave-note,
        .context-hint {
            display: block;
            margin-top: 8px;
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 12px;
            line-height: 1.6;
        }

        .auto-link {
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #92400e;
            overflow-wrap: anywhere;
        }

        .autosave-note,
        .context-hint {
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

        .recipient-toast-stack {
            position: fixed;
            top: 82px;
            right: 22px;
            z-index: 9999;
            display: grid;
            gap: 10px;
            width: min(360px, calc(100vw - 32px));
            pointer-events: none;
        }

        .recipient-toast {
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
            animation: recipientToastIn 180ms ease-out forwards;
        }

        .recipient-toast.is-success {
            border-left-color: #0f766e;
        }

        .recipient-toast.is-error {
            border-left-color: #b91c1c;
        }

        .recipient-toast-icon {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: #fff7ed;
            color: #d97706;
        }

        .recipient-toast.is-success .recipient-toast-icon {
            background: rgba(15, 118, 110, 0.1);
            color: #0f766e;
        }

        .recipient-toast.is-error .recipient-toast-icon {
            background: rgba(185, 28, 28, 0.1);
            color: #b91c1c;
        }

        .recipient-toast-title {
            margin: 0;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.3;
        }

        .recipient-toast-message {
            margin: 2px 0 0;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.45;
        }

        @keyframes recipientToastIn {
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

<div class="recipient-toast-stack" id="recipientToastStack" aria-live="polite" aria-atomic="true"></div>

<div class="row clearfix recipient-editor-grid">
    <div class="col-xl-5 col-lg-6 col-md-12">
        <div class="card recipient-editor-card">
            <div class="header"><h2>{{ $pageTitle }}</h2></div>
            <div class="card-body py-3">
                <form method="post" action="{{ $formAction }}" id="recipientEditorForm" data-store-url="{{ route('admin.recipients.store') }}" data-update-url="{{ $isEdit ? route('admin.recipients.update', $recipient) : '' }}" data-recipient-id="{{ $recipient->id }}">
                    @csrf
                    @if ($method === 'PUT')
                        <input type="hidden" name="_method" value="PUT" data-method-field>
                    @endif
                    <input type="hidden" name="period_id" value="{{ $selectedPeriod?->id }}">
                    <input type="hidden" name="category_id" value="{{ $category->id }}">

                    <h3 class="section-title">Konteks Undangan</h3>
                    <div class="form-group">
                        <label>Event</label>
                        <input class="form-control" value="{{ $selectedPeriod?->name }}" readonly>
                    </div>
                    <div class="form-group">
                        <label>Kategori Undangan</label>
                        <input class="form-control" value="{{ $category->title }}" readonly>
                        <span class="context-hint">
                            @if ($category->usesPrivateAccess())
                                Link personal memakai token otomatis. Setelah nama penerima tersimpan, link final langsung muncul di bawah.
                            @elseif ($category->usesNipAccess())
                                Link kategori dibagikan ke penerima. Penerima membuka undangan dengan NIP yang terdaftar.
                            @else
                                Link kategori dibagikan ke penerima. Penerima membuka undangan dengan nama yang terdaftar.
                            @endif
                        </span>
                    </div>

                    <h3 class="section-title">Data Penerima</h3>
                    <div class="row">
                        @if ($category->usesNipAccess())
                        <div class="col-md-12 form-group">
                            <label>NIP</label>
                            <input class="form-control" name="identifier" value="{{ $identifier }}" placeholder="NIP penerima" inputmode="numeric" required>
                        </div>
                        @endif
                        <div class="col-md-4 form-group">
                            <label>Sapaan</label>
                            <select class="form-control" name="salutation">
                                <option value="" @selected(! $salutation)>Tanpa sapaan</option>
                                <option value="Bapak" @selected($salutation === 'Bapak')>Bapak</option>
                                <option value="Ibu" @selected($salutation === 'Ibu')>Ibu</option>
                                <option value="Saudara/i" @selected($salutation === 'Saudara/i')>Saudara/i</option>
                            </select>
                        </div>
                        <div class="col-md-8 form-group">
                            <label>Nama</label>
                            <input class="form-control" name="name" value="{{ $recipientName }}" placeholder="Nama penerima" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Jabatan</label>
                        <input class="form-control" name="position" value="{{ $position }}" placeholder="Contoh: Tenaga Kependidikan / Satpam / Cleaning Service">
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <input class="form-control" name="context_note" value="{{ $contextNote }}" placeholder="Contoh: Orang tua dari ...">
                    </div>
                    <div class="auto-link">
                        Link undangan: <strong data-auto-link>{{ $invitationUrl }}</strong>
                    </div>

                    <div class="autosave-note" data-autosave-inline>
                        <i class="fa fa-clock-o"></i>
                        <span>Perubahan akan tersimpan otomatis setelah nama terisi.</span>
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
                        <h2>Live Preview Undangan Private</h2>
                        <span>Nama penerima, sapaan, dan teks kategori tampil langsung</span>
                    </div>
                    <span data-preview-status>Otomatis</span>
                </div>
                <div class="preview-frame-wrap">
                    <iframe class="preview-frame" id="recipientPreviewFrame" title="Preview undangan private"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="recipientPreviewTemplate">
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
            <p class="guest">__INVITATION_NAME__</p>
            <button class="btn" id="openInvitation" type="button">Buka Undangan</button>
        </section>

        <section class="invitation invitation-layout" id="invitationContent">
            <article class="panel rsvp-card card--full admin-preview-rsvp watermark-card" __RSVP_HIDDEN__>
                <div class="rsvp-card-head">
                    <div class="rsvp-badge">&#10003;</div>
                    <div>
                        <h3>Konfirmasi Kehadiran</h3>
                        <p>Penerima private dapat mengisi konfirmasi melalui link personal yang dibagikan panitia.</p>
                    </div>
                </div>
                <div class="rsvp-steps">
                    <div class="rsvp-step is-active"><span class="rsvp-step-num">1</span><span>Buka link personal</span></div>
                    <div class="rsvp-step"><span class="rsvp-step-num">2</span><span>Baca detail undangan</span></div>
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
                            <h3>Catatan Penerima</h3>
                            <ol>
                                <li>__CONTEXT_NOTE__</li>
                                <li>Link private ini hanya berlaku untuk __INVITATION_NAME__.</li>
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
            var form = document.getElementById('recipientEditorForm');
            var frame = document.getElementById('recipientPreviewFrame');
            var template = document.getElementById('recipientPreviewTemplate').innerHTML;
            var previewStatus = document.querySelector('[data-preview-status]');
            var autoLink = document.querySelector('[data-auto-link]');
            var toastStack = document.getElementById('recipientToastStack');
            var autosaveInline = document.querySelector('[data-autosave-inline] span');
            var isPersisted = Boolean(form.dataset.recipientId);
            var currentLink = @json($invitationUrl);

            var eventData = @json($eventPreviewData);
            var categoryData = @json($categoryPreviewData);

            function field(name) {
                var input = form.querySelector('[name="' + name + '"]');
                return input ? input.value.trim() : '';
            }

            function invitationName() {
                return [field('salutation'), field('name')].filter(Boolean).join(' ') || 'Nama Penerima';
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
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

            function replaceAll(source, map) {
                Object.keys(map).forEach(function (key) {
                    source = source.split(key).join(map[key]);
                });

                return source;
            }

            function renderPreview() {
                var name = invitationName();
                var location = eventData.location || 'Gedung Hexagon Fakultas Teknik UNMUL';
                var address = eventData.address || 'Jl. Sambaliung No.9, Gunung Kelua, Samarinda';
                var mapQuery = encodeURIComponent([location, address].filter(Boolean).join(', '));

                autoLink.textContent = currentLink;

                frame.srcdoc = replaceAll(template, {
                    '__COVER_TEXT__': escapeHtml(categoryData.cover_text || 'Dengan hormat, Fakultas Teknik Universitas Mulawarman mengundang kehadiran Bapak/Ibu/Saudara(i) pada prosesi yudisium.'),
                    '__INVITATION_TEXT__': escapeHtml(categoryData.invitation_text || 'Dengan hormat, kami mengundang Bapak/Ibu/Saudara(i) untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.'),
                    '__CLOSING_TEXT__': escapeHtml(categoryData.closing_text || 'Atas kehadiran Bapak/Ibu/Saudara(i), kami ucapkan terima kasih.'),
                    '__INVITATION_NAME__': escapeHtml(name),
                    '__CONTEXT_NOTE__': escapeHtml(field('position') || field('context_note') || 'Tidak ada catatan khusus.'),
                    '__EVENT_DATE_LABEL__': escapeHtml(formatDate(eventData.date, 'long')),
                    '__EVENT_DATE_SHORT__': escapeHtml(formatDate(eventData.date, 'short')),
                    '__EVENT_TIME__': escapeHtml(eventData.time || '09.00 s/d Selesai'),
                    '__LOCATION__': escapeHtml(location),
                    '__ADDRESS__': escapeHtml(address),
                    '__MAP_QUERY__': mapQuery,
                    '__SIGNATURE_DATE_LABEL__': escapeHtml(signatureDateLabel(eventData.signature_city || 'Samarinda', eventData.date)),
                    '__SIGNER_NAME__': escapeHtml(eventData.signer_name || 'Nama Dekan / Pejabat'),
                    '__SIGNER_TITLE__': escapeHtml(eventData.signer_title || 'Dekan Fakultas Teknik Universitas Mulawarman'),
                    '__RSVP_HIDDEN__': categoryData.rsvp_enabled ? '' : 'hidden'
                });

                previewStatus.textContent = 'Terbaru';
            }

            function showToast(type, title, message) {
                var toast = document.createElement('div');
                toast.className = 'recipient-toast is-' + type;
                toast.innerHTML = [
                    '<div class="recipient-toast-icon"><i class="fa ' + (type === 'success' ? 'fa-check' : 'fa-exclamation') + '"></i></div>',
                    '<div>',
                    '<p class="recipient-toast-title">' + escapeHtml(title) + '</p>',
                    '<p class="recipient-toast-message">' + escapeHtml(message) + '</p>',
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
                form.dataset.recipientId = payload.id;
                form.dataset.updateUrl = payload.update_url || form.dataset.updateUrl;
                form.action = form.dataset.updateUrl;
                currentLink = payload.invitation_url || currentLink;
                autoLink.textContent = currentLink;
                ensureMethodField();

                if (payload.edit_url && window.location.href !== payload.edit_url) {
                    window.history.replaceState({}, '', payload.edit_url);
                }
            }

            function validationMessage(errors) {
                if (!errors) return 'Periksa kembali input penerima.';

                var firstKey = Object.keys(errors)[0];
                return firstKey && errors[firstKey] && errors[firstKey][0]
                    ? errors[firstKey][0]
                    : 'Periksa kembali input penerima.';
            }

            function saveRecipient() {
                if (!field('name')) {
                    setAutosaveText('Isi nama penerima supaya autosave mulai berjalan.', 'fa-info-circle');
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
                        showToast('success', 'Tersimpan', payload.message || 'Perubahan penerima tersimpan otomatis.');
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
                            saveTimer = setTimeout(saveRecipient, 220);
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
                saveTimer = setTimeout(saveRecipient, 900);
            }

            form.addEventListener('input', queuePreviewAndSave);
            form.addEventListener('change', queuePreviewAndSave);
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                clearTimeout(saveTimer);
                saveRecipient();
            });

            renderPreview();
        })();
    </script>
@endpush
