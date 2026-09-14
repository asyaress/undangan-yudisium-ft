@php
    $guestName = $participant?->name
        ?? $recipient?->invitation_name
        ?? $category->recipient_label
        ?? 'Tamu Undangan';
    $recipientSalutation = trim((string) $recipient?->salutation);
    $tutorialGreeting = $recipient
        ? $recipient->politeAddress()
        : ($participant ? ($category->recipient_label ?: 'Yudisiawan/Yudisiawati') : ($category->recipient_label ?: 'Tamu Undangan'));
    $invitationGreeting = $tutorialGreeting;
    $eventDateLabel = $period->event_date?->locale('id')->translatedFormat('l, d F Y') ?? 'Tanggal menunggu konfirmasi';
    $eventDateShort = $period->event_date?->locale('id')->translatedFormat('d F Y') ?? 'Tanggal menunggu konfirmasi';
    $eventTime = $period->event_time ?: '09.00 s/d Selesai';
    $eventLocation = $period->location ?: 'Gedung utama Fakultas Teknik Universitas Mulawarman';
    $eventAddress = $period->address ?: 'Jl. Sambaliung No.9, Gunung Kelua, Samarinda';
    $mapQuery = rawurlencode($eventLocation.' '.$eventAddress);
    $signatureCity = $period->signature_city ?: 'Samarinda';
    $signatureDate = str_contains($signatureCity, ',') ? $signatureCity : trim($signatureCity.', '.$eventDateShort, ', ');
    $signerName = $period->signer_name ?: 'Prof. Dr. Ir. Thamrin, S.T., M.T, IPU, ASEAN. Eng., APEC. Eng';
    $signerTitle = $period->signer_title ?: 'Dekan Fakultas Teknik Universitas Mulawarman';
    $agendaItems = $period->agenda_list;
    $eventNotes = $period->event_note_list;
    $rsvpStatus = $participant?->rsvp_status ?? $recipient?->rsvp_status ?? 'pending';
    $rsvpStatusLabels = [
        'attending' => 'kehadiran',
        'declined' => 'berhalangan hadir',
        'represented' => 'diwakilkan',
    ];
    $rsvpBadgeLabels = [
        'attending' => 'Bersedia Hadir',
        'declined' => 'Berhalangan Hadir',
        'represented' => 'Diwakilkan',
    ];
    $canonicalCategory = $recipient?->invitationCategory() ?: $category;
    $recipientAllowsRepresentative = $canonicalCategory->usesPrivateAccess();
    $recipientUsesSignature = $canonicalCategory->usesPrivateAccess() || $canonicalCategory->usesNipAccess();
    $confirmationOptionsText = $participant || ! $recipientAllowsRepresentative ? 'hadir atau berhalangan' : 'hadir, berhalangan, atau diwakilkan';
    $rsvpClosed = $period->rsvpIsClosed();
    $rsvpDeadlineLabel = $period->rsvp_deadline?->locale('id')->translatedFormat('d F Y H:i');
    $rsvpRespondedAt = $participant?->rsvp_responded_at ?? $recipient?->responded_at;
    $playgroundReturnUrl = request()->getRequestUri().'#letterRsvp';
    $studentQrPayload = $participant
        ? 'YFT|'.$period->id.'|'.$participant->id.'|'.$participant->invitation_token
        : null;
    $studentQrFileName = $participant
        ? 'qr-buku-tamu-'.$participant->nim.'.png'
        : null;
    $showStudentQrCard = $participant && $studentQrPayload && $rsvpStatus === 'attending';
    $studentQrGuideSteps = $showStudentQrCard
        ? [[
            'text' => 'Konfirmasi sudah tersimpan. Unduh kartu konfirmasi ini dan simpan di ponsel. Saat hari acara, tunjukkan QR-nya di meja registrasi untuk dipindai panitia.',
            'target' => 'letterStudentQr',
        ]]
        : [];
    $formalTutorialSteps = [
        ['text' => '__OPENING__', 'target' => 'letterRecipientName'],
        ['text' => "Mulai dari sini, {$tutorialGreeting} bisa cek waktu, tempat, dan alamat acara. Bagian ini yang paling penting untuk dicatat sebelum hadir.", 'target' => 'letterDetail'],
        ['text' => "Berikutnya, {$tutorialGreeting} bisa melihat susunan acara. Bagian ini menunjukkan alur prosesi dari pembukaan sampai sesi penutup.", 'target' => 'letterAgenda'],
    ];
    if ($canonicalCategory->requiresRsvp()) {
        $formalTutorialSteps[] = [
            'text' => "Di bagian akhir, mohon isi konfirmasi kehadiran sesuai kondisi sebenarnya: {$confirmationOptionsText}.",
            'target' => 'letterRsvp',
        ];
    }
    $formalInvitationBoot = [
        'tutorialAudience' => $recipientSalutation,
        'shouldShowStudentQrGuide' => $showStudentQrCard && session('success'),
        'studentQrGuideSteps' => $studentQrGuideSteps,
        'tutorialSteps' => $formalTutorialSteps,
    ];
