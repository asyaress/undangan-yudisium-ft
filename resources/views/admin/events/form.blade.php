@php
    $isEdit = $mode === 'edit';
    $pageTitle = $isEdit ? 'Edit Event' : 'Tambah Event';
    $agendaText = old('agenda_items', implode("\n", $period->agenda_items ?? []));
    $notesText = old('event_notes', implode("\n", $period->event_notes ?? []));
    $eventYear = old('event_year', $period->event_year ?? now()->year);
    $nameValue = old('name', $period->name);
    $autoSlug = \Illuminate\Support\Str::slug($nameValue ?: 'nama-event');
    $storeUrl = route('admin.periods.store');
    $updateUrl = $period->exists ? route('admin.periods.update', $period) : '';
@endphp

@extends('layouts.dashboard')

@section('title', $pageTitle)
@section('breadcrumb_parent', 'Event Yudisium')
@section('breadcrumb_active', $pageTitle)

@section('page_actions')
    @if ($isEdit)
        <a class="btn btn-outline-secondary" href="{{ route('undangan.show', ['slug' => $period->slug, 'to' => 'yudisiawan', 'preview' => 'open']) }}" target="_blank" rel="noopener">
            <i class="fa fa-eye"></i> Preview Publik
        </a>
    @endif
    <a class="btn btn-outline-secondary" href="{{ route('admin.events.index') }}">
        <i class="fa fa-arrow-left"></i> Kembali
    </a>
@endsection

