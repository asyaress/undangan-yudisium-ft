@php
    $step = $step ?? 'intro';
    $event = $event ?? null;
    $participant = $participant ?? null;
    $checkinStatus = $checkinStatus ?? ($event?->checkinStatus() ?? 'no_event');
    $radius = (int) ($radius ?? $event?->checkin_radius_meter ?? 300);
    $locationRequired = (bool) ($event?->checkin_location_required ?? true);
    $hasCoordinate = (bool) ($event?->hasCheckinCoordinate() ?? false);
    $eventTitle = $event?->archive_title ?? 'Yudisium Fakultas Teknik';
    $eventDate = $event?->event_date?->locale('id')->translatedFormat('d F Y') ?? 'Tanggal acara belum diatur';
    $eventLocation = $event?->location ?: 'Lokasi acara';
    $opensAt = $event?->checkin_opens_at?->locale('id')->translatedFormat('d F Y H:i');
    $closesAt = $event?->checkin_closes_at?->locale('id')->translatedFormat('d F Y H:i');
    $windowLabel = match (true) {
        $opensAt && $closesAt => $opensAt.' - '.$closesAt.' WITA',
        $opensAt => 'Mulai '.$opensAt.' WITA',
        $closesAt => 'Sampai '.$closesAt.' WITA',
        default => 'Mengikuti arahan panitia',
    };
    $blockedTitle = match ($checkinStatus) {
        'not_open' => 'Check-in Belum Dibuka',
        'closed' => 'Check-in Sudah Ditutup',
        'location_unset' => 'Lokasi Check-in Belum Diatur',
        'no_event' => 'Event Tidak Tersedia',
        default => 'Check-in Tidak Tersedia',
    };
    $blockedText = match ($checkinStatus) {
        'not_open' => $opensAt ? 'Check-in dibuka pada '.$opensAt.' WITA.' : 'Check-in belum dibuka oleh panitia.',
        'closed' => $closesAt ? 'Check-in ditutup pada '.$closesAt.' WITA.' : 'Check-in sudah ditutup oleh panitia.',
        'location_unset' => 'Koordinat lokasi belum lengkap. Silakan hubungi panitia sebelum membuka check-in.',
        'no_event' => 'Belum ada event yudisium aktif untuk check-in.',
        default => 'Silakan hubungi panitia.',
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Check-in Kehadiran Yudisium Fakultas Teknik Universitas Mulawarman">
    <title>Check-in Yudisium FT UNMUL</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f6f8;
            --surface: #ffffff;
            --text: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --soft: #f8fafc;
            --primary: #d9480f;
            --primary-dark: #d9450b;
            --good: #047857;
            --warn: #b45309;
            --bad: #b91c1c;
            --shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
            margin: 0;
        }

        body {
            font-family: "Manrope", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background: var(--bg);
            padding: 16px;
        }

        .shell {
            width: min(100%, 560px);
            min-height: calc(100dvh - 32px);
            margin: 0 auto;
            display: grid;
            align-content: center;
            gap: 14px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 4px 2px;
        }

        .brand-mark {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 8px;
            background: #fff;
            border: 1px solid var(--line);
        }

        .brand-mark img {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }

        .brand h1 {
            margin: 0;
            font-size: 1.42rem;
            line-height: 1.18;
            letter-spacing: 0;
            font-weight: 800;
        }

        .brand p {
            margin: 6px 0 0;
            color: var(--muted);
            line-height: 1.6;
            font-size: 0.92rem;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: var(--shadow);
            padding: 20px;
        }

        .card[hidden] {
            display: none;
        }

        .kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0 0 0 10px;
            border-left: 3px solid var(--primary);
            background: transparent;
            color: var(--primary);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        h2 {
            margin: 0 0 10px;
            font-size: 1.18rem;
            line-height: 1.3;
            font-weight: 800;
        }

        p {
            margin: 0;
        }

        .copy {
            color: var(--muted);
            line-height: 1.7;
            font-size: 0.94rem;
        }

        .meta {
            display: grid;
            gap: 8px;
            margin: 14px 0;
        }

        .meta-row,
        .person-row {
            display: grid;
            grid-template-columns: 112px minmax(0, 1fr);
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--line);
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.55;
        }

        .meta-row:last-child,
        .person-row:last-child {
            border-bottom: 0;
        }

        .meta-row strong,
        .person-row strong {
            color: var(--text);
            overflow-wrap: anywhere;
        }

        .step-list {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }

        .step-dot {
            height: 4px;
            border-radius: 0;
            background: #e5e7eb;
        }

        .step-dot.is-active {
            background: var(--primary);
        }

        .field {
            display: grid;
            gap: 8px;
            margin-top: 14px;
        }

        label {
            color: var(--text);
            font-size: 0.86rem;
            font-weight: 800;
        }

        input {
            width: 100%;
            min-height: 50px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: var(--text);
            font: inherit;
            padding: 13px 14px;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(217, 72, 15, 0.12);
        }

        .actions {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .btn {
            width: 100%;
            min-height: 50px;
            border: 0;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: none;
        }

        .btn:hover {
            background: var(--primary-dark);
            color: #fff;
        }

        .btn:disabled {
            opacity: 0.58;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn.secondary {
            background: #fff;
            color: var(--text);
            border: 1px solid var(--line);
            box-shadow: none;
        }

        .notice {
            border-radius: 8px;
            padding: 12px 14px;
            margin-top: 14px;
            background: var(--soft);
            border: 1px solid var(--line);
            color: var(--muted);
            line-height: 1.65;
            font-size: 0.9rem;
        }

        .notice.good {
            color: var(--good);
            background: rgba(4, 120, 87, 0.08);
            border-color: rgba(4, 120, 87, 0.16);
        }

        .notice.warn {
            color: var(--warn);
            background: rgba(180, 83, 9, 0.08);
            border-color: rgba(180, 83, 9, 0.16);
        }

        .notice.bad {
            color: var(--bad);
            background: rgba(185, 28, 28, 0.08);
            border-color: rgba(185, 28, 28, 0.16);
        }

        .status-chip {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            padding: 7px 10px;
            border-radius: 8px;
            color: var(--good);
            background: rgba(4, 120, 87, 0.08);
            border: 1px solid rgba(4, 120, 87, 0.14);
            font-size: 0.78rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .footer-note {
            text-align: center;
            color: var(--muted);
            font-size: 0.76rem;
            line-height: 1.6;
            padding: 2px 16px 8px;
        }

        @media (max-width: 390px) {
            body {
                padding: 10px;
            }

            .shell {
                min-height: calc(100dvh - 20px);
            }

            .card {
                padding: 16px;
                border-radius: 8px;
            }

            .meta-row,
            .person-row {
                grid-template-columns: 92px minmax(0, 1fr);
                font-size: 0.86rem;
            }
        }

        @media (max-width: 430px) {
            .brand {
                align-items: flex-start;
            }

            .brand h1 {
                font-size: 1.28rem;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="brand">
            <div class="brand-mark">
                <img src="{{ asset('Unmul.png') }}" alt="Logo Universitas Mulawarman">
            </div>
            <div>
                <h1>Check-in Yudisium</h1>
                <p>Fakultas Teknik Universitas Mulawarman</p>
            </div>
        </section>

        @if (! $event || $step === 'blocked')
            <section class="card">
                <span class="kicker">Status</span>
                <h2>{{ $blockedTitle }}</h2>
                <p class="copy">{{ $blockedText }}</p>
                @if ($event)
                    <div class="meta">
                        <div class="meta-row"><span>Event</span><strong>{{ $eventTitle }}</strong></div>
                        <div class="meta-row"><span>Jadwal</span><strong>{{ $windowLabel }}</strong></div>
                    </div>
                @endif
            </section>
        @else
            <section class="card" id="introCard" @if (! in_array($step, ['intro', 'nim'], true)) hidden @endif>
                <span class="kicker">Check-in</span>
                <h2>{{ $eventTitle }}</h2>
                <p class="copy">Silakan lakukan check-in setelah Anda tiba di lokasi acara.</p>
                <div class="meta">
                    <div class="meta-row"><span>Tanggal</span><strong>{{ $eventDate }}</strong></div>
                    <div class="meta-row"><span>Lokasi</span><strong>{{ $eventLocation }}</strong></div>
                    <div class="meta-row"><span>Waktu</span><strong>{{ $windowLabel }}</strong></div>
                </div>
                <div class="actions">
                    <button class="btn" type="button" id="startButton">Mulai Check-in</button>
                </div>
            </section>

            <section class="card" id="nimCard" @if ($step !== 'nim') hidden @endif>
                <div class="step-list" aria-hidden="true">
                    <span class="step-dot is-active"></span>
                    <span class="step-dot"></span>
                    <span class="step-dot"></span>
                    <span class="step-dot"></span>
                </div>
                <span class="kicker">Langkah 1</span>
                <h2>Masukkan NIM</h2>
                <p class="copy">Masukkan NIM Anda.</p>
                @if ($lookupError)
                    <div class="notice bad">{{ $lookupError }} Silakan periksa kembali atau hubungi panitia.</div>
                @endif
                <form method="post" action="{{ route('checkin.search') }}">
                    @csrf
                    <input type="hidden" name="event_id" value="{{ $event->id }}">
                    <div class="field">
                        <label for="nim">NIM Mahasiswa</label>
                        <input id="nim" name="nim" type="text" value="{{ old('nim', $lookupNim) }}" placeholder="2200000001" inputmode="numeric" autocomplete="off" required>
                    </div>
                    <div class="actions">
                        <button class="btn" type="submit">Verifikasi Data</button>
                        <button class="btn secondary" type="button" data-back-intro>Kembali</button>
                    </div>
                </form>
            </section>

            @if ($participant && in_array($step, ['identity', 'location'], true))
                <section class="card" id="identityCard" @if ($step === 'location') hidden @endif>
                    <div class="step-list" aria-hidden="true">
                        <span class="step-dot is-active"></span>
                        <span class="step-dot is-active"></span>
                        <span class="step-dot"></span>
                        <span class="step-dot"></span>
                    </div>
                    <span class="kicker">Langkah 2</span>
                    <h2>Verifikasi Data</h2>
                    <div class="person">
                        <div class="person-row"><span>Nama</span><strong>{{ $participant->name }}</strong></div>
                        <div class="person-row"><span>NIM</span><strong>{{ $participant->nim }}</strong></div>
                        <div class="person-row"><span>Prodi</span><strong>{{ $participant->studyProgram?->name ?: ($participant->study_program ?: '-') }}</strong></div>
                    </div>
                    @if ($participant->checked_in_at)
                        <div class="notice good">
                            Anda sudah melakukan check-in pada {{ $participant->checked_in_at?->locale('id')->translatedFormat('d F Y H:i') }} WITA.
                        </div>
                    @else
                        <p class="copy" style="margin-top: 12px;">Apakah ini data Anda?</p>
                        <div class="actions">
                            <button class="btn" type="button" id="confirmIdentity">Ya, ini data saya</button>
                            <a class="btn secondary" href="{{ route('checkin.form', ['slug' => $event->slug]) }}">Bukan saya</a>
                        </div>
                    @endif
                </section>

                @if (! $participant->checked_in_at)
                    <section class="card" id="locationCard" @if ($step !== 'location') hidden @endif>
                        <div class="step-list" aria-hidden="true">
                            <span class="step-dot is-active"></span>
                            <span class="step-dot is-active"></span>
                            <span class="step-dot is-active"></span>
                            <span class="step-dot"></span>
                        </div>
                        <span class="kicker">Langkah 3</span>
                        <h2>Verifikasi Lokasi</h2>
                        <p class="copy">Sistem perlu memeriksa apakah Anda sudah berada di area acara.</p>
                        @if ($locationError)
                            <div class="notice bad" id="locationStatus">{{ $locationError }}</div>
                        @else
                            <div class="notice" id="locationStatus">
                                Radius check-in: {{ number_format($radius) }} meter.
                            </div>
                        @endif
                        <form method="post" action="{{ route('checkin.confirm') }}" id="finalCheckinForm">
                            @csrf
                            <input type="hidden" name="event_id" value="{{ $event->id }}">
                            <input type="hidden" name="participant_id" value="{{ $participant->id }}">
                            <input type="hidden" name="nim" value="{{ $participant->nim }}">
                            <input type="hidden" name="latitude" id="latitudeInput">
                            <input type="hidden" name="longitude" id="longitudeInput">
                            <input type="hidden" name="accuracy" id="accuracyInput">
                            <div class="actions">
                                <button class="btn" type="button" id="locateButton">Izinkan Lokasi &amp; Cek Radius</button>
                                <button class="btn" type="submit" id="submitCheckinButton" hidden>Konfirmasi Saya Hadir di Lokasi</button>
                                <button class="btn secondary" type="button" id="retryLocationButton" hidden>Coba Lagi</button>
                            </div>
                        </form>
                    </section>
                @endif
            @endif

            @if ($participant && $step === 'manual_review')
                <section class="card">
                    <span class="kicker">Verifikasi</span>
                    <h2>Lokasi Perlu Diverifikasi</h2>
                    <p class="copy">Sistem mendeteksi lokasi Anda berada di luar area acara atau GPS kurang akurat. Data Anda sudah masuk ke daftar verifikasi panitia. Silakan menuju meja registrasi.</p>
                    <div class="meta">
                        <div class="meta-row"><span>Jarak</span><strong>{{ number_format($distance ?? 0) }} meter</strong></div>
                        <div class="meta-row"><span>Radius</span><strong>{{ number_format($radius) }} meter</strong></div>
                        @if ($accuracy)
                            <div class="meta-row"><span>Akurasi GPS</span><strong>{{ number_format($accuracy) }} meter</strong></div>
                        @endif
                    </div>
                    <div class="actions">
                        <a class="btn secondary" href="{{ route('checkin.form', ['slug' => $event->slug]) }}">Selesai</a>
                    </div>
                </section>
            @endif

            @if ($participant && $step === 'success')
                <section class="card">
                    <span class="status-chip">Check-in Berhasil</span>
                    <h2>{{ $alreadyCheckedIn ? 'Anda Sudah Check-in' : 'Check-in Berhasil' }}</h2>
                    <p class="copy">{{ $alreadyCheckedIn ? 'Data kehadiran Anda sudah tercatat sebelumnya.' : 'Kehadiran Anda telah tercatat.' }}</p>
                    <div class="meta">
                        <div class="meta-row"><span>Nama</span><strong>{{ $participant->name }}</strong></div>
                        <div class="meta-row"><span>NIM</span><strong>{{ $participant->nim }}</strong></div>
                        <div class="meta-row"><span>Prodi</span><strong>{{ $participant->studyProgram?->name ?: ($participant->study_program ?: '-') }}</strong></div>
                        <div class="meta-row"><span>Waktu</span><strong>{{ $participant->checked_in_at?->locale('id')->translatedFormat('d F Y H:i') }} WITA</strong></div>
                        @if ($distance !== null)
                            <div class="meta-row"><span>Jarak</span><strong>{{ number_format($distance) }} meter</strong></div>
                        @endif
                    </div>
                    <div class="actions">
                        <a class="btn secondary" href="{{ route('checkin.form', ['slug' => $event->slug]) }}">Selesai</a>
                    </div>
                </section>
            @endif
        @endif

        <p class="footer-note">Jika lokasi sulit terbaca, hubungi panitia di meja registrasi.</p>
    </main>

    <script>
        const introCard = document.getElementById("introCard");
        const nimCard = document.getElementById("nimCard");
        const startButton = document.getElementById("startButton");
        const backButtons = document.querySelectorAll("[data-back-intro]");
        const confirmIdentity = document.getElementById("confirmIdentity");
        const identityCard = document.getElementById("identityCard");
        const locationCard = document.getElementById("locationCard");
        const locateButton = document.getElementById("locateButton");
        const retryLocationButton = document.getElementById("retryLocationButton");
        const submitCheckinButton = document.getElementById("submitCheckinButton");
        const locationStatus = document.getElementById("locationStatus");
        const latitudeInput = document.getElementById("latitudeInput");
        const longitudeInput = document.getElementById("longitudeInput");
        const accuracyInput = document.getElementById("accuracyInput");
        const eventCoordinate = {
            enabled: @json($locationRequired && $hasCoordinate),
            latitude: @json($event?->checkin_latitude !== null ? (float) $event->checkin_latitude : null),
            longitude: @json($event?->checkin_longitude !== null ? (float) $event->checkin_longitude : null),
            radius: @json($radius),
            accuracyLimit: 500,
        };

        const showCard = (card) => {
            if (!card) return;
            card.hidden = false;
            card.scrollIntoView({ behavior: "smooth", block: "center" });
        };

        const hideCard = (card) => {
            if (card) card.hidden = true;
        };

        const setNotice = (type, text) => {
            if (!locationStatus) return;
            locationStatus.className = `notice ${type}`;
            locationStatus.textContent = text;
        };

        const distanceMeters = (latA, lngA, latB, lngB) => {
            const earth = 6371000;
            const toRad = (value) => value * Math.PI / 180;
            const dLat = toRad(latB - latA);
            const dLng = toRad(lngB - lngA);
            const a = Math.sin(dLat / 2) ** 2
                + Math.cos(toRad(latA)) * Math.cos(toRad(latB)) * Math.sin(dLng / 2) ** 2;
            return Math.round(earth * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
        };

        startButton?.addEventListener("click", () => {
            hideCard(introCard);
            showCard(nimCard);
            window.setTimeout(() => document.getElementById("nim")?.focus(), 220);
        });

        backButtons.forEach((button) => {
            button.addEventListener("click", () => {
                hideCard(nimCard);
                showCard(introCard);
            });
        });

        confirmIdentity?.addEventListener("click", () => {
            showCard(locationCard);
            if (!eventCoordinate.enabled) {
                setNotice("good", "Verifikasi lokasi tidak diwajibkan untuk event ini.");
                if (locateButton) locateButton.hidden = true;
                if (submitCheckinButton) {
                    submitCheckinButton.hidden = false;
                    submitCheckinButton.textContent = "Konfirmasi Saya Hadir di Lokasi";
                }
                return;
            }

            locationCard?.scrollIntoView({ behavior: "smooth", block: "center" });
        });

        const requestLocation = () => {
            const isLocalhost = ["localhost", "127.0.0.1", "::1"].includes(window.location.hostname);
            if (!window.isSecureContext && !isLocalhost) {
                setNotice("bad", "Akses lokasi membutuhkan HTTPS. Silakan buka link check-in resmi dengan https atau hubungi panitia.");
                return;
            }

            if (!navigator.geolocation) {
                setNotice("bad", "Perangkat ini belum mendukung akses lokasi. Silakan konfirmasi langsung ke panitia.");
                return;
            }

            if (submitCheckinButton) submitCheckinButton.hidden = true;
            if (retryLocationButton) retryLocationButton.hidden = true;
            if (locateButton) locateButton.disabled = true;
            setNotice("", "Membaca lokasi perangkat...");

            navigator.geolocation.getCurrentPosition((position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = Math.round(position.coords.accuracy || 0);
                const distance = distanceMeters(eventCoordinate.latitude, eventCoordinate.longitude, lat, lng);
                const inside = distance <= eventCoordinate.radius;
                const accurate = accuracy > 0 && accuracy <= eventCoordinate.accuracyLimit;

                latitudeInput.value = lat;
                longitudeInput.value = lng;
                accuracyInput.value = accuracy;

                if (inside && accurate) {
                    setNotice("good", `Anda berada di area acara. Jarak terdeteksi ${distance} meter, akurasi GPS ${accuracy} meter.`);
                    submitCheckinButton.textContent = "Konfirmasi Saya Hadir di Lokasi";
                } else {
                    const reason = inside ? "GPS kurang akurat" : "lokasi berada di luar area acara";
                    setNotice("warn", `Lokasi perlu diverifikasi karena ${reason}. Jarak terdeteksi ${distance} meter, akurasi GPS ${accuracy} meter.`);
                    submitCheckinButton.textContent = "Kirim Info ke Panitia";
                }

                if (submitCheckinButton) submitCheckinButton.hidden = false;
                if (retryLocationButton) retryLocationButton.hidden = false;
                if (locateButton) locateButton.disabled = false;
            }, (error) => {
                const denied = error.code === error.PERMISSION_DENIED;
                setNotice("bad", denied
                    ? "Izin lokasi ditolak. Aktifkan izin lokasi browser atau konfirmasi langsung ke panitia."
                    : "Lokasi belum berhasil terbaca. Coba lagi atau konfirmasi langsung ke panitia.");
                if (retryLocationButton) retryLocationButton.hidden = false;
                if (locateButton) locateButton.disabled = false;
            }, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0,
            });
        };

        locateButton?.addEventListener("click", requestLocation);
        retryLocationButton?.addEventListener("click", requestLocation);

        if (@json($step === 'nim')) {
            showCard(nimCard);
            hideCard(introCard);
        }
    </script>
</body>
</html>
