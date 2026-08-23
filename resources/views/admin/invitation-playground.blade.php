@php
    $guestName = $participant?->name
        ?? $recipient?->invitation_name
        ?? $category->recipient_label
        ?? 'Tamu Undangan';
    $recipientSalutation = trim((string) $recipient?->salutation);
    $tutorialGreeting = $recipientSalutation
        ?: ($participant ? ($category->recipient_label ?: 'Yudisiawan/Yudisiawati') : ($category->recipient_label ?: 'Tamu Undangan'));
    $invitationGreeting = $recipientSalutation
        ?: ($participant ? ($category->recipient_label ?: 'Yudisiawan/Yudisiawati') : ($category->recipient_label ?: 'Tamu Undangan'));
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
    $confirmationOptionsText = $participant ? 'hadir atau berhalangan' : 'hadir, berhalangan, atau diwakilkan';
    $rsvpClosed = $period->rsvpIsClosed();
    $rsvpDeadlineLabel = $period->rsvp_deadline?->locale('id')->translatedFormat('d F Y H:i');
    $rsvpRespondedAt = $participant?->rsvp_responded_at ?? $recipient?->responded_at;
    $playgroundReturnUrl = request()->getRequestUri().'#letterRsvp';
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
    <style>
        .playground-filter {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
        }

        .formal-preview-stage,
        .formal-preview-stage * {
            box-sizing: border-box;
        }

        .formal-preview-stage {
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            padding: 42px 20px;
            border-radius: 8px;
            background: #111827;
        }

        .formal-preview-stage:not(.is-open) {
            height: 100vh;
            height: 100dvh;
            min-height: 100vh;
            min-height: 100dvh;
            padding: 0;
            background: #fff;
        }

        .formal-bg-video,
        .formal-bg-overlay {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        .formal-bg-video {
            object-fit: cover;
        }

        .formal-bg-overlay {
            background: rgba(17, 24, 39, 0.58);
        }

        .formal-cover {
            position: relative;
            z-index: 3;
            display: grid;
            place-items: center;
            min-height: 100vh;
            min-height: 100dvh;
            padding: clamp(22px, 5vw, 48px);
            background: #fff;
            transition: opacity 420ms ease, transform 420ms ease, visibility 420ms ease;
        }

        .formal-cover.is-hidden {
            opacity: 0;
            transform: translateY(-20px) scale(0.98);
            visibility: hidden;
            pointer-events: none;
            min-height: 0;
            height: 0;
        }

        .formal-cover-panel {
            width: min(560px, 100%);
            max-height: calc(100vh - 44px);
            max-height: calc(100dvh - 44px);
            padding: clamp(24px, 5vw, 44px);
            overflow: hidden;
            border: 0;
            border-radius: 0;
            background: #fff;
            text-align: center;
        }

        .formal-cover-label {
            margin: 0 0 10px;
            color: #e85d04;
            font-size: 12px;
            font-weight: 850;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .formal-cover-logo {
            width: clamp(78px, 16vw, 96px);
            height: clamp(78px, 16vw, 96px);
            object-fit: contain;
            margin: clamp(14px, 3vh, 20px) auto;
        }

        .formal-cover-panel h2 {
            margin: 0;
            color: #111827;
            font-size: clamp(26px, 5vw, 44px);
            font-weight: 850;
            line-height: 1.12;
            letter-spacing: 0;
        }

        .formal-cover-panel p {
            margin: clamp(10px, 2vh, 14px) auto clamp(16px, 3vh, 24px);
            max-width: 420px;
            color: #667085;
            font-size: clamp(13px, 2.7vw, 15px);
            line-height: 1.65;
        }

        .formal-cover-guest-label {
            display: block;
            margin: 18px 0 4px;
            color: #98a2b3;
            font-size: 12px;
            font-weight: 850;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .formal-cover-guest {
            margin: 0 0 22px;
            color: #111827;
            font-size: clamp(15px, 3.4vw, 18px);
            font-weight: 850;
            line-height: 1.45;
        }

        .formal-open-btn {
            border: 0;
            border-radius: 999px;
            background: #e85d04;
            color: #fff;
            font-weight: 850;
            cursor: pointer;
            transition: transform 180ms ease, background 180ms ease;
        }

        .formal-open-btn {
            min-width: 190px;
            min-height: 48px;
            padding: 12px 22px;
        }

        .formal-open-btn:hover {
            background: #d9480f;
            transform: translateY(-1px);
        }

        .letter-sheet {
            position: relative;
            z-index: 1;
            overflow: hidden;
            width: min(980px, 100%);
            margin: 0 auto;
            padding: clamp(28px, 5vw, 56px);
            border: 1px solid #d7dce4;
            border-radius: 10px;
            background: #fff;
            color: #111827;
            font-family: Manrope, Arial, sans-serif;
            opacity: 0;
            transform: translateY(26px) scale(0.985);
            pointer-events: none;
            transition: opacity 560ms ease, transform 560ms ease;
        }

        .formal-preview-stage.is-open .letter-sheet {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .letter-content {
            position: relative;
            z-index: 1;
            min-width: 0;
        }

        .letter-head {
            display: grid;
            grid-template-columns: 82px minmax(0, 1fr);
            gap: 18px;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #e85d04;
        }

        .letter-logo {
            width: 82px;
            height: 82px;
            object-fit: contain;
        }

        .letter-title {
            margin: 0;
            color: #111827;
            font-size: clamp(25px, 4vw, 42px);
            font-weight: 850;
            line-height: 1.12;
            letter-spacing: 0;
        }

        .letter-subtitle {
            margin: 8px 0 0;
            color: #667085;
            font-size: 15px;
            line-height: 1.6;
        }

        .recipient-line {
            display: grid;
            gap: 4px;
            margin: 28px 0 24px;
            color: #344054;
            font-size: 15px;
            line-height: 1.65;
        }

        .recipient-line strong {
            color: #111827;
            font-size: 19px;
            overflow-wrap: anywhere;
        }

        .recipient-focus {
            display: inline-block;
            width: fit-content;
            max-width: 100%;
            border-radius: 8px;
            transition: background 180ms ease, outline-color 180ms ease;
        }

        .recipient-focus.is-playground-highlight {
            outline: 2px solid rgba(232, 93, 4, 0.72);
            outline-offset: 6px;
            background: #fff7ed;
        }

        .letter-intro {
            margin: 0 0 26px;
            color: #344054;
            font-size: 16px;
            line-height: 1.9;
            overflow-wrap: break-word;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0;
            margin: 24px 0 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .info-item {
            padding: 16px 18px;
            border-right: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            background: #fff;
        }

        .info-item:nth-child(2n) {
            border-right: 0;
        }

        .info-item:nth-last-child(-n + 2) {
            border-bottom: 0;
        }

        .info-label {
            display: block;
            margin-bottom: 4px;
            color: #e85d04;
            font-size: 11px;
            font-weight: 850;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .info-value {
            color: #111827;
            font-size: 15px;
            line-height: 1.55;
            overflow-wrap: break-word;
        }

        .letter-section {
            padding: 28px 0;
            border-top: 1px solid #e5e7eb;
            scroll-margin-top: 132px;
        }

        .letter-reveal {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity 520ms ease, transform 520ms ease;
        }

        .formal-preview-stage.is-open .letter-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .letter-reveal.is-playground-highlight,
        .info-grid.is-playground-highlight {
            outline: 2px solid rgba(232, 93, 4, 0.55);
            outline-offset: 8px;
            border-radius: 10px;
        }

        .letter-section h3 {
            margin: 0 0 14px;
            color: #111827;
            font-size: 18px;
            font-weight: 850;
            letter-spacing: 0;
        }

        .agenda-list,
        .notes-list {
            margin: 0 0 0 20px;
            padding: 0;
            color: #475467;
            font-size: 15px;
            line-height: 1.85;
            overflow-wrap: break-word;
        }

        .agenda-list {
            list-style-type: decimal;
        }

        .agenda-list li::marker,
        .notes-list li::marker {
            color: #e85d04;
            font-weight: 850;
        }

        .agenda-list ol {
            margin-top: 4px;
            margin-left: 18px;
            padding-left: 0;
            list-style-type: lower-alpha;
        }

        .map-panel {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.9fr);
            gap: 22px;
            align-items: stretch;
        }

        .map-copy {
            display: grid;
            align-content: center;
            gap: 10px;
            color: #475467;
            font-size: 15px;
            line-height: 1.75;
        }

        .map-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: fit-content;
            min-height: 40px;
            margin-top: 4px;
            padding: 9px 14px;
            border: 1px solid #e85d04;
            border-radius: 8px;
            background: #fff;
            color: #c2410c;
            font-size: 13px;
            font-weight: 850;
            line-height: 1.2;
            text-decoration: none;
        }

        .map-action:hover,
        .map-action:focus {
            background: #fff7ed;
            color: #9a3412;
            text-decoration: none;
        }

        .map-action svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
        }

        .map-frame {
            overflow: hidden;
            min-height: 220px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f8fafc;
        }

        .map-frame iframe {
            width: 100%;
            height: 100%;
            min-height: 220px;
            border: 0;
        }

        .rsvp-strip {
            display: grid;
            gap: 14px;
            padding: 0;
            border: 0;
            background: transparent;
        }

        .rsvp-strip p {
            margin: 0;
            color: #7c2d12;
            line-height: 1.7;
        }

        .playground-flash {
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #344054;
            font-size: 14px;
            line-height: 1.6;
        }

        .playground-flash.good {
            border-color: #fed7aa;
            background: #fff7ed;
            color: #9a3412;
        }

        .playground-flash.error {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .playground-rsvp-form {
            display: grid;
            gap: 14px;
        }

        .playground-rsvp-person {
            display: grid;
            gap: 4px;
            padding-bottom: 2px;
            color: #667085;
            font-size: 13px;
            line-height: 1.55;
        }

        .playground-rsvp-person strong {
            color: #111827;
            font-size: 15px;
            font-weight: 850;
            overflow-wrap: anywhere;
        }

        .playground-rsvp-person span {
            overflow-wrap: anywhere;
        }

        .playground-field {
            display: grid;
            gap: 8px;
        }

        .playground-field label {
            margin: 0;
            color: #667085;
            font-size: 11px;
            font-weight: 850;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .playground-note {
            width: 100%;
            min-height: 76px;
            resize: vertical;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #111827;
            line-height: 1.55;
            font-size: 14px;
        }

        .playground-input {
            width: 100%;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #111827;
            line-height: 1.55;
            font-size: 14px;
        }

        .playground-input:focus,
        .playground-note:focus {
            outline: 0;
            border-color: #e85d04;
        }

        [data-playground-note-field],
        [data-playground-delegate-fields] {
            overflow: hidden;
            opacity: 0;
            max-height: 0;
            transform: translateY(-6px);
            transition: opacity 220ms ease, max-height 260ms ease, transform 260ms ease;
        }

        [data-playground-note-field].is-open {
            opacity: 1;
            max-height: 190px;
            transform: translateY(0);
        }

        [data-playground-delegate-fields].is-open {
            opacity: 1;
            max-height: 130px;
            transform: translateY(0);
        }

        [data-playground-note-field][hidden],
        [data-playground-delegate-fields][hidden] {
            display: none !important;
        }

        .radio-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .radio-grid.two-options {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .radio-option {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 9px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            color: #344054;
            font-weight: 850;
            font-size: 13px;
            cursor: pointer;
        }

        .radio-option > span:last-child {
            min-width: 0;
        }

        .radio-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .radio-mark {
            width: 16px;
            height: 16px;
            border: 1px solid #d0d5dd;
            border-radius: 999px;
            background: #fff;
        }

        .radio-icon {
            display: inline-grid;
            place-items: center;
            width: 22px;
            height: 22px;
            color: #667085;
            transform: translateY(0) scale(1);
            transition: color 180ms ease, transform 180ms ease;
        }

        .radio-icon svg {
            width: 19px;
            height: 19px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
        }

        .radio-option:has(input:checked) {
            border-color: #e85d04;
            background: #fff7ed;
            color: #9a3412;
        }

        .radio-option:has(input:checked) .radio-mark {
            border-color: #e85d04;
            background: #e85d04;
            outline: 4px solid #fff;
            outline-offset: -5px;
        }

        .radio-option:has(input:checked) .radio-icon {
            color: #e85d04;
            transform: translateY(-1px) scale(1.06);
        }

        .radio-option:has(input:checked) .radio-icon svg {
            animation: radioIconPop 260ms ease both;
        }

        @keyframes radioIconPop {
            0% {
                transform: scale(0.82) rotate(-4deg);
                opacity: 0.45;
            }

            70% {
                transform: scale(1.12) rotate(2deg);
                opacity: 1;
            }

            100% {
                transform: scale(1) rotate(0);
                opacity: 1;
            }
        }

        .playground-delegate-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .playground-submit {
            width: fit-content;
            min-height: 40px;
            padding: 9px 16px;
            border: 0;
            border-radius: 8px;
            background: #e85d04;
            color: #fff;
            font-weight: 850;
            font-size: 13px;
            cursor: pointer;
        }

        .playground-success {
            display: grid;
            grid-template-columns: 34px minmax(0, 1fr);
            align-items: start;
            gap: 12px;
            padding: 0;
            color: #475467;
            font-size: 14px;
            line-height: 1.6;
        }

        .playground-success-icon {
            display: inline-grid;
            place-items: center;
            width: 30px;
            height: 30px;
            border: 1px solid #e85d04;
            border-radius: 999px;
            color: #9a3412;
            animation: statusIconIn 360ms ease both;
        }

        .playground-success-icon svg {
            width: 17px;
            height: 17px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2.2;
        }

        .playground-success-copy {
            display: grid;
            gap: 2px;
        }

        .playground-success-label {
            color: #111827;
            font-size: 14px;
            font-weight: 850;
        }

        .playground-success time {
            color: #667085;
        }

        @keyframes statusIconIn {
            0% {
                opacity: 0;
                transform: scale(0.76) rotate(-8deg);
            }

            70% {
                opacity: 1;
                transform: scale(1.08) rotate(2deg);
            }

            100% {
                opacity: 1;
                transform: scale(1) rotate(0);
            }
        }

        .signature-block {
            display: grid;
            justify-items: end;
            padding-top: 28px;
            color: #111827;
            text-align: left;
        }

        .signature-inner {
            width: min(430px, 100%);
        }

        .signature-date,
        .signature-role {
            margin: 0 0 6px;
            color: #344054;
            font-size: 15px;
        }

        .signature-assets {
            position: relative;
            min-height: 168px;
            display: grid;
            place-items: center;
            margin: 6px 0 10px;
        }

        .signature-assets .ttd {
            max-width: 360px;
            width: 100%;
            object-fit: contain;
        }

        .signature-assets .stamp {
            position: absolute;
            left: 18%;
            top: 50%;
            width: 112px;
            transform: translateY(-50%) rotate(-4deg);
        }

        .signature-name {
            margin: 0 0 4px;
            color: #111827;
            font-size: 17px;
            font-weight: 850;
            overflow-wrap: break-word;
        }

        .signature-title {
            margin: 0;
            color: #667085;
            font-size: 14px;
            line-height: 1.55;
        }

        .formal-tutorial {
            position: fixed;
            inset: 0;
            z-index: 9998;
            padding: 20px;
            background: transparent;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 220ms ease, visibility 220ms ease;
        }

        .formal-tutorial.is-visible {
            opacity: 1;
            visibility: visible;
            pointer-events: none;
        }

        .formal-spotlight {
            position: fixed;
            z-index: 9999;
            border: 2px solid rgba(232, 93, 4, 0.82);
            border-radius: 14px;
            outline: 9999px solid rgba(17, 24, 39, 0.58);
            opacity: 0;
            transform: scale(0.98);
            pointer-events: none;
            transition: opacity 180ms ease, transform 180ms ease, top 220ms ease, left 220ms ease, width 220ms ease, height 220ms ease;
        }

        .formal-spotlight.is-visible {
            opacity: 1;
            transform: scale(1);
        }

        .formal-tutorial-card {
            position: fixed;
            z-index: 10000;
            width: min(700px, 100%);
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 190px;
            gap: 22px;
            align-items: stretch;
            padding: 26px 24px 20px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            pointer-events: auto;
            animation: guideCardIn 240ms ease-out both;
            transition: top 280ms ease, left 280ms ease, right 280ms ease, bottom 280ms ease, opacity 180ms ease, transform 180ms ease;
            will-change: top, left, transform, opacity;
        }

        .formal-tutorial-card.is-changing {
            opacity: 0.72;
            transform: translateY(4px) scale(0.992);
        }

        .formal-tutorial-card::after {
            content: "";
            position: absolute;
            right: 176px;
            top: 56px;
            width: 22px;
            height: 22px;
            border-top: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            background: #fff;
            transform: rotate(45deg);
        }

        @keyframes guideCardIn {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.985);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .formal-tutorial-copy {
            position: relative;
            z-index: 1;
            min-width: 0;
        }

        .formal-tutorial-card h3 {
            margin: 0 0 10px;
            color: #111827;
            font-size: clamp(24px, 3vw, 32px);
            font-weight: 850;
            letter-spacing: 0;
        }

        .formal-tutorial-card p {
            min-height: 118px;
            margin: 0;
            color: #475467;
            font-size: 17px;
            line-height: 1.85;
            opacity: 1;
            transform: translateY(0);
            transition: opacity 170ms ease, transform 170ms ease;
        }

        .formal-tutorial-card.is-changing p {
            opacity: 0;
            transform: translateY(5px);
        }

        .formal-tutorial-guide {
            position: relative;
            z-index: 2;
            display: grid;
            place-items: end center;
            align-self: stretch;
            min-height: 220px;
            margin: -10px -8px -20px 0;
            overflow: hidden;
            pointer-events: none;
        }

        .formal-tutorial-guide::before {
            content: "";
            position: absolute;
            right: 10px;
            bottom: 20px;
            width: 138px;
            height: 138px;
            border-radius: 999px;
            background: #fff7ed;
            opacity: 0.9;
        }

        .formal-tutorial-guide img {
            position: relative;
            z-index: 1;
            width: min(215px, 30vw);
            max-height: 258px;
            object-fit: contain;
            transform: translateY(0);
        }

        .formal-tutorial-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 18px;
        }

        .formal-tutorial-progress {
            color: #667085;
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
        }

        .formal-tutorial-actions {
            display: flex;
            gap: 8px;
        }

        .formal-tutorial-actions button {
            min-height: 42px;
            padding: 9px 16px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #344054;
            font-weight: 850;
            cursor: pointer;
        }

        .formal-tutorial-actions .primary {
            border-color: #e85d04;
            background: #e85d04;
            color: #fff;
        }

        @media (max-width: 900px), (max-height: 640px) {
            .formal-preview-stage {
                padding: 18px 12px;
            }

            .formal-preview-stage:not(.is-open) {
                padding: 0;
            }

            .formal-cover {
                padding: 18px;
            }

            .formal-cover-panel {
                max-height: calc(100vh - 36px);
                max-height: calc(100dvh - 36px);
                padding: 18px 8px;
            }

            .letter-head,
            .map-panel {
                grid-template-columns: 1fr;
            }

            .letter-sheet {
                width: 100%;
                max-width: 720px;
                padding: clamp(22px, 5.5vw, 34px);
            }

            .letter-head {
                gap: 12px;
                padding-bottom: 16px;
            }

            .letter-title {
                font-size: clamp(24px, 6vw, 34px);
            }

            .letter-subtitle,
            .recipient-line,
            .info-value,
            .map-copy,
            .signature-date,
            .signature-role {
                font-size: 14px;
            }

            .recipient-line {
                margin: 24px 0 20px;
            }

            .recipient-line strong {
                font-size: 17px;
            }

            .letter-intro {
                font-size: 15px;
                line-height: 1.8;
            }

            .letter-section {
                padding: 24px 0;
            }

            .formal-tutorial-card {
                grid-template-columns: minmax(0, 1fr) clamp(145px, 22vw, 180px);
                gap: 18px;
                padding: 22px 20px 18px;
                width: min(680px, calc(100vw - 24px));
                left: 12px !important;
                right: 12px !important;
                bottom: 12px !important;
                top: auto !important;
                max-height: min(54vh, 420px);
                overflow: hidden;
            }

            .formal-tutorial-card::after {
                display: none;
            }

            .formal-tutorial-guide {
                min-height: 180px;
                margin: -8px -2px -18px 0;
                justify-items: end;
            }

            .formal-tutorial-guide::before {
                width: 126px;
                height: 126px;
                right: 0;
                bottom: 14px;
                transform: none;
            }

            .formal-tutorial-guide img {
                width: clamp(150px, 21vw, 178px);
                max-height: 214px;
            }

            .letter-logo {
                width: 72px;
                height: 72px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-item,
            .info-item:nth-child(2n),
            .info-item:nth-last-child(-n + 2) {
                border-right: 0;
                border-bottom: 1px solid #e5e7eb;
            }

            .info-item:last-child {
                border-bottom: 0;
            }

            .signature-block {
                justify-items: stretch;
            }
        }

        @media (max-width: 480px), (max-height: 620px) {
            .formal-cover-label,
            .formal-cover-guest-label {
                font-size: 11px;
            }

            .formal-cover-logo {
                width: clamp(64px, 18vw, 78px);
                height: clamp(64px, 18vw, 78px);
                margin: 12px auto;
            }

            .formal-cover-panel h2 {
                font-size: clamp(24px, 7vw, 32px);
            }

            .formal-cover-panel p {
                margin: 10px auto 14px;
                line-height: 1.55;
            }

            .formal-cover-guest {
                margin-bottom: 16px;
            }

            .formal-open-btn {
                min-height: 44px;
                min-width: 168px;
                padding: 10px 18px;
            }

            .formal-preview-stage.is-open {
                padding: 14px 8px;
            }

            .letter-sheet {
                padding: 24px;
                border-radius: 8px;
            }

            .letter-head {
                padding-bottom: 14px;
                border-bottom-width: 2px;
            }

            .letter-logo {
                width: 58px;
                height: 58px;
            }

            .letter-title {
                font-size: clamp(22px, 6.7vw, 28px);
                line-height: 1.14;
            }

            .letter-subtitle {
                margin-top: 6px;
                font-size: 13px;
                line-height: 1.5;
            }

            .recipient-line {
                gap: 3px;
                margin: 22px 0 18px;
                font-size: 13px;
                line-height: 1.55;
            }

            .recipient-line strong {
                font-size: 16px;
            }

            .letter-intro {
                margin-bottom: 22px;
                font-size: 14px;
                line-height: 1.75;
            }

            .info-grid {
                margin: 20px 0 8px;
                border-radius: 7px;
            }

            .info-item {
                padding: 13px 14px;
            }

            .info-label {
                font-size: 10px;
            }

            .info-value,
            .agenda-list,
            .notes-list,
            .map-copy,
            .signature-title {
                font-size: 13px;
            }

            .letter-section {
                padding: 22px 0;
                scroll-margin-top: 92px;
            }

            .letter-section h3 {
                font-size: 16px;
            }

            .agenda-list,
            .notes-list {
                margin-left: 18px;
                line-height: 1.75;
            }

            .agenda-list ol {
                margin-left: 14px;
            }

            .map-panel {
                gap: 14px;
            }

            .map-action {
                width: 100%;
            }

            .map-frame,
            .map-frame iframe {
                min-height: 180px;
            }

            .radio-grid {
                grid-template-columns: 1fr;
            }

            .playground-delegate-grid {
                grid-template-columns: 1fr;
            }

            .radio-option {
                min-height: 40px;
                font-size: 12px;
            }

            .playground-submit {
                width: 100%;
            }

            .signature-assets {
                min-height: 128px;
            }

            .signature-assets .stamp {
                left: 8%;
                width: 86px;
            }

            .signature-name {
                font-size: 15px;
            }
        }

        @media (max-width: 480px) {
            .formal-tutorial-card {
                grid-template-columns: minmax(114px, 136px) minmax(0, 1fr);
                align-items: center;
                gap: 14px;
                width: min(440px, calc(100vw - 24px));
                max-height: min(58vh, 430px);
                min-height: 0;
                padding: 16px 18px;
            }

            .formal-tutorial-copy {
                order: 2;
            }

            .formal-tutorial-card h3 {
                margin-bottom: 6px;
                font-size: clamp(22px, 6vw, 26px);
            }

            .formal-tutorial-card p {
                min-height: 0;
                font-size: 14px;
                line-height: 1.58;
            }

            .formal-tutorial-footer {
                gap: 8px;
                margin-top: 12px;
            }

            .formal-tutorial-progress {
                min-width: 36px;
            }

            .formal-tutorial-actions {
                flex: 1;
                justify-content: flex-end;
                min-width: 0;
            }

            .formal-tutorial-guide {
                position: relative;
                order: 1;
                right: auto;
                bottom: auto;
                align-self: center;
                width: auto;
                height: auto;
                min-height: 0;
                margin: 0;
                place-items: center;
            }

            .formal-tutorial-guide::before {
                right: 50%;
                bottom: auto;
                top: 34px;
                width: 112px;
                height: 112px;
                transform: translateX(50%);
            }

            .formal-tutorial-guide img {
                width: 132px;
                max-height: 178px;
            }

            .formal-tutorial-actions button {
                min-height: 38px;
                padding: 8px 12px;
            }
        }

        @media (max-width: 360px) {
            .formal-preview-stage.is-open {
                padding: 10px 6px;
            }

            .formal-tutorial-card {
                grid-template-columns: 94px minmax(0, 1fr);
                gap: 12px;
                padding: 15px;
            }

            .formal-tutorial-guide {
                width: auto;
                height: auto;
            }

            .formal-tutorial-guide::before {
                top: 28px;
                width: 86px;
                height: 86px;
            }

            .formal-tutorial-guide img {
                width: 102px;
                max-height: 148px;
            }

            .formal-tutorial-card h3 {
                font-size: 21px;
            }

            .formal-tutorial-card p {
                font-size: 13px;
            }

            .formal-tutorial-actions button {
                padding: 7px 10px;
            }

            .letter-sheet {
                padding: 20px 18px;
            }

            .letter-title {
                font-size: 21px;
            }

            .letter-intro {
                font-size: 13px;
            }
        }

        @media (max-height: 520px) {
            .formal-cover-panel {
                padding-top: 12px;
                padding-bottom: 12px;
            }

            .formal-cover-logo {
                width: 58px;
                height: 58px;
                margin: 8px auto;
            }

            .formal-cover-panel p {
                margin-bottom: 10px;
            }
        }

        @if ($standalone ?? false)
            html,
            body {
                width: 100%;
                min-height: 100%;
                margin: 0;
                background: #111827;
                color: #111827;
                font-family: Manrope, Arial, sans-serif;
                overflow-x: hidden;
            }

            html.formal-cover-locked,
            html.formal-cover-locked body {
                height: 100%;
                overflow: hidden;
                overscroll-behavior: none;
            }

            body {
                min-height: 100vh;
            }

            .formal-preview-stage {
                min-height: 100vh;
                padding: 0;
                border-radius: 0;
            }

            .formal-cover {
                min-height: 100vh;
                padding: 24px;
            }

            .formal-preview-stage.is-open {
                padding: 42px 20px;
            }

            @media (max-width: 900px), (max-height: 640px) {
                .formal-preview-stage.is-open {
                    padding: 18px 10px;
                }
            }
        @endif
    </style>
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
    <video class="formal-bg-video" autoplay muted loop playsinline preload="auto">
        <source src="{{ asset('video-back.mp4') }}" type="video/mp4">
    </video>
    <div class="formal-bg-overlay" aria-hidden="true"></div>

    <section class="formal-cover" id="formalCover">
        <div class="formal-cover-panel">
            <p class="formal-cover-label">Undangan</p>
            <h2>Yudisium Fakultas Teknik</h2>
            <img class="formal-cover-logo" src="{{ asset('Unmul.png') }}" alt="Logo Universitas Mulawarman">
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
                <img class="letter-logo" src="{{ asset('Unmul.png') }}" alt="Logo Universitas Mulawarman">
                <div>
                    <h1 class="letter-title">{{ $period->archive_title }}</h1>
                    <p class="letter-subtitle">Fakultas Teknik Universitas Mulawarman</p>
                </div>
            </header>

            <div class="recipient-line letter-reveal" id="letterRecipient">
                <span>Kepada Yth.</span>
                <strong class="recipient-focus" id="letterRecipientName">{{ $guestName }}</strong>
                @if ($recipient?->context_note)
                    <span>{{ $recipient->context_note }}</span>
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
                        @elseif (! $rsvpClosed && $recipient)
                            <form method="post" action="{{ route('rsvp.recipient') }}" class="playground-rsvp-form" id="playgroundRecipientRsvpForm">
                                @csrf
                                <input type="hidden" name="recipient_id" value="{{ $recipient->id }}">
                                <input type="hidden" name="token" value="{{ $recipient->token }}">
                                <input type="hidden" name="return_to" value="{{ $playgroundReturnUrl }}">
                                <div class="playground-rsvp-person">
                                    <strong>{{ $recipient->invitation_name }}</strong>
                                    <span>{{ $recipient->category?->title }}</span>
                                </div>
                                <div class="playground-field">
                                    <label>Status Kehadiran</label>
                                    <div class="radio-grid">
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
                                        <label class="radio-option">
                                            <input type="radio" name="attendance" value="represented" @checked(old('attendance', $recipient->rsvp_status) === 'represented') required>
                                            <span class="radio-mark" aria-hidden="true"></span>
                                            <span class="radio-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M17 8l4 4-4 4M21 12h-7"></path></svg>
                                            </span>
                                            <span>Diwakilkan</span>
                                        </label>
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
                                <button class="playground-submit" type="submit">Simpan Konfirmasi</button>
                            </form>
                        @else
                            <div class="playground-flash error">Data penerima belum tersedia untuk kategori ini.</div>
                        @endif
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
            var highlightedTarget = null;
            var tutorialTransitionTimer = null;
            var spotlightFrame = null;
            var initialHashTarget = window.location.hash
                ? document.getElementById(window.location.hash.slice(1))
                : null;
            var tutorialAudience = @js($recipientSalutation);

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

            var tutorialSteps = [
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
                },
                {
                    text: @js("Di bagian akhir, mohon isi konfirmasi kehadiran sesuai kondisi sebenarnya: {$confirmationOptionsText}."),
                    target: '{{ $category->requiresRsvp() ? 'letterRsvp' : 'letterNotes' }}'
                }
            ];

            function lockCoverScroll() {
                if (cover && !stage.classList.contains('is-open')) {
                    document.documentElement.classList.add('formal-cover-locked');
                }
            }

            function unlockCoverScroll() {
                document.documentElement.classList.remove('formal-cover-locked');
            }

            function openInvitation(options) {
                options = options || {};
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
                    tutorialNext.textContent = tutorialIndex >= tutorialSteps.length - 1 ? 'Mulai baca' : 'Lanjut';

                    if (target) {
                        moveSpotlight(target);
                        window.setTimeout(function () {
                            moveSpotlight(target);
                        }, 260);
                    }

                    tutorialCard.classList.remove('is-changing');
                }, 130);
            }

            function openTutorial() {
                tutorialIndex = 0;
                tutorial.classList.add('is-visible');
                showTutorialStep();
            }

            function closeTutorial() {
                window.clearTimeout(tutorialTransitionTimer);
                tutorial.classList.remove('is-visible');
                tutorialCard.classList.remove('is-changing');
                clearHighlight();
                stage.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

            document.querySelectorAll('.playground-rsvp-form').forEach(function (form) {
                var noteField = form.querySelector('[data-playground-note-field]');
                var noteLabel = form.querySelector('[data-playground-note-label]');
                var noteInput = form.querySelector('textarea[name="note"]');
                var delegateFields = form.querySelector('[data-playground-delegate-fields]');
                var delegateInputs = delegateFields
                    ? Array.prototype.slice.call(delegateFields.querySelectorAll('input'))
                    : [];

                function showConditionalField(field) {
                    if (!field) return;

                    window.clearTimeout(field._playgroundHideTimer);
                    field.hidden = false;
                    field.style.display = '';

                    window.requestAnimationFrame(function () {
                        field.classList.add('is-open');
                    });
                }

                function hideConditionalField(field) {
                    if (!field) return;

                    window.clearTimeout(field._playgroundHideTimer);
                    field.classList.remove('is-open');
                    field._playgroundHideTimer = window.setTimeout(function () {
                        if (!field.classList.contains('is-open')) {
                            field.hidden = true;
                            field.style.display = 'none';
                        }
                    }, 270);
                }

                function syncNoteField() {
                    var checked = form.querySelector('input[name="attendance"]:checked');
                    var mode = checked ? checked.value : '';

                    if (!checked) {
                        hideConditionalField(noteField);
                        hideConditionalField(delegateFields);
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
                }

                form.querySelectorAll('input[name="attendance"]').forEach(function (input) {
                    input.addEventListener('change', syncNoteField);
                });

                syncNoteField();
            });
        })();
    </script>
@endpush
