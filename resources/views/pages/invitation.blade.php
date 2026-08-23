@php
    $mode = $mode ?? 'archive';
    $participant = $participant ?? null;
    $recipient = $recipient ?? null;
    $selectedCategory = $selectedCategory ?? null;
    $activeEvent = $activeEvent ?? null;
    $events = $events ?? collect();
    $categories = $categories ?? collect();
    $isStudentCategory = $isStudentCategory ?? false;
    $isPublicCategory = $isPublicCategory ?? false;
    $isPrivateCategory = $isPrivateCategory ?? false;
    $requiresRsvp = $requiresRsvp ?? false;
    $rsvpClosed = $rsvpClosed ?? false;
    $rsvpDeadlineLabel = $rsvpDeadlineLabel ?? null;
    $lookupError = $lookupError ?? null;
    $studentIdentityConfirmed = $studentIdentityConfirmed ?? false;

    $recipientName = $participant?->name
        ?? $recipient?->name
        ?? $selectedCategory?->recipient_label
        ?? 'Tamu Undangan';

    $rsvpStatus = $participant?->rsvp_status ?? $recipient?->rsvp_status ?? 'pending';
    $rsvpRespondedAt = $participant?->rsvp_responded_at ?? $recipient?->responded_at;
    $isInvitationMode = $mode === 'invitation';
    $bodyClasses = match (true) {
        in_array($mode, ['archive', 'category-select'], true) => 'opened archive-mode',
        default => '',
    };
    $eventTitle = $activeEvent?->archive_title ?? 'Yudisium Fakultas Teknik';
    $eventProgram = $activeEvent?->cohort_label && $activeEvent?->period_label
        ? trim($activeEvent->cohort_label.' '.$activeEvent->period_label)
        : ($activeEvent?->name ?? 'Program Sarjana');
    $eventYear = $activeEvent?->event_year ?? now()->year;
    $eventDateLabel = $activeEvent?->event_date?->locale('id')->translatedFormat('l, d F Y') ?? 'Tanggal menunggu konfirmasi panitia';
    $eventDateShort = $activeEvent?->event_date?->locale('id')->translatedFormat('d F Y') ?? 'Menunggu jadwal resmi';
    $eventTimeLabel = $activeEvent?->event_time ?: '09.00 s/d Selesai';
    $eventLocation = $activeEvent?->location ?: 'Gedung utama Fakultas Teknik Universitas Mulawarman';
    $eventAddress = $activeEvent?->address ?: 'Jl. Sambaliung No.9, Gunung Kelua, Samarinda';
    $mapQuery = rawurlencode($eventLocation.' '.$eventAddress);
    $eventSubtitle = trim(($eventProgram !== '' ? $eventProgram : 'Program Sarjana').' Tahun '.$eventYear);
    $coverText = $selectedCategory?->cover_text ?: $eventSubtitle;
    $tutorialGreeting = $recipient?->salutation ?: 'Bapak/Ibu/Saudara';
    $invitationGreeting = $recipient?->salutation ?: 'Bapak/Ibu/Saudara(i)';
    $agendaItems = $activeEvent?->agenda_list ?? [];
    $eventNotes = $activeEvent?->event_note_list ?? [];
    $signatureCity = $activeEvent?->signature_city ?: 'Samarinda';
    $signatureDateLabel = str_contains($signatureCity, ',')
        ? $signatureCity
        : trim($signatureCity.', '.$eventDateShort, ', ');
    $signerName = $activeEvent?->signer_name ?: 'Prof. Dr. Ir. Thamrin, S.T., M.T, IPU, ASEAN. Eng., APEC. Eng';
    $signerTitle = $activeEvent?->signer_title ?: 'Dekan Fakultas Teknik Universitas Mulawarman';
    $showRsvpGuide = $requiresRsvp && $rsvpStatus === 'pending' && ! $rsvpClosed;
    $rsvpTutorialSteps = [];
    if ($showRsvpGuide) {
        if ($isStudentCategory) {
            $rsvpTutorialSteps = [
                'Undangan Yudisium Fakultas Teknik Universitas Mulawarman telah dibuka untuk Saudara/i.',
                'Silakan membaca detail undangan pada bagian ini, meliputi hari, tanggal, waktu, tempat, serta susunan acara.',
                'Setelah memahami informasi acara, Saudara/i dimohon melanjutkan ke tahap konfirmasi kehadiran pada bagian bawah halaman.',
                'Masukkan Nomor Induk Mahasiswa (NIM) yang terdaftar, lalu pilih status kehadiran Hadir atau Berhalangan.',
            ];
        } else {
            $rsvpTutorialSteps = [
                'Undangan resmi telah dibuka untuk '.$tutorialGreeting.'.',
                'Silakan membaca detail undangan pada bagian ini, meliputi informasi waktu, tempat, dan susunan acara.',
                'Setelah memahami isi undangan, '.$tutorialGreeting.' dimohon melanjutkan ke tahap konfirmasi kehadiran.',
                'Pada formulir konfirmasi kehadiran, pilih Bersedia Hadir atau Berhalangan Hadir sesuai ketersediaan '.$tutorialGreeting.'.',
            ];
        }
        if ($rsvpDeadlineLabel) {
            $rsvpTutorialSteps[3] .= ' Batas waktu konfirmasi kehadiran: '.$rsvpDeadlineLabel.' WITA.';
        }
    }
    $autoOpenInvitation = $isInvitationMode && (
        session()->has('success')
        || session()->has('error')
        || $errors->any()
        || old('nim')
    );
    if ($autoOpenInvitation) {
        $bodyClasses = trim($bodyClasses.' opened invitation-postback');
    }
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <meta name="description" content="{{ $pageTitle ?? 'Undangan Yudisium Fakultas Teknik Universitas Mulawarman' }}" />
  <title>{{ $pageTitle ?? 'Undangan Yudisium FT UNMUL' }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="preload" as="video" href="{{ asset('video-back.mp4') }}" type="video/mp4" />
  @include('pages.partials.invitation-styles')
</head>

<body class="{{ $bodyClasses }}">
  <div class="bg-video-layer" aria-hidden="true">
    <video class="bg-video" id="backgroundVideo" autoplay muted loop playsinline webkit-playsinline="true" preload="auto">
      <source src="{{ asset('video-back.mp4') }}" type="video/mp4" />
    </video>
  </div>
  <div class="bg-video-overlay" aria-hidden="true"></div>
  <div class="transition-layer" aria-hidden="true"></div>

  <main>
    @if ($isInvitationMode)
      @include('pages.partials.invitation-cover')
    @endif

    <section class="invitation {{ ! in_array($mode, ['archive', 'category-select'], true) ? 'invitation-layout' : '' }}" id="invitationContent">
      @if ($mode === 'archive')
        @include('pages.partials.invitation-archive')
      @elseif ($mode === 'category-select')
        @include('pages.partials.invitation-category-select')
      @else
        @include('pages.partials.invitation-body')
      @endif
    </section>
  </main>

  @if ($requiresRsvp && $isInvitationMode && ! $rsvpClosed && count($rsvpTutorialSteps) > 0)
    @include('pages.partials.rsvp-tutorial')
  @endif

  @include('pages.partials.invitation-scripts')
</body>

</html>
