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
    <link rel="preload" href="{{ asset('css/formal-invitation.css') }}?v=2" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('css/formal-invitation.css') }}?v=2"></noscript>
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
                            <div class="student-qr-frame">
                                <canvas
                                    class="student-qr-canvas"
                                    width="220"
                                    height="220"
                                    aria-label="QR buku tamu mahasiswa"
                                    data-qr-canvas></canvas>
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
    <script>
        (function () {
            var stage = document.getElementById('formalPreviewStage');
            var cover = document.getElementById('formalCover');
            var openButton = document.getElementById('formalOpenButton');
            var revealItems = Array.prototype.slice.call(document.querySelectorAll('.letter-reveal'));
            var tutorial = document.getElementById('formalTutorial');
            var tutorialText = document.getElementById('formalTutorialText');
            var tutorialProgress = document.getElementById('formalTutorialProgress');
            var tutorialNext = document.getElementById('formalTutorialNext');
            var tutorialSkip = document.getElementById('formalTutorialSkip');
            var spotlight = document.getElementById('formalSpotlight');
            var tutorialCard = document.getElementById('formalTutorialCard');
            var tutorialIndex = 0;
            var tutorialFinalLabel = 'Mulai baca';
            var tutorialCloseTarget = null;
            var highlightedTarget = null;
            var tutorialTransitionTimer = null;
            var spotlightFrame = null;
            var initialHashTarget = window.location.hash
                ? document.getElementById(window.location.hash.slice(1))
                : null;
            var tutorialAudience = @js($recipientSalutation);
            var shouldShowStudentQrGuide = @json($showStudentQrCard && session('success'));

            function openingGreeting() {
                var hour = new Date().getHours();

                if (hour >= 4 && hour < 10) return 'Selamat pagi';
                if (hour >= 10 && hour < 15) return 'Selamat siang';
                if (hour >= 15 && hour < 18) return 'Selamat sore';

                return 'Selamat malam';
            }

            function openingTutorialText() {
                var audience = tutorialAudience ? ', ' + tutorialAudience : '';

                return openingGreeting() + audience + '. Terima kasih sudah membuka undangan ini. Saya bantu arahkan sebentar supaya bagian pentingnya mudah diikuti.';
            }

            var defaultTutorialSteps = [
                {
                    text: openingTutorialText(),
                    target: 'letterRecipientName'
                },
                {
                    text: @js("Mulai dari sini, {$tutorialGreeting} bisa cek waktu, tempat, dan alamat acara. Bagian ini yang paling penting untuk dicatat sebelum hadir."),
                    target: 'letterDetail'
                },
                {
                    text: @js("Berikutnya, {$tutorialGreeting} bisa melihat susunan acara. Bagian ini menunjukkan alur prosesi dari pembukaan sampai sesi penutup."),
                    target: 'letterAgenda'
                }
            ];

            @if ($canonicalCategory->requiresRsvp())
            defaultTutorialSteps.push({
                text: @js("Di bagian akhir, mohon isi konfirmasi kehadiran sesuai kondisi sebenarnya: {$confirmationOptionsText}."),
                target: 'letterRsvp'
            });
            @endif
            var tutorialSteps = defaultTutorialSteps;
            var studentQrGuideSteps = @json($studentQrGuideSteps);

            function lockCoverScroll() {
                if (cover && !stage.classList.contains('is-open')) {
                    document.documentElement.classList.add('formal-cover-locked');
                }
            }

            function unlockCoverScroll() {
                document.documentElement.classList.remove('formal-cover-locked');
            }

            function startFormalBackgroundVideo() {
                var video = stage ? stage.querySelector('.formal-bg-video') : null;
                var overlay = stage ? stage.querySelector('.formal-bg-overlay') : null;
                if (!video || video.dataset.started === '1') {
                    return;
                }
                video.dataset.started = '1';
                video.hidden = false;
                if (overlay) {
                    overlay.hidden = false;
                }
                var source = video.querySelector('source[data-src]');
                if (source && !source.getAttribute('src')) {
                    source.setAttribute('src', source.dataset.src || '');
                    video.load();
                }
                video.play().catch(function () {});
            }

            function openInvitation(options) {
                options = options || {};
                startFormalBackgroundVideo();
                stage.classList.add('is-open');
                cover.classList.add('is-hidden');
                unlockCoverScroll();

                window.setTimeout(function () {
                    revealItems.slice(0, 3).forEach(function (item) {
                        item.classList.add('is-visible');
                    });

                    if (options.target) {
                        options.target.classList.add('is-visible');
                        options.target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                        stage.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }

                    if (!options.skipTutorial) {
                        openTutorial();
                    }
                }, 180);
            }

            function clearHighlight() {
                if (highlightedTarget) {
                    highlightedTarget.classList.remove('is-playground-highlight');
                    highlightedTarget = null;
                }

                spotlight.classList.remove('is-visible');
            }

            function moveSpotlight(target) {
                window.cancelAnimationFrame(spotlightFrame);
                spotlightFrame = window.requestAnimationFrame(function () {
                    applySpotlight(target);
                });
            }

            function applySpotlight(target) {
                if (!target) {
                    clearHighlight();
                    return;
                }

                if (highlightedTarget && highlightedTarget !== target) {
                    highlightedTarget.classList.remove('is-playground-highlight');
                }

                highlightedTarget = target;
                highlightedTarget.classList.add('is-playground-highlight');

                var rect = target.getBoundingClientRect();
                var pad = 12;
                var compactViewport = window.innerWidth <= 900 || window.innerHeight <= 640;
                var cardRect = tutorialCard.getBoundingClientRect();
                var availableBottom = compactViewport
                    ? Math.max(120, cardRect.top - 14)
                    : window.innerHeight - 10;
                var maxSpotlightHeight = compactViewport
                    ? Math.max(220, availableBottom - Math.max(rect.top - pad, 10))
                    : Math.max(180, window.innerHeight * 0.56);
                var spotlightHeight = Math.min(rect.height + pad * 2, maxSpotlightHeight, window.innerHeight - 20);
                spotlight.style.top = Math.max(rect.top - pad, 10) + 'px';
                spotlight.style.left = Math.max(rect.left - pad, 10) + 'px';
                spotlight.style.width = Math.min(rect.width + pad * 2, window.innerWidth - 20) + 'px';
                spotlight.style.height = spotlightHeight + 'px';
                spotlight.classList.add('is-visible');
                positionTutorialCard(rect);
            }

            function positionTutorialCard(targetRect) {
                var gap = 18;
                var margin = 14;

                tutorialCard.style.top = '';
                tutorialCard.style.left = '';
                tutorialCard.style.right = '';
                tutorialCard.style.bottom = '';

                if (window.innerWidth <= 900 || window.innerHeight <= 640) {
                    return;
                }

                var cardRect = tutorialCard.getBoundingClientRect();
                var cardWidth = Math.min(cardRect.width || 700, window.innerWidth - margin * 2);
                var cardHeight = Math.min(cardRect.height || 280, window.innerHeight - margin * 2);
                var spaceRight = window.innerWidth - targetRect.right;
                var spaceLeft = targetRect.left;
                var placeRight = spaceRight >= cardWidth + gap || spaceRight >= spaceLeft;
                var left = placeRight
                    ? Math.min(targetRect.right + gap, window.innerWidth - cardWidth - margin)
                    : Math.max(margin, targetRect.left - cardWidth - gap);

                var targetCenter = targetRect.top + (Math.min(targetRect.height, window.innerHeight) / 2);
                var top = Math.max(margin, Math.min(targetCenter - cardHeight / 2, window.innerHeight - cardHeight - margin));

                tutorialCard.style.left = left + 'px';
                tutorialCard.style.top = top + 'px';
            }

            function scrollTargetForTutorial(target) {
                var rect = target.getBoundingClientRect();
                var absoluteTop = rect.top + window.scrollY;
                var visualOffset;

                if (window.innerWidth <= 900 || window.innerHeight <= 640) {
                    visualOffset = target.id === 'letterAgenda'
                        ? Math.max(26, window.innerHeight * 0.07)
                        : Math.max(76, window.innerHeight * 0.18);
                } else if (window.innerWidth <= 1024) {
                    visualOffset = Math.max(96, window.innerHeight * 0.18);
                } else {
                    visualOffset = Math.max(110, window.innerHeight * 0.16);
                }

                window.scrollTo({
                    top: Math.max(0, absoluteTop - visualOffset),
                    behavior: 'smooth'
                });
            }

            function showTutorialStep() {
                var step = tutorialSteps[tutorialIndex] || tutorialSteps[0];
                var target = step.target ? document.getElementById(step.target) : null;

                window.clearTimeout(tutorialTransitionTimer);
                tutorialCard.classList.add('is-changing');

                if (target) {
                    target.classList.add('is-visible');
                    scrollTargetForTutorial(target);
                } else {
                    clearHighlight();
                }

                tutorialTransitionTimer = window.setTimeout(function () {
                    tutorialText.textContent = step.text;
                    tutorialProgress.textContent = (tutorialIndex + 1) + ' / ' + tutorialSteps.length;
                    tutorialNext.textContent = tutorialIndex >= tutorialSteps.length - 1 ? tutorialFinalLabel : 'Lanjut';

                    if (target) {
                        moveSpotlight(target);
                        window.setTimeout(function () {
                            moveSpotlight(target);
                        }, 260);
                    }

                    tutorialCard.classList.remove('is-changing');
                }, 130);
            }

            function openTutorial(customSteps, options) {
                options = options || {};
                tutorialSteps = customSteps || defaultTutorialSteps;
                tutorialFinalLabel = options.finalLabel || 'Mulai baca';
                tutorialCloseTarget = options.closeTarget || null;
                tutorialIndex = 0;
                tutorial.classList.add('is-visible');
                showTutorialStep();
            }

            function closeTutorial() {
                window.clearTimeout(tutorialTransitionTimer);
                tutorial.classList.remove('is-visible');
                tutorialCard.classList.remove('is-changing');
                clearHighlight();
                var closeTarget = tutorialCloseTarget ? document.getElementById(tutorialCloseTarget) : null;
                if (closeTarget) {
                    closeTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    stage.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                tutorialCloseTarget = null;
            }

            if (openButton) {
                openButton.addEventListener('click', function () {
                    openInvitation();
                });
            }

            lockCoverScroll();

            if (initialHashTarget) {
                openInvitation({
                    skipTutorial: true,
                    target: initialHashTarget
                });
            }

            if ('IntersectionObserver' in window) {
                var revealObserver = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                        }
                    });
                }, {
                    rootMargin: '0px 0px -12% 0px',
                    threshold: 0.12
                });

                revealItems.forEach(function (item) {
                    revealObserver.observe(item);
                });
            } else {
                revealItems.forEach(function (item) {
                    item.classList.add('is-visible');
                });
            }

            tutorialNext.addEventListener('click', function () {
                if (tutorialIndex >= tutorialSteps.length - 1) {
                    closeTutorial();
                    return;
                }

                tutorialIndex += 1;
                showTutorialStep();
            });

            tutorialSkip.addEventListener('click', closeTutorial);

            window.addEventListener('resize', function () {
                if (tutorial.classList.contains('is-visible') && highlightedTarget) {
                    moveSpotlight(highlightedTarget);
                }
            });

            var scrollFrame;
            window.addEventListener('scroll', function () {
                if (tutorial.classList.contains('is-visible') && highlightedTarget) {
                    window.cancelAnimationFrame(scrollFrame);
                    scrollFrame = window.requestAnimationFrame(function () {
                        applySpotlight(highlightedTarget);
                    });
                }
            }, { passive: true });

            function loadCardImage(src) {
                return new Promise(function (resolve) {
                    if (!src) {
                        resolve(null);
                        return;
                    }

                    var image = new Image();
                    image.crossOrigin = 'anonymous';
                    image.onload = function () { resolve(image); };
                    image.onerror = function () { resolve(null); };
                    image.src = src;
                });
            }

            function roundedRect(context, x, y, width, height, radius) {
                context.beginPath();
                context.moveTo(x + radius, y);
                context.arcTo(x + width, y, x + width, y + height, radius);
                context.arcTo(x + width, y + height, x, y + height, radius);
                context.arcTo(x, y + height, x, y, radius);
                context.arcTo(x, y, x + width, y, radius);
                context.closePath();
            }

            function fitText(context, text, x, y, maxWidth, lineHeight, maxLines) {
                var words = String(text || '').split(/\s+/).filter(Boolean);
                var line = '';
                var lines = [];

                words.forEach(function (word) {
                    var testLine = line ? line + ' ' + word : word;
                    if (context.measureText(testLine).width > maxWidth && line) {
                        lines.push(line);
                        line = word;
                    } else {
                        line = testLine;
                    }
                });

                if (line) lines.push(line);
                lines.slice(0, maxLines).forEach(function (item, index) {
                    var output = item;
                    if (index === maxLines - 1 && lines.length > maxLines) {
                        while (context.measureText(output + '...').width > maxWidth && output.length > 0) {
                            output = output.slice(0, -1);
                        }
                        output += '...';
                    }
                    context.fillText(output, x, y + (index * lineHeight));
                });
            }

            function downloadCanvas(canvas, fileName) {
                var link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = fileName || 'qr-buku-tamu.png';
                document.body.appendChild(link);
                link.click();
                link.remove();
            }

            function openStudentQrGuide() {
                var target = document.getElementById('letterStudentQr');
                if (!target || !tutorial) return;

                target.classList.add('is-visible');

                if (!stage.classList.contains('is-open')) {
                    openInvitation({
                        skipTutorial: true,
                        target: target
                    });
                }

                window.setTimeout(function () {
                    openTutorial(studentQrGuideSteps, {
                        finalLabel: 'Selesai',
                        closeTarget: 'letterStudentQr'
                    });
                }, 520);
            }

            async function drawQrCard(card, qrCanvas, fileName) {
                var canvas = document.createElement('canvas');
                canvas.width = 900;
                canvas.height = 1280;
                var context = canvas.getContext('2d');
                var logo = await loadCardImage(card.dataset.unmulLogo);
                var qrImage = await loadCardImage(qrCanvas.toDataURL('image/png'));

                context.fillStyle = '#f8fafc';
                context.fillRect(0, 0, canvas.width, canvas.height);

                context.save();
                roundedRect(context, 54, 54, 792, 1172, 34);
                context.fillStyle = '#ffffff';
                context.fill();
                context.strokeStyle = '#e5e7eb';
                context.lineWidth = 2;
                context.stroke();
                context.restore();

                if (logo) {
                    context.save();
                    context.globalAlpha = 0.045;
                    context.translate(688, 1080);
                    context.rotate(-18 * Math.PI / 180);
                    context.drawImage(logo, -250, -250, 500, 500);
                    context.restore();
                }

                context.fillStyle = '#475467';
                context.font = '600 30px Manrope, Arial, sans-serif';
                fitText(context, card.dataset.eventTitle || '', 96, 142, 700, 42, 2);

                context.fillStyle = '#111827';
                context.font = '800 46px Manrope, Arial, sans-serif';
                fitText(context, card.dataset.studentName || '', 96, 290, 700, 54, 2);

                context.fillStyle = '#667085';
                context.font = '600 28px Manrope, Arial, sans-serif';
                fitText(context, (card.dataset.studentNim || '-') + ' - ' + (card.dataset.studentProgram || '-'), 96, 400, 700, 38, 2);

                context.fillStyle = '#9a3412';
                context.font = '700 25px Manrope, Arial, sans-serif';
                fitText(context, card.dataset.eventDate || '', 96, 484, 700, 32, 1);

                context.save();
                roundedRect(context, 106, 554, 688, 688, 30);
                context.fillStyle = '#ffffff';
                context.fill();
                context.strokeStyle = '#e5e7eb';
                context.lineWidth = 2;
                context.stroke();
                context.restore();

                if (qrImage) {
                    context.drawImage(qrImage, 146, 594, 608, 608);
                }

                downloadCanvas(canvas, fileName);
            }

            document.querySelectorAll('[data-student-qr-card]').forEach(function (card) {
                var qrCanvas = card.querySelector('[data-qr-canvas]');
                if (!qrCanvas || !window.QRCode) return;

                window.QRCode.toCanvas(qrCanvas, card.dataset.qrPayload || '', {
                    errorCorrectionLevel: 'M',
                    margin: 2,
                    scale: 8,
                    color: {
                        dark: '#111827',
                        light: '#ffffff'
                    }
                });

                var button = card.querySelector('[data-download-qr-card]');
                if (button) {
                    button.addEventListener('click', function () {
                        drawQrCard(card, qrCanvas, button.dataset.fileName || 'qr-buku-tamu.png');
                    });
                }
            });

            if (shouldShowStudentQrGuide) {
                window.setTimeout(openStudentQrGuide, 720);
            }

            document.querySelectorAll('.playground-rsvp-form').forEach(function (form) {
                var noteField = form.querySelector('[data-playground-note-field]');
                var noteLabel = form.querySelector('[data-playground-note-label]');
                var noteInput = form.querySelector('textarea[name="note"]');
                var delegateFields = form.querySelector('[data-playground-delegate-fields]');
                var delegateInputs = delegateFields
                    ? Array.prototype.slice.call(delegateFields.querySelectorAll('input'))
                    : [];
                var signatureField = form.querySelector('[data-playground-signature-field]');
                var signatureCanvas = signatureField ? signatureField.querySelector('[data-playground-signature-canvas]') : null;
                var signatureInput = signatureField ? signatureField.querySelector('[data-playground-signature-input]') : null;
                var signatureDrawnInput = signatureField ? signatureField.querySelector('[data-playground-signature-drawn]') : null;
                var signatureLabel = signatureField ? signatureField.querySelector('[data-playground-signature-label]') : null;
                var signatureHelp = signatureField ? signatureField.querySelector('[data-playground-signature-help]') : null;
                var signatureError = signatureField ? signatureField.querySelector('[data-playground-signature-error]') : null;
                var signatureClear = signatureField ? signatureField.querySelector('[data-playground-signature-clear]') : null;
                var signatureContext = null;
                var signatureMode = '';
                var hasSignature = false;
                var isDrawing = false;
                var lastPoint = null;

                function showConditionalField(field) {
                    if (!field) return;

                    window.clearTimeout(field._playgroundHideTimer);
                    field.hidden = false;
                    field.style.removeProperty('display');
                    field.setAttribute('aria-hidden', 'false');
                    field.offsetHeight;

                    window.requestAnimationFrame(function () {
                        field.classList.add('is-open');
                    });
                }

                function hideConditionalField(field) {
                    if (!field) return;

                    window.clearTimeout(field._playgroundHideTimer);
                    field.classList.remove('is-open');
                    field.setAttribute('aria-hidden', 'true');
                    field._playgroundHideTimer = window.setTimeout(function () {
                        if (!field.classList.contains('is-open')) {
                            field.hidden = true;
                        }
                    }, 420);
                }

                function clearSignature() {
                    if (!signatureCanvas || !signatureContext || !signatureInput || !signatureDrawnInput || !signatureField) return;

                    signatureContext.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
                    signatureInput.value = '';
                    signatureDrawnInput.value = '0';
                    hasSignature = false;
                    lastPoint = null;
                    signatureField.classList.remove('is-drawn');

                    if (signatureError) {
                        signatureError.hidden = true;
                    }
                }

                function resizeSignatureCanvas() {
                    if (!signatureCanvas) return;

                    var rect = signatureCanvas.getBoundingClientRect();
                    var ratio = window.devicePixelRatio || 1;
                    var width = Math.max(Math.round(rect.width), 320);
                    var height = Math.max(Math.round(rect.height), 140);
                    var previousSignature = hasSignature && signatureInput && signatureInput.value ? signatureInput.value : '';

                    signatureCanvas.width = Math.round(width * ratio);
                    signatureCanvas.height = Math.round(height * ratio);
                    signatureContext = signatureCanvas.getContext('2d');
                    signatureContext.setTransform(ratio, 0, 0, ratio, 0, 0);
                    signatureContext.lineCap = 'round';
                    signatureContext.lineJoin = 'round';
                    signatureContext.lineWidth = 2.4;
                    signatureContext.strokeStyle = '#111827';

                    if (previousSignature) {
                        var image = new Image();
                        image.onload = function () {
                            signatureContext.drawImage(image, 0, 0, width, height);
                        };
                        image.src = previousSignature;
                    }
                }

                function showSignatureField(mode) {
                    if (!signatureField) return;

                    if (mode !== signatureMode) {
                        clearSignature();
                        signatureMode = mode;
                    }

                    if (signatureLabel) {
                        signatureLabel.textContent = mode === 'represented' ? 'Paraf perwakilan' : 'Tanda tangan';
                    }

                    if (signatureHelp) {
                        signatureHelp.textContent = mode === 'represented'
                            ? 'Perwakilan membubuhkan paraf sebagai bukti konfirmasi.'
                            : 'Bubuhkan tanda tangan sebagai konfirmasi kehadiran.';
                    }

                    if (signatureError) {
                        signatureError.textContent = mode === 'represented'
                            ? 'Mohon isi paraf perwakilan terlebih dahulu.'
                            : 'Mohon isi tanda tangan terlebih dahulu.';
                        signatureError.hidden = true;
                    }

                    showConditionalField(signatureField);
                    window.requestAnimationFrame(resizeSignatureCanvas);
                }

                function hideSignatureField() {
                    if (!signatureField) return;

                    clearSignature();
                    signatureMode = '';
                    hideConditionalField(signatureField);
                }

                function getSignaturePoint(event) {
                    if (!signatureCanvas) return null;

                    var source = (event.touches && event.touches[0]) || (event.changedTouches && event.changedTouches[0]) || event;
                    var rect = signatureCanvas.getBoundingClientRect();

                    return {
                        x: source.clientX - rect.left,
                        y: source.clientY - rect.top
                    };
                }

                function updateSignatureValue() {
                    if (!signatureCanvas || !signatureInput || !signatureDrawnInput || !signatureField) return;

                    signatureInput.value = signatureCanvas.toDataURL('image/png');
                    signatureDrawnInput.value = '1';
                    signatureField.classList.add('is-drawn');
                    hasSignature = true;

                    if (signatureError) {
                        signatureError.hidden = true;
                    }
                }

                function startSignature(event) {
                    if (!signatureCanvas || !signatureContext || (signatureField && signatureField.hidden)) return;

                    event.preventDefault();
                    var point = getSignaturePoint(event);
                    if (!point) return;

                    isDrawing = true;
                    lastPoint = point;
                    signatureContext.beginPath();
                    signatureContext.moveTo(point.x, point.y);

                    if (event.pointerId !== undefined && signatureCanvas.setPointerCapture) {
                        signatureCanvas.setPointerCapture(event.pointerId);
                    }
                }

                function moveSignature(event) {
                    if (!isDrawing || !signatureContext) return;

                    event.preventDefault();
                    var point = getSignaturePoint(event);
                    if (!point) return;

                    signatureContext.lineTo(point.x, point.y);
                    signatureContext.stroke();
                    lastPoint = point;
                    updateSignatureValue();
                }

                function endSignature(event) {
                    if (!isDrawing || !signatureContext) return;

                    event.preventDefault();

                    if (!hasSignature && lastPoint) {
                        signatureContext.beginPath();
                        signatureContext.arc(lastPoint.x, lastPoint.y, 1.7, 0, Math.PI * 2);
                        signatureContext.fillStyle = '#111827';
                        signatureContext.fill();
                        updateSignatureValue();
                    }

                    isDrawing = false;
                    signatureContext.closePath();
                }

                if (signatureCanvas) {
                    resizeSignatureCanvas();
                    if (signatureClear) {
                        signatureClear.addEventListener('click', clearSignature);
                    }
                    window.addEventListener('resize', resizeSignatureCanvas);

                    if (window.PointerEvent) {
                        signatureCanvas.addEventListener('pointerdown', startSignature);
                        signatureCanvas.addEventListener('pointermove', moveSignature);
                        window.addEventListener('pointerup', endSignature);
                        window.addEventListener('pointercancel', endSignature);
                    } else {
                        signatureCanvas.addEventListener('mousedown', startSignature);
                        signatureCanvas.addEventListener('mousemove', moveSignature);
                        window.addEventListener('mouseup', endSignature);
                        signatureCanvas.addEventListener('touchstart', startSignature, { passive: false });
                        signatureCanvas.addEventListener('touchmove', moveSignature, { passive: false });
                        window.addEventListener('touchend', endSignature, { passive: false });
                        window.addEventListener('touchcancel', endSignature, { passive: false });
                    }
                }

                function syncNoteField() {
                    var checked = form.querySelector('input[name="attendance"]:checked');
                    var mode = checked ? checked.value : '';

                    if (!checked) {
                        hideConditionalField(noteField);
                        hideConditionalField(delegateFields);
                        hideSignatureField();
                        return;
                    }

                    if (noteField && noteInput) {
                        if (mode === 'declined') {
                            showConditionalField(noteField);
                        } else {
                            hideConditionalField(noteField);
                        }

                        noteInput.required = mode === 'declined';

                        if (mode === 'declined') {
                            if (noteLabel) noteLabel.textContent = 'Catatan berhalangan';
                            noteInput.placeholder = noteInput.dataset.declinedPlaceholder || 'Tuliskan alasan berhalangan hadir secara singkat.';
                        } else {
                            noteInput.value = '';
                        }
                    }

                    if (delegateFields) {
                        if (mode === 'represented') {
                            showConditionalField(delegateFields);
                        } else {
                            hideConditionalField(delegateFields);
                        }

                        delegateInputs.forEach(function (input) {
                            input.required = mode === 'represented';

                            if (mode !== 'represented') {
                                input.value = '';
                            }
                        });
                    }

                    if (mode === 'attending' || mode === 'represented') {
                        showSignatureField(mode);
                    } else {
                        hideSignatureField();
                    }
                }

                form.querySelectorAll('input[name="attendance"]').forEach(function (input) {
                    input.addEventListener('change', syncNoteField);
                });

                form.addEventListener('submit', function (event) {
                    var checked = form.querySelector('input[name="attendance"]:checked');
                    var mode = checked ? checked.value : '';
                    var needsSignature = mode === 'attending' || mode === 'represented';

                    if (!needsSignature || !signatureField || !signatureInput) return;

                    if (!hasSignature || signatureInput.value === '') {
                        event.preventDefault();
                        showSignatureField(mode);

                        if (signatureError) {
                            signatureError.hidden = false;
                        }

                        signatureField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });

                syncNoteField();
            });
        })();
    </script>
@endpush