@endphp

@extends(($standalone ?? false) ? 'layouts.playground' : 'layouts.dashboard')

@section('title', 'Playground Undangan')
@section('breadcrumb_parent', 'Eksperimen')
@section('breadcrumb_active', 'Undangan Formal')

@section('page_actions')
    @unless($standalone ?? false)
        <a class="btn btn-outline-secondary" href="{{ $originalUrl }}" target="_blank" rel="noopener">
            <i class="fa fa-external-link"></i> Buka Undangan Utama
        </a>
    @endunless
@endsection

@push('head')
    <meta name="theme-color" content="#ffffff">
    <meta name="color-scheme" content="light">
    <style id="formal-critical">
        html, body { margin: 0; background: #fff; color: #1c1c1e; }
        .formal-preview-stage:not(.is-open) .formal-bg-video,
        .formal-preview-stage:not(.is-open) .formal-bg-overlay {
            display: none !important;
            visibility: hidden !important;
        }
        .formal-preview-stage:not(.is-open) {
            background: #fff !important;
            min-height: 100dvh;
        }
        .formal-cover {
            position: relative;
            z-index: 3;
            display: grid;
            place-items: center;
            min-height: 100dvh;
            background: #fff;
            text-align: center;
        }
    </style>
    <link rel="preload" href="{{ asset('css/formal-invitation.css') }}?v=3" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('css/formal-invitation.css') }}?v=3"></noscript>
    @if ($standalone ?? false)
        <link rel="preload" href="{{ asset('js/formal-invitation.js') }}?v=2" as="script">
    @endif
@endpush