@push('head')
    <style>
        .event-editor-grid {
            align-items: flex-start;
        }

        .event-editor-card .section-title {
            margin: 18px 0 12px;
            padding-top: 14px;
            border-top: 1px solid #eef0f3;
            color: #111827;
            font-size: 14px;
            font-weight: 800;
        }

        .event-editor-card .section-title:first-child {
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
        }

        .event-editor-card label {
            color: #4b5563;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .event-editor-card .form-control {
            border-color: #e5e7eb;
            border-radius: 6px;
            color: #111827;
        }

        .event-editor-card .form-control:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.12);
        }

        .auto-url {
            display: block;
            margin-top: 8px;
            padding: 10px 12px;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            background: #fff7ed;
            color: #92400e;
            font-size: 12px;
            line-height: 1.6;
            overflow-wrap: anywhere;
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

        .editor-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
            padding-top: 12px;
            border-top: 1px solid #eef0f3;
        }

        .autosave-note {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: #f8fafc;
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
        }

        .autosave-note i {
            color: #d97706;
        }

        .event-toast-stack {
            position: fixed;
            top: 82px;
            right: 22px;
            z-index: 9999;
            display: grid;
            gap: 10px;
            width: min(360px, calc(100vw - 32px));
            pointer-events: none;
        }

        .event-toast {
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
            animation: eventToastIn 180ms ease-out forwards;
        }

        .event-toast.is-success {
            border-left-color: #0f766e;
        }

        .event-toast.is-error {
            border-left-color: #b91c1c;
        }

        .event-toast-icon {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: #fff7ed;
            color: #d97706;
        }

        .event-toast.is-success .event-toast-icon {
            background: rgba(15, 118, 110, 0.1);
            color: #0f766e;
        }

        .event-toast.is-error .event-toast-icon {
            background: rgba(185, 28, 28, 0.1);
            color: #b91c1c;
        }

        .event-toast-title {
            margin: 0;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.3;
        }

        .event-toast-message {
            margin: 2px 0 0;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.45;
        }

        @keyframes eventToastIn {
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

<div class="event-toast-stack" id="eventToastStack" aria-live="polite" aria-atomic="true"></div>

<div class="row clearfix event-editor-grid">
    <div class="col-xl-5 col-lg-6 col-md-12">
        <div class="card event-editor-card">
            <div class="header"><h2>{{ $pageTitle }}</h2></div>
            <div class="card-body py-3">
                <form method="post" action="{{ $formAction }}" id="eventEditorForm" data-store-url="{{ $storeUrl }}" data-update-url="{{ $updateUrl }}" data-period-id="{{ $period->id }}">
                    @csrf
                    @if ($method === 'PUT')
                        <input type="hidden" name="_method" value="PUT" data-method-field>
                    @endif

                    <h3 class="section-title">Identitas Event</h3>
                    <div class="form-group">
                        <label>Nama Event</label>
                        <input class="form-control" name="name" value="{{ $nameValue }}" placeholder="Yudisium Tahun 2026 Angkatan 83 Periode 3" required>
                        <span class="auto-url">URL otomatis: <strong data-auto-url>{{ route('undangan.show', ['slug' => $period->slug ?: $autoSlug]) }}</strong></span>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Tahun</label>
                            <input class="form-control" name="event_year" type="number" min="2000" max="2100" value="{{ $eventYear }}" placeholder="2026">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Angkatan</label>
                            <input class="form-control" name="cohort_label" value="{{ old('cohort_label', $period->cohort_label) }}" placeholder="Angkatan 83">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Periode</label>
                            <input class="form-control" name="period_label" value="{{ old('period_label', $period->period_label) }}" placeholder="Periode 3">
                        </div>
                    </div>

                    <h3 class="section-title">Jadwal & Lokasi</h3>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Tanggal Acara</label>
                            <input class="form-control" name="event_date" type="date" value="{{ old('event_date', $period->event_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Waktu Acara</label>
                            <input class="form-control" name="event_time" value="{{ old('event_time', $period->event_time) }}" placeholder="09.00 s/d Selesai">
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Lokasi</label>
                            <input class="form-control" name="location" value="{{ old('location', $period->location) }}" placeholder="Gedung Hexagon Fakultas Teknik UNMUL">
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Alamat</label>
                            <input class="form-control" name="address" value="{{ old('address', $period->address) }}" placeholder="Jl. Sambaliung No.9, Gunung Kelua, Samarinda">
                        </div>
                    </div>

                    <h3 class="section-title">Check-in & RSVP</h3>
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label>Batas Konfirmasi Kehadiran</label>
                            <input class="form-control" name="rsvp_deadline" type="datetime-local" value="{{ old('rsvp_deadline', $period->rsvp_deadline?->format('Y-m-d\\TH:i')) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Check-in Dibuka</label>
                            <input class="form-control" name="checkin_opens_at" type="datetime-local" value="{{ old('checkin_opens_at', $period->checkin_opens_at?->format('Y-m-d\\TH:i')) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Check-in Ditutup</label>
                            <input class="form-control" name="checkin_closes_at" type="datetime-local" value="{{ old('checkin_closes_at', $period->checkin_closes_at?->format('Y-m-d\\TH:i')) }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Radius</label>
                            <input class="form-control" name="checkin_radius_meter" type="number" min="100" max="1000" value="{{ old('checkin_radius_meter', $period->checkin_radius_meter ?: 300) }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Latitude</label>
                            <input class="form-control" name="checkin_latitude" type="number" step="0.0000001" value="{{ old('checkin_latitude', $period->checkin_latitude) }}" placeholder="-0.4690">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Longitude</label>
                            <input class="form-control" name="checkin_longitude" type="number" step="0.0000001" value="{{ old('checkin_longitude', $period->checkin_longitude) }}" placeholder="117.1436">
                        </div>
                    </div>

                    <h3 class="section-title">Isi Undangan</h3>
                    <div class="form-group">
                        <label>Susunan Acara</label>
                        <textarea class="form-control" name="agenda_items" rows="8" placeholder="Pembukaan&#10;Prosesi Yudisium&#10;a. Pembacaan SK Yudisium&#10;b. Penyerahan Medali dan Sertifikat Yudisium">{{ $agendaText }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Catatan Undangan</label>
                        <textarea class="form-control" name="event_notes" rows="4" placeholder="Satu catatan per baris">{{ $notesText }}</textarea>
                    </div>

                    <h3 class="section-title">Tanda Tangan</h3>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Kota</label>
                            <input class="form-control" name="signature_city" value="{{ old('signature_city', $period->signature_city ?: 'Samarinda') }}" placeholder="Samarinda">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Nama</label>
                            <input class="form-control" name="signer_name" value="{{ old('signer_name', $period->signer_name) }}" placeholder="Nama dekan / pejabat">
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Jabatan</label>
                            <input class="form-control" name="signer_title" value="{{ old('signer_title', $period->signer_title ?: 'Dekan Fakultas Teknik Universitas Mulawarman') }}" placeholder="Dekan Fakultas Teknik Universitas Mulawarman">
                        </div>
                    </div>

                    <h3 class="section-title">Status</h3>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="fancy-checkbox">
                                <input type="hidden" name="checkin_location_required" value="0">
                                <input name="checkin_location_required" type="checkbox" value="1" @checked((string) old('checkin_location_required', $period->exists ? (int) $period->checkin_location_required : 1) === '1')>
                                <span>Validasi lokasi</span>
                            </label>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="fancy-checkbox">
                                <input type="hidden" name="is_active" value="0">
                                <input name="is_active" type="checkbox" value="1" @checked((string) old('is_active', $period->exists ? (int) $period->is_active : 1) === '1')>
                                <span>Event aktif</span>
                            </label>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="fancy-checkbox">
                                <input type="hidden" name="is_published" value="0">
                                <input name="is_published" type="checkbox" value="1" @checked((string) old('is_published', $period->exists ? (int) $period->is_published : 1) === '1')>
                                <span>Tampil publik</span>
                            </label>
                        </div>
                    </div>

                    <div class="autosave-note" data-autosave-inline>
                        <i class="fa fa-clock-o"></i>
                        <span>Perubahan akan tersimpan otomatis.</span>
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
                        <span>Mengikuti UI undangan publik yang sedang dipakai</span>
                    </div>
                    <span data-preview-status>Otomatis</span>
                </div>
                <div class="preview-frame-wrap">
                    <iframe class="preview-frame" id="invitationPreviewFrame" title="Preview undangan"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="invitationPreviewTemplate">
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
            <p class="guest">Yudisiawan/Yudisiawati</p>
            <button class="btn" id="openInvitation" type="button">Buka Undangan</button>
        </section>

        <section class="invitation invitation-layout" id="invitationContent">
            <article class="panel rsvp-card card--full admin-preview-rsvp watermark-card">
                <div class="rsvp-card-head">
                    <div class="rsvp-badge">✓</div>
                    <div>
                        <h3>Konfirmasi Kehadiran</h3>
                        <p>Masukkan NIM terlebih dahulu. Setelah cocok dengan data panitia, undangan dan formulir konfirmasi akan terbuka.</p>
                    </div>
                </div>
                <div class="rsvp-steps">
                    <div class="rsvp-step is-active"><span class="rsvp-step-num">1</span><span>Masukkan NIM</span></div>
                    <div class="rsvp-step"><span class="rsvp-step-num">2</span><span>Periksa data mahasiswa</span></div>
                    <div class="rsvp-step"><span class="rsvp-step-num">3</span><span>Isi status kehadiran dan simpan bukti</span></div>
                </div>
            </article>

            <div class="invitation-details-section card--full">
                <div class="details-divider" id="invitationDetails">Detail Undangan</div>
                <div class="invitation-details" id="invitationContentDetails">
                    <article class="panel card card--white card--full">
                        <h2 class="title">Dengan Hormat</h2>
                        <p class="line">Dengan hormat, kami mengundang Yudisiawan/Yudisiawati untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.</p>
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
                            <h3>Susunan Acara</h3>
                            <ol>__AGENDA_ITEMS__</ol>
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

                    <article class="panel card card--white card--full note-card watermark-card">
                        <h3 class="title">Catatan</h3>
                        <ol class="notes-list">__EVENT_NOTES__</ol>
                    </article>

                    <article class="panel card card--white card--full closing-card">
                        <p class="closing">Atas kehadiran Yudisiawan/Yudisiawati, kami ucapkan terima kasih.</p>
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
            var form = document.getElementById('eventEditorForm');
            var frame = document.getElementById('invitationPreviewFrame');
            var template = document.getElementById('invitationPreviewTemplate').innerHTML;
            var previewStatus = document.querySelector('[data-preview-status]');
            var autoUrl = document.querySelector('[data-auto-url]');
            var toastStack = document.getElementById('eventToastStack');
            var autosaveInline = document.querySelector('[data-autosave-inline] span');
            var baseInvitationUrl = @json(url('/undangan'));
            var isPersisted = Boolean(form.dataset.periodId);

            var defaults = {
                eventName: 'Yudisium Tahun 2026 Angkatan 83 Periode 3',
                eventYear: String(new Date().getFullYear()),
                cohortLabel: 'Angkatan 83',
                periodLabel: 'Periode 3',
                eventTime: '09.00 s/d Selesai',
                location: 'Gedung Hexagon Fakultas Teknik UNMUL',
                address: 'Jl. Sambaliung No.9, Gunung Kelua, Samarinda',
                signatureCity: 'Samarinda',
                signerName: 'Nama Dekan / Pejabat',
                signerTitle: 'Dekan Fakultas Teknik Universitas Mulawarman'
            };

            function field(name) {
                var input = form.querySelector('[name="' + name + '"]');
                return input ? input.value.trim() : '';
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
                    .replace(/-{2,}/g, '-') || 'nama-event';
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

            function parseAgenda(value) {
                var lines = String(value || '').split(/\r\n|\r|\n/).map(function (line) {
                    return line.trim();
                }).filter(Boolean);

                if (!lines.length) {
                    lines = [
                        'Pembukaan',
                        'Lagu Kebangsaan Indonesia Raya dan Mars Fakultas Teknik',
                        'Prosesi Yudisium',
                        'a. Pembacaan SK Yudisium',
                        'b. Penyerahan Medali dan Sertifikat Yudisium',
                        'Foto Bersama'
                    ];
                }

                var agenda = [];
                lines.forEach(function (line) {
                    var child = line.match(/^(?:[-*]\s+|[a-z]\s*[\.\)]\s+)(.+)$/i);
                    if (child && agenda.length) {
                        agenda[agenda.length - 1].children.push(child[1].trim());
                        return;
                    }

                    agenda.push({
                        title: line.replace(/^\d+\s*[\.\)]\s*/, ''),
                        children: []
                    });
                });

                return agenda.map(function (item) {
                    var children = item.children.length
                        ? '<ol>' + item.children.map(function (child) {
                            return '<li>' + escapeHtml(child) + '</li>';
                        }).join('') + '</ol>'
                        : '';

                    return '<li>' + escapeHtml(item.title) + children + '</li>';
                }).join('');
            }

            function parseNotes(value) {
                var lines = String(value || '').split(/\r\n|\r|\n/).map(function (line) {
                    return line.trim();
                }).filter(Boolean);

                if (!lines.length) {
                    lines = [
                        'Hadir 30 menit sebelum acara dimulai.',
                        'Pakaian PSL untuk dosen dan karyawan, serta menyesuaikan ketentuan panitia bagi peserta lainnya.'
                    ];
                }

                return lines.map(function (line) {
                    return '<li>' + escapeHtml(line) + '</li>';
                }).join('');
            }

            function replaceAll(source, map) {
                Object.keys(map).forEach(function (key) {
                    source = source.split(key).join(map[key]);
                });

                return source;
            }

            function renderPreview() {
                var name = field('name') || defaults.eventName;
                var year = field('event_year') || defaults.eventYear;
                var cohort = field('cohort_label') || defaults.cohortLabel;
                var period = field('period_label') || defaults.periodLabel;
                var coverText = [cohort, period, 'Tahun ' + year].filter(Boolean).join(' ');
                var location = field('location') || defaults.location;
                var address = field('address') || defaults.address;
                var mapQuery = encodeURIComponent([location, address].filter(Boolean).join(', '));
                var slug = slugify(name);

                autoUrl.textContent = baseInvitationUrl + '/' + slug;

                frame.srcdoc = replaceAll(template, {
                    '__COVER_TEXT__': escapeHtml(coverText),
                    '__EVENT_DATE_LABEL__': escapeHtml(formatDate(field('event_date'), 'long')),
                    '__EVENT_DATE_SHORT__': escapeHtml(formatDate(field('event_date'), 'short')),
                    '__EVENT_TIME__': escapeHtml(field('event_time') || defaults.eventTime),
                    '__LOCATION__': escapeHtml(location),
                    '__ADDRESS__': escapeHtml(address),
                    '__AGENDA_ITEMS__': parseAgenda(field('agenda_items')),
                    '__MAP_QUERY__': mapQuery,
                    '__SIGNATURE_DATE_LABEL__': escapeHtml(signatureDateLabel(field('signature_city') || defaults.signatureCity, field('event_date'))),
                    '__SIGNER_NAME__': escapeHtml(field('signer_name') || defaults.signerName),
                    '__SIGNER_TITLE__': escapeHtml(field('signer_title') || defaults.signerTitle),
                    '__EVENT_NOTES__': parseNotes(field('event_notes'))
                });

                previewStatus.textContent = 'Terbaru';
            }

            function showToast(type, title, message) {
                var toast = document.createElement('div');
                toast.className = 'event-toast is-' + type;
                toast.innerHTML = [
                    '<div class="event-toast-icon"><i class="fa ' + (type === 'success' ? 'fa-check' : 'fa-exclamation') + '"></i></div>',
                    '<div>',
                    '<p class="event-toast-title">' + escapeHtml(title) + '</p>',
                    '<p class="event-toast-message">' + escapeHtml(message) + '</p>',
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
                form.dataset.periodId = payload.id;
                form.dataset.updateUrl = payload.update_url || form.dataset.updateUrl;
                form.action = form.dataset.updateUrl;
                ensureMethodField();

                if (payload.edit_url && window.location.href !== payload.edit_url) {
                    window.history.replaceState({}, '', payload.edit_url);
                }

                if (payload.invitation_url) {
                    autoUrl.textContent = payload.invitation_url;
                }
            }

            function validationMessage(errors) {
                if (!errors) return 'Periksa kembali input event.';

                var firstKey = Object.keys(errors)[0];
                return firstKey && errors[firstKey] && errors[firstKey][0]
                    ? errors[firstKey][0]
                    : 'Periksa kembali input event.';
            }

            function saveEvent() {
                if (!field('name')) {
                    setAutosaveText('Isi nama event supaya autosave mulai berjalan.', 'fa-info-circle');
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
                        showToast('success', 'Tersimpan', payload.message || 'Perubahan tersimpan otomatis.');
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
                            saveTimer = setTimeout(saveEvent, 220);
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
                saveTimer = setTimeout(saveEvent, 900);
            }

            form.addEventListener('input', queuePreviewAndSave);
            form.addEventListener('change', queuePreviewAndSave);
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                clearTimeout(saveTimer);
                saveEvent();
            });

            renderPreview();
        })();
    </script>
@endpush