@section('content')
@unless($standalone ?? false)
    @include('layouts.partials.block-header')

    <div class="card playground-filter">
        <div class="header"><h2>Playground Konsep Undangan Formal</h2></div>
        <div class="card-body py-3">
            <form method="get" class="row">
                <div class="col-lg-4 col-md-6 form-group">
                    <label>Event</label>
                    <select class="form-control" name="period_id" onchange="this.form.submit()">
                        @foreach ($periods as $item)
                            <option value="{{ $item->id }}" @selected($item->id === $period->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 form-group">
                    <label>Kategori</label>
                    <select class="form-control" name="category" onchange="this.form.submit()">
                        @foreach ($categories as $item)
                            <option value="{{ $item->slug }}" @selected($item->id === $category->id)>{{ $item->title }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($recipientOptions->isNotEmpty())
                    <div class="col-lg-4 col-md-6 form-group">
                        <label>Penerima</label>
                        <select class="form-control" name="recipient_id" onchange="this.form.submit()">
                            @foreach ($recipientOptions as $item)
                                <option value="{{ $item->id }}" @selected($recipient?->id === $item->id)>{{ trim(($item->salutation ? $item->salutation.' ' : '').$item->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if ($participantOptions->isNotEmpty())
                    <div class="col-lg-4 col-md-6 form-group">
                        <label>Mahasiswa</label>
                        <select class="form-control" name="participant_id" onchange="this.form.submit()">
                            @foreach ($participantOptions as $item)
                                <option value="{{ $item->id }}" @selected($participant?->id === $item->id)>{{ $item->name }} - {{ $item->nim }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </form>
        </div>
    </div>
@endunless

<div class="formal-preview-stage" id="formalPreviewStage">
    <video class="formal-bg-video" muted loop playsinline preload="none" hidden>
        <source data-src="{{ asset('video-back.mp4') }}" type="video/mp4">
    </video>
    <div class="formal-bg-overlay" aria-hidden="true" hidden></div>

    <section class="formal-cover" id="formalCover">
        <div class="formal-cover-panel">
            <p class="formal-cover-label">Undangan</p>
            <h2>Yudisium Fakultas Teknik</h2>
            <img class="formal-cover-logo" src="{{ asset('Unmul.png') }}" alt="Lambang Universitas Mulawarman">
            <p>{{ $category->cover_text ?: 'Fakultas Teknik Universitas Mulawarman mengundang kehadiran pada prosesi yudisium.' }}</p>
            <span class="formal-cover-guest-label">Kepada Yth.</span>
            <p class="formal-cover-guest">{{ $guestName }}</p>
            <button class="formal-open-btn" type="button" id="formalOpenButton">Buka Undangan</button>
        </div>
    </section>

    <section class="formal-tutorial" id="formalTutorial" aria-modal="true" role="dialog" aria-labelledby="formalTutorialTitle">
        <div class="formal-spotlight" id="formalSpotlight" aria-hidden="true"></div>
        <div class="formal-tutorial-card" id="formalTutorialCard">
            <div class="formal-tutorial-copy">
                <h3 id="formalTutorialTitle">Panduan Undangan</h3>
                <p id="formalTutorialText"></p>
                <div class="formal-tutorial-footer">
                    <span class="formal-tutorial-progress" id="formalTutorialProgress"></span>
                    <div class="formal-tutorial-actions">
                        <button type="button" id="formalTutorialSkip">Tutup</button>
                        <button class="primary" type="button" id="formalTutorialNext">Lanjut</button>
                    </div>
                </div>
            </div>
            <div class="formal-tutorial-guide" aria-hidden="true">
                <img src="{{ asset('tutorial-guide.png') }}" alt="">
            </div>
        </div>
    </section>

    <article class="letter-sheet">
        <div class="letter-content">
            <header class="letter-head">
                <img class="letter-logo" src="{{ asset('Unmul.png') }}" alt="Lambang Universitas Mulawarman">
                <div>
                    <p class="letter-university">Universitas Mulawarman</p>
                    <h1 class="letter-title">{{ $period->archive_title }}</h1>
                    <p class="letter-subtitle">Fakultas Teknik</p>
                </div>
            </header>

            <div class="recipient-line letter-reveal" id="letterRecipient">
                <span>Kepada Yth.</span>
                <strong class="recipient-focus" id="letterRecipientName">{{ $guestName }}</strong>
                @if ($recipient)
                    @forelse ($recipient->listedPositions() as $position)
                        <span>{{ $position }}</span>
                    @empty
                        @if ($recipient->context_note)
                            <span>{{ $recipient->context_note }}</span>
                        @else
                            <span>{{ $category->recipient_label }}</span>
                        @endif
                    @endforelse
                @elseif ($participant?->studyProgram)
                    <span>{{ $participant->studyProgram->name }}</span>
                @else
                    <span>{{ $category->recipient_label }}</span>
                @endif
            </div>

            <p class="letter-intro letter-reveal">
                {{ $category->invitation_text ?: "Dengan hormat, kami mengundang {$invitationGreeting} untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman." }}
            </p>

            <section class="info-grid letter-reveal" id="letterDetail" aria-label="Detail undangan">
                <div class="info-item">
                    <span class="info-label">Hari/Tanggal</span>
                    <span class="info-value">{{ $eventDateLabel }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Waktu</span>
                    <span class="info-value">{{ $eventTime }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tempat</span>
                    <span class="info-value">{{ $eventLocation }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Alamat</span>
                    <span class="info-value">{{ $eventAddress }}</span>
                </div>
            </section>

            <section class="letter-section letter-reveal" id="letterAgenda">
                <h3>Susunan Acara</h3>
                <ol class="agenda-list">
                    @foreach ($agendaItems as $agendaItem)
                        <li>
                            {{ $agendaItem['title'] ?? $agendaItem }}
                            @if (! empty($agendaItem['children']))
                                <ol>
                                    @foreach ($agendaItem['children'] as $agendaChild)
                                        <li>{{ $agendaChild }}</li>
                                    @endforeach
                                </ol>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="letter-section letter-reveal" id="letterLocation">
                <h3>Lokasi Acara</h3>
                <div class="map-panel">
                    <div class="map-copy">
                        <strong>{{ $eventLocation }}</strong>
                        <span>{{ $eventAddress }}</span>
                        <a class="map-action" href="https://www.google.com/maps/search/?api=1&query={{ $mapQuery }}" target="_blank" rel="noopener">
                            <span>Buka petunjuk lokasi</span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M7 17 17 7"></path>
                                <path d="M9 7h8v8"></path>
                            </svg>
                        </a>
                    </div>
                    <div class="map-frame">
                        <iframe title="Peta lokasi acara" src="https://maps.google.com/maps?q={{ $mapQuery }}&t=&z=16&ie=UTF8&iwloc=&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </section>

            <section class="letter-section letter-reveal" id="letterNotes">
                <h3>Catatan</h3>
                <ol class="notes-list">
                    @foreach ($eventNotes as $note)
                        <li>{{ $note }}</li>
                    @endforeach
                </ol>
            </section>

            @if ($category->requiresRsvp())
                <section class="letter-section letter-reveal" id="letterRsvp">
                    <h3>Konfirmasi Kehadiran</h3>
                    <div class="rsvp-strip">
                        @if (session('success') && $rsvpStatus === 'pending')
                            <div class="playground-flash good">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                            <div class="playground-flash error">{{ session('error') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="playground-flash error">{{ $errors->first() }}</div>
                        @endif
                        @if ($rsvpClosed)
                            <div class="playground-flash error">Pengisian konfirmasi kehadiran telah ditutup{{ $rsvpDeadlineLabel ? ' sejak '.$rsvpDeadlineLabel : '' }}.</div>
                        @endif

                        @if ($rsvpStatus !== 'pending')
                            <div class="playground-success" role="status" aria-live="polite">
                                <span class="playground-success-icon" aria-hidden="true">
                                    @if ($rsvpStatus === 'declined')
                                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18M9.5 14.5l5 5M14.5 14.5l-5 5"></path></svg>
                                    @elseif ($rsvpStatus === 'represented')
                                        <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M17 8l4 4-4 4M21 12h-7"></path></svg>
                                    @else
                                        <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>
                                    @endif
                                </span>
                                <span class="playground-success-copy">
                                    <strong class="playground-success-label">{{ $rsvpBadgeLabels[$rsvpStatus] ?? 'Terkonfirmasi' }}</strong>
                                    <span>
                                        Konfirmasi {{ $rsvpStatusLabels[$rsvpStatus] ?? 'kehadiran' }} tersimpan
                                        @if ($rsvpRespondedAt)
                                            <time datetime="{{ $rsvpRespondedAt->toIso8601String() }}">{{ $rsvpRespondedAt->locale('id')->translatedFormat('d F Y H:i') }} WITA</time>
                                        @endif
                                    </span>
                                </span>
                            </div>
                        @elseif ($rsvpClosed && ($recipient || $participant))
                            <div class="playground-rsvp-person">
                                @if ($recipient)
                                    <strong>{{ $recipient->invitation_name }}</strong>
                                    @foreach ($recipient->listedPositions() as $position)
                                        <span>{{ $position }}</span>
                                    @endforeach
                                @else
                                    <strong>{{ $participant->name }}</strong>
                                    <span>{{ $participant->studyProgram?->name ?: ($participant->study_program ?: 'Program studi belum diisi') }}</span>
                                @endif
                            </div>
                        @elseif (! $rsvpClosed && $recipient)
                            <form method="post" action="{{ route('rsvp.recipient') }}" class="playground-rsvp-form" id="playgroundRecipientRsvpForm">
                                @csrf
                                <input type="hidden" name="recipient_id" value="{{ $recipient->id }}">
                                <input type="hidden" name="token" value="{{ $recipient->token }}">
                                <input type="hidden" name="return_to" value="{{ $playgroundReturnUrl }}">
                                <div class="playground-rsvp-person">
                                    <strong>{{ $recipient->invitation_name }}</strong>
                                    @foreach ($recipient->listedPositions() as $position)
                                        <span>{{ $position }}</span>
                                    @endforeach
                                </div>
                                <div class="playground-field">
                                    <label>Status Kehadiran</label>
                                    <div class="radio-grid {{ $recipientAllowsRepresentative ? '' : 'two-options' }}">
                                        <label class="radio-option">
                                            <input type="radio" name="attendance" value="attending" @checked(old('attendance', $recipient->rsvp_status) === 'attending') required>
                                            <span class="radio-mark" aria-hidden="true"></span>
                                            <span class="radio-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>
                                            </span>
                                            <span>Bersedia Hadir</span>
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="attendance" value="declined" @checked(old('attendance', $recipient->rsvp_status) === 'declined') required>
                                            <span class="radio-mark" aria-hidden="true"></span>
                                            <span class="radio-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18M9.5 14.5l5 5M14.5 14.5l-5 5"></path></svg>
                                            </span>
                                            <span>Berhalangan Hadir</span>
                                        </label>
                                        @if ($recipientAllowsRepresentative)
                                            <label class="radio-option">
                                                <input type="radio" name="attendance" value="represented" @checked(old('attendance', $recipient->rsvp_status) === 'represented') required>
                                                <span class="radio-mark" aria-hidden="true"></span>
                                                <span class="radio-icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M17 8l4 4-4 4M21 12h-7"></path></svg>
                                                </span>
                                                <span>Diwakilkan</span>
                                            </label>
                                        @endif
                                    </div>
                                </div>
                                <div class="playground-field" data-playground-note-field hidden>
                                    <label for="playground-recipient-note" data-playground-note-label>Catatan berhalangan</label>
                                    <textarea class="playground-note" id="playground-recipient-note" name="note" data-declined-placeholder="Tuliskan alasan berhalangan hadir secara singkat." placeholder="Tuliskan alasan berhalangan hadir secara singkat.">{{ old('note') }}</textarea>
                                </div>
                                <div class="playground-delegate-grid" data-playground-delegate-fields hidden>
                                    <div class="playground-field">
                                        <label for="playground-recipient-representative-name">Nama Perwakilan</label>
                                        <input class="playground-input" id="playground-recipient-representative-name" name="representative_name" value="{{ old('representative_name') }}" placeholder="Nama lengkap perwakilan">
                                    </div>
                                    <div class="playground-field">
                                        <label for="playground-recipient-representative-position">Jabatan Perwakilan</label>
                                        <input class="playground-input" id="playground-recipient-representative-position" name="representative_position" value="{{ old('representative_position') }}" placeholder="Jabatan perwakilan">
                                    </div>
                                </div>
                                @if ($recipientUsesSignature)
                                    <div class="playground-field playground-signature-field" data-playground-signature-field hidden>
                                        <div class="playground-signature-head">
                                            <label for="playground-recipient-signature" data-playground-signature-label>Tanda tangan</label>
                                            <button type="button" class="playground-signature-clear" data-playground-signature-clear>Hapus</button>
                                        </div>
                                        <div class="playground-signature-pad">
                                            <canvas id="playground-recipient-signature" data-playground-signature-canvas aria-label="Area tanda tangan"></canvas>
                                            <span class="playground-signature-placeholder" data-playground-signature-placeholder>Tulis di sini</span>
                                        </div>
                                        <input type="hidden" name="rsvp_signature" data-playground-signature-input>
                                        <input type="hidden" name="signature_drawn" value="0" data-playground-signature-drawn>
                                        <p class="playground-signature-help" data-playground-signature-help>Bubuhkan tanda tangan sebagai konfirmasi kehadiran.</p>
                                        <p class="playground-signature-error" data-playground-signature-error hidden>Mohon isi tanda tangan terlebih dahulu.</p>
                                    </div>
                                @endif
                                <button class="playground-submit" type="submit">Simpan Konfirmasi</button>
                            </form>
                        @elseif (! $rsvpClosed && $participant)
                            <form method="post" action="{{ route('rsvp.participant') }}" class="playground-rsvp-form" id="playgroundParticipantRsvpForm">
                                @csrf
                                <input type="hidden" name="event_id" value="{{ $period->id }}">
                                <input type="hidden" name="participant_token" value="{{ $participant->invitation_token }}">
                                <input type="hidden" name="return_to" value="{{ $playgroundReturnUrl }}">
                                <div class="playground-rsvp-person">
                                    <strong>{{ $participant->name }}</strong>
                                    <span>{{ $participant->studyProgram?->name ?: ($participant->study_program ?: 'Program studi belum diisi') }}</span>
                                </div>
                                <div class="playground-field">
                                    <label>Status Kehadiran</label>
                                    <div class="radio-grid two-options">
                                        <label class="radio-option">
                                            <input type="radio" name="attendance" value="attending" @checked(old('attendance', $participant->rsvp_status) === 'attending') required>
                                            <span class="radio-mark" aria-hidden="true"></span>
                                            <span class="radio-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>
                                            </span>
                                            <span>Bersedia Hadir</span>
                                        </label>
                                        <label class="radio-option">
                                            <input type="radio" name="attendance" value="declined" @checked(old('attendance', $participant->rsvp_status) === 'declined') required>
                                            <span class="radio-mark" aria-hidden="true"></span>
                                            <span class="radio-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18M9.5 14.5l5 5M14.5 14.5l-5 5"></path></svg>
                                            </span>
                                            <span>Berhalangan Hadir</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="playground-field" data-playground-note-field hidden>
                                    <label for="playground-participant-note" data-playground-note-label>Catatan berhalangan</label>
                                    <textarea class="playground-note" id="playground-participant-note" name="note" data-declined-placeholder="Tuliskan alasan berhalangan hadir secara singkat." placeholder="Tuliskan alasan berhalangan hadir secara singkat.">{{ old('note') }}</textarea>
                                </div>
                                <div class="playground-field playground-signature-field" data-playground-signature-field hidden>
                                    <div class="playground-signature-head">
                                        <label for="playground-participant-signature" data-playground-signature-label>Tanda tangan</label>
                                        <button type="button" class="playground-signature-clear" data-playground-signature-clear>Hapus</button>
                                    </div>
                                    <div class="playground-signature-pad">
                                        <canvas id="playground-participant-signature" data-playground-signature-canvas aria-label="Area tanda tangan"></canvas>
                                        <span class="playground-signature-placeholder" data-playground-signature-placeholder>Tulis di sini</span>
                                    </div>
                                    <input type="hidden" name="rsvp_signature" data-playground-signature-input>
                                    <input type="hidden" name="signature_drawn" value="0" data-playground-signature-drawn>
                                    <p class="playground-signature-help" data-playground-signature-help>Bubuhkan tanda tangan sebagai konfirmasi kehadiran.</p>
                                    <p class="playground-signature-error" data-playground-signature-error hidden>Mohon isi tanda tangan terlebih dahulu.</p>
                                </div>
                                <button class="playground-submit" type="submit">Simpan Konfirmasi</button>
                            </form>
                        @elseif (! $rsvpClosed)
                            <div class="playground-flash error">Data penerima belum tersedia untuk kategori ini.</div>
                        @endif
                    </div>
                </section>
            @endif

            @if ($showStudentQrCard)
                <section class="letter-section letter-reveal" id="letterStudentQr">
                    <div
                        class="student-qr-card"
                        data-student-qr-card
                        data-qr-payload="{{ $studentQrPayload }}"
                        data-student-name="{{ $participant->name }}"
                        data-student-nim="{{ $participant->nim }}"
                        data-student-program="{{ $participant->studyProgram?->name ?: ($participant->study_program ?: 'Program studi belum diisi') }}"
                        data-event-title="{{ $period->archive_title }}"
                        data-event-date="{{ $eventDateLabel }}"
                        data-unmul-logo="{{ asset('Unmul.png') }}">
                        <div class="student-qr-copy">
                            <h4>{{ $participant->name }}</h4>
                            <p>{{ $period->archive_title }}</p>
                            <div class="student-qr-person">
                                <span>{{ $participant->nim }} - {{ $participant->studyProgram?->name ?: ($participant->study_program ?: 'Program studi belum diisi') }}</span>
                                <span>{{ $eventDateLabel }}</span>
                            </div>
                        </div>
                        <div class="student-qr-panel">
                            <div class="student-qr-frame" data-qr-frame data-qr-state="loading">
                                <div class="ui-load-state" data-qr-loading aria-live="polite">
                                    <span class="ui-spinner" aria-hidden="true"></span>
                                    <span class="ui-load-label">Memuat QR...</span>
                                </div>
                                <p class="ui-load-state ui-load-state--error" data-qr-error hidden>
                                    QR belum tampil. <button type="button" class="ui-load-retry" data-qr-retry>Coba lagi</button>
                                </p>
                                <canvas
                                    class="student-qr-canvas"
                                    width="220"
                                    height="220"
                                    aria-label="QR buku tamu mahasiswa"
                                    data-qr-canvas
                                    hidden></canvas>
                            </div>
                            <span class="student-qr-code">Kode: {{ strtoupper(substr($participant->invitation_token, -8)) }}</span>
                            <button
                                class="student-qr-download"
                                type="button"
                                data-download-qr-card
                                data-file-name="{{ $studentQrFileName }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <path d="M7 10l5 5 5-5"></path>
                                    <path d="M12 15V3"></path>
                                </svg>
                                <span>Unduh PNG</span>
                            </button>
                        </div>
                    </div>
                </section>
            @endif

            <footer class="signature-block letter-reveal">
                <div class="signature-inner">
                    <p class="signature-date">{{ $signatureDate }}</p>
                    <p class="signature-role">Dekan Fakultas Teknik,</p>
                    <div class="signature-assets">
                        <img class="ttd" src="{{ asset('ttd.png') }}" alt="Tanda tangan dekan">
                        <img class="stamp" src="{{ asset('stempel.png') }}" alt="Stempel Fakultas Teknik">
                    </div>
                    <p class="signature-name">{{ $signerName }}</p>
                    <p class="signature-title">{{ $signerTitle }}</p>
                </div>
            </footer>
        </div>
    </article>
</div>
@endsection

@push('scripts')
    @if ($showStudentQrCard)
        <script src="{{ asset('vendor/qrcode/qrcode.min.js') }}" defer></script>
    @endif
    <script type="application/json" id="formal-invitation-boot">@json($formalInvitationBoot)</script>
    <script src="{{ asset('js/formal-invitation.js') }}?v=3" defer></script>
@endpush
