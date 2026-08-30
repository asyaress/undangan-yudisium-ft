@extends('layouts.standalone-admin')

@section('title', 'Scan QR Check-in')

@section('content')
<div
    class="mobile-scanner"
    id="mobileScanner"
    data-scan-url="{{ route('admin.checkin.manual.scan') }}"
    data-live-url="{{ route('admin.checkin.manual.live', ['period_id' => $period?->id]) }}"
    data-period-id="{{ $period?->id }}"
    data-csrf="{{ csrf_token() }}">
    <section class="scan-hero">
        <img src="{{ asset('Unmul.png') }}" alt="Logo Universitas Mulawarman">
        <div>
            <span>Mode scanner HP</span>
            <h2>Check-in Mahasiswa</h2>
            <p>{{ $period?->name ?: 'Pilih event yudisium terlebih dahulu' }}</p>
        </div>
    </section>

    <form class="scanner-event" method="get" action="{{ route('admin.checkin.scanner.index') }}">
        <label for="period_id">Event</label>
        <select id="period_id" name="period_id" onchange="this.form.submit()">
            @foreach ($adminPeriods as $p)
                <option value="{{ $p->id }}" @selected($period?->id === $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
    </form>

    <section class="scan-card">
        @if ($adminPeriods->isEmpty() || ! $period)
            <div class="scan-alert is-error">Buat dan pilih event terlebih dahulu.</div>
        @else
            <div class="scan-guide">
                <div class="scan-guide-icon"><i class="fa fa-qrcode"></i></div>
                <div>
                    <strong>Arahkan kamera ke QR mahasiswa.</strong>
                    <span>Check-in otomatis tersimpan begitu QR terbaca. Jika kamera sulit membaca, pakai NIM manual di bawah.</span>
                </div>
            </div>

            <div class="camera-shell">
                <div id="qrReader" class="qr-reader"></div>
                <div class="scan-frame" aria-hidden="true"></div>
                <div class="camera-placeholder" id="cameraPlaceholder">
                    <i class="fa fa-qrcode"></i>
                    <strong>Kamera belum aktif</strong>
                    <span>Tekan mulai, lalu posisikan QR di tengah kotak kamera.</span>
                </div>
            </div>

            <div class="scanner-actions">
                <button class="scan-button primary" type="button" id="startCamera">
                    <i class="fa fa-camera"></i>
                    <span>Mulai Kamera</span>
                </button>
                <button class="scan-button ghost" type="button" id="stopCamera" disabled>
                    <i class="fa fa-pause"></i>
                    <span>Stop</span>
                </button>
            </div>

            <div class="scan-alert" id="scanStatus" role="status" aria-live="polite">
                Scanner siap digunakan.
            </div>

            <div class="manual-panel">
                <div class="manual-copy">
                    <strong>QR tidak terbaca?</strong>
                    <span>Masukkan NIM mahasiswa, lalu tekan check-in.</span>
                </div>
                <form class="quick-nim-form" id="quickNimForm">
                    <label for="quickNim">NIM mahasiswa</label>
                    <div class="quick-nim-row">
                        <input
                            id="quickNim"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            autocomplete="off"
                            placeholder="Masukkan NIM">
                        <button type="submit">Check-in</button>
                    </div>
                </form>
            </div>
        @endif
    </section>

    <section class="scan-result-card" id="resultCard" hidden>
        <span class="result-icon" id="resultIcon"><i class="fa fa-check"></i></span>
        <div>
            <h3 id="resultTitle">Check-in berhasil</h3>
            <p id="resultMessage">Data mahasiswa sudah tercatat.</p>
            <dl id="resultMeta"></dl>
        </div>
    </section>

    <section class="scan-summary">
        <div><span>Data</span><strong data-stat="total">{{ number_format($livePayload['summary']['total'] ?? 0) }}</strong></div>
        <div><span>RSVP Hadir</span><strong data-stat="attending">{{ number_format($livePayload['summary']['attending'] ?? 0) }}</strong></div>
        <div><span>Masuk</span><strong data-stat="checked_in">{{ number_format($livePayload['summary']['checked_in'] ?? 0) }}</strong></div>
        <div><span>Belum</span><strong data-stat="remaining">{{ number_format($livePayload['summary']['remaining'] ?? 0) }}</strong></div>
    </section>

    <section class="recent-mobile">
        <div class="recent-mobile-head">
            <h3>Terbaru</h3>
            <span id="lastSync">Realtime aktif</span>
        </div>
        <div id="recentMobileLogs"></div>
    </section>
</div>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/toastr/toastr.min.css') }}">
    <style>
        * {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
            background: #f3f4f6;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: max(16px, env(safe-area-inset-top)) max(12px, env(safe-area-inset-right)) max(24px, env(safe-area-inset-bottom)) max(12px, env(safe-area-inset-left));
            background: #f3f4f6;
            color: #111827;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .mobile-scanner {
            width: min(100%, 560px);
            margin: 0 auto 40px;
            color: #111827;
        }

        .scan-hero {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 16px;
        }

        .scan-hero img {
            width: 62px;
            height: 62px;
            object-fit: contain;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            padding: 8px;
        }

        .scan-hero span {
            display: block;
            margin-bottom: 4px;
            color: #e85d04;
            font-size: 11px;
            font-weight: 850;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .scan-hero h2 {
            margin: 0;
            color: #111827;
            font-size: clamp(24px, 6vw, 34px);
            font-weight: 850;
            letter-spacing: 0;
            line-height: 1.1;
        }

        .scan-hero p {
            margin: 6px 0 0;
            color: #667085;
            font-size: 14px;
            line-height: 1.4;
        }

        .scanner-event,
        .scan-card,
        .scan-result-card,
        .scan-summary,
        .recent-mobile {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.07);
        }

        .scanner-event {
            display: grid;
            gap: 8px;
            margin-bottom: 12px;
            padding: 12px;
        }

        .scanner-event label,
        .quick-nim-form label {
            margin: 0;
            color: #667085;
            font-size: 11px;
            font-weight: 850;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .scanner-event select,
        .quick-nim-row input {
            width: 100%;
            min-height: 46px;
            border: 1px solid #d0d5dd;
            border-radius: 10px;
            background: #fff;
            color: #111827;
            font-size: 16px;
            font-weight: 700;
            padding: 10px 12px;
        }

        .scanner-event select {
            padding-right: 38px;
        }

        .scanner-event select:focus,
        .quick-nim-row input:focus {
            border-color: #e85d04;
            box-shadow: 0 0 0 2px #e85d04;
            outline: 0;
        }

        .scan-card {
            padding: 14px;
        }

        .scan-guide {
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr);
            gap: 12px;
            align-items: center;
            margin-bottom: 12px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
        }

        .scan-guide-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #e85d04;
            color: #fff;
            font-size: 22px;
        }

        .scan-guide strong,
        .manual-copy strong {
            display: block;
            color: #111827;
            font-size: 15px;
            font-weight: 850;
            line-height: 1.35;
        }

        .scan-guide span,
        .manual-copy span {
            display: block;
            margin-top: 3px;
            color: #667085;
            font-size: 13px;
            line-height: 1.5;
        }

        .camera-shell {
            position: relative;
            aspect-ratio: 1 / 1;
            min-height: 300px;
            overflow: hidden;
            border: 1px solid #d0d5dd;
            border-radius: 12px;
            background: #111827;
        }

        .qr-reader,
        .qr-reader video,
        .qr-reader canvas {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
        }

        .qr-reader {
            position: absolute;
            inset: 0;
            z-index: 1;
        }

        .qr-reader > div:first-child {
            border: 0 !important;
        }

        .camera-placeholder {
            position: absolute;
            inset: 0;
            z-index: 2;
            display: grid;
            place-items: center;
            align-content: center;
            gap: 8px;
            padding: 22px;
            color: #fff;
            text-align: center;
            background: #111827;
            transition: opacity 180ms ease;
        }

        .camera-placeholder.is-hidden {
            opacity: 0;
            pointer-events: none;
        }

        .camera-placeholder i {
            color: #e85d04;
            font-size: 42px;
        }

        .camera-placeholder strong {
            font-size: 18px;
            font-weight: 850;
        }

        .camera-placeholder span {
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.55;
        }

        .scan-frame {
            position: absolute;
            inset: 18%;
            z-index: 3;
            pointer-events: none;
            border: 2px solid #e85d04;
            border-radius: 18px;
            opacity: 0;
            transition: opacity 180ms ease;
        }

        .camera-shell.is-running .scan-frame {
            opacity: 1;
        }

        .scanner-actions {
            display: grid;
            grid-template-columns: 1fr 0.65fr;
            gap: 10px;
            margin-top: 12px;
        }

        .scan-button,
        .quick-nim-row button {
            min-height: 50px;
            border: 0;
            border-radius: 10px;
            cursor: pointer;
            font: inherit;
            font-weight: 850;
        }

        .scan-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
        }

        .scan-button.primary,
        .quick-nim-row button {
            background: #e85d04;
            color: #fff;
        }

        .scan-button.primary:active,
        .quick-nim-row button:active {
            transform: translateY(1px);
        }

        .scan-button.ghost {
            border: 1px solid #d0d5dd;
            background: #fff;
            color: #344054;
        }

        .scan-button:disabled {
            cursor: not-allowed;
            border: 1px solid #d0d5dd;
            background: #f3f4f6;
            color: #98a2b3;
        }

        .scan-alert {
            margin-top: 12px;
            padding: 12px 14px;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #e85d04;
            border-radius: 10px;
            background: #fff;
            color: #111827;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.5;
        }

        .scan-alert.is-success {
            border-color: #e5e7eb;
            border-left-color: #059669;
            background: #fff;
            color: #047857;
        }

        .scan-alert.is-error {
            border-color: #e5e7eb;
            border-left-color: #dc2626;
            background: #fff;
            color: #991b1b;
        }

        .quick-nim-form {
            display: grid;
            gap: 8px;
            margin-top: 10px;
        }

        .manual-panel {
            margin-top: 14px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f9fafb;
        }

        .manual-copy {
            margin-bottom: 10px;
        }

        .quick-nim-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 112px;
            gap: 8px;
        }

        .scan-result-card {
            display: grid;
            grid-template-columns: 52px minmax(0, 1fr);
            gap: 12px;
            margin-top: 12px;
            padding: 16px;
            transform: translateY(8px);
            opacity: 0;
            transition: opacity 220ms ease, transform 220ms ease;
        }

        .scan-result-card.is-open {
            transform: translateY(0);
            opacity: 1;
        }

        .result-icon {
            display: grid;
            place-items: center;
            width: 52px;
            height: 52px;
            border-radius: 999px;
            border: 0;
            background: #059669;
            color: #fff;
            font-size: 24px;
        }

        .scan-result-card.is-warning .result-icon {
            background: #e85d04;
            color: #fff;
        }

        .scan-result-card.is-error .result-icon {
            background: #dc2626;
            color: #fff;
        }

        .scan-result-card h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 850;
        }

        .scan-result-card p {
            margin: 4px 0 8px;
            color: #667085;
            font-size: 14px;
            line-height: 1.45;
        }

        .scan-result-card dl {
            display: grid;
            gap: 6px;
            margin: 0;
        }

        .scan-result-card dl div {
            display: grid;
            grid-template-columns: 70px minmax(0, 1fr);
            gap: 8px;
            color: #667085;
            font-size: 13px;
        }

        .scan-result-card dt,
        .scan-result-card dd {
            margin: 0;
        }

        .scan-result-card dd {
            color: #111827;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .scan-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1px;
            overflow: hidden;
            margin-top: 12px;
            background: #e5e7eb;
        }

        .scan-summary div {
            display: grid;
            gap: 4px;
            padding: 13px 8px;
            background: #fff;
            text-align: center;
        }

        .scan-summary span {
            color: #667085;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .scan-summary strong {
            color: #111827;
            font-size: 20px;
            font-weight: 850;
        }

        .recent-mobile {
            margin-top: 12px;
            padding: 14px;
        }

        .recent-mobile-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .recent-mobile h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 850;
        }

        .recent-mobile-head span {
            color: #667085;
            font-size: 12px;
            font-weight: 700;
        }

        .mobile-log {
            display: grid;
            grid-template-columns: 54px minmax(0, 1fr);
            gap: 10px;
            padding: 11px 0;
            border-top: 1px solid #eef0f3;
        }

        .mobile-log:first-child {
            border-top: 0;
        }

        .mobile-log time {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 34px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #e5e7eb;
            color: #e85d04;
            font-size: 12px;
            font-weight: 850;
        }

        .mobile-log strong {
            display: block;
            color: #111827;
            font-size: 14px;
            font-weight: 850;
            line-height: 1.35;
        }

        .mobile-log span {
            display: block;
            color: #667085;
            font-size: 12px;
            line-height: 1.45;
        }

        .empty-mobile-log {
            padding: 12px 0 4px;
            color: #667085;
            font-size: 14px;
            text-align: center;
        }

        #toast-container.toast-top-center {
            top: max(12px, env(safe-area-inset-top));
            right: 12px;
            left: 12px;
            width: auto;
        }

        #toast-container.toast-top-center > div {
            width: min(100%, 420px);
            margin-right: auto;
            margin-left: auto;
            border-radius: 12px;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.16);
            opacity: 1;
        }

        @media (max-width: 420px) {
            body {
                padding-right: 10px;
                padding-left: 10px;
            }

            .mobile-scanner {
                width: 100%;
                margin-bottom: 20px;
            }

            .scan-hero {
                gap: 12px;
            }

            .scan-hero img {
                width: 52px;
                height: 52px;
            }

            .scan-hero h2 {
                font-size: 24px;
            }

            .scan-card {
                padding: 12px;
            }

            .scan-guide {
                grid-template-columns: 40px minmax(0, 1fr);
                padding: 10px;
            }

            .scan-guide-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }

            .camera-shell {
                min-height: 260px;
            }

            .quick-nim-row {
                grid-template-columns: 1fr;
            }

            .scanner-actions {
                grid-template-columns: 1fr 92px;
            }

            .scan-summary span {
                font-size: 10px;
            }

            .scan-summary strong {
                font-size: 18px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('assets-template/assets/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets-template/assets/vendor/toastr/toastr.js') }}"></script>
    <script src="{{ asset('vendor/html5-qrcode/html5-qrcode.min.js') }}"></script>
    <script>
        (function () {
            var app = document.getElementById('mobileScanner');
            if (!app) return;

            var readerId = 'qrReader';
            var startButton = document.getElementById('startCamera');
            var stopButton = document.getElementById('stopCamera');
            var cameraShell = document.querySelector('.camera-shell');
            var placeholder = document.getElementById('cameraPlaceholder');
            var statusBox = document.getElementById('scanStatus');
            var resultCard = document.getElementById('resultCard');
            var resultIcon = document.getElementById('resultIcon');
            var resultTitle = document.getElementById('resultTitle');
            var resultMessage = document.getElementById('resultMessage');
            var resultMeta = document.getElementById('resultMeta');
            var quickForm = document.getElementById('quickNimForm');
            var quickInput = document.getElementById('quickNim');
            var recentLogs = document.getElementById('recentMobileLogs');
            var lastSync = document.getElementById('lastSync');
            var numberFormat = new Intl.NumberFormat('id-ID');
            var livePayload = @json($livePayload);
            var scanner = null;
            var cameraRunning = false;
            var busy = false;
            var lastCode = '';
            var lastCodeAt = 0;

            if (window.toastr) {
                toastr.options = {
                    closeButton: false,
                    newestOnTop: true,
                    progressBar: false,
                    positionClass: 'toast-top-center',
                    preventDuplicates: true,
                    showDuration: 180,
                    hideDuration: 180,
                    timeOut: 3200,
                    extendedTimeOut: 900,
                    showMethod: 'fadeIn',
                    hideMethod: 'fadeOut'
                };
            }

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, function (char) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
                });
            }

            function setStatus(type, message) {
                if (!statusBox) return;

                statusBox.className = 'scan-alert' + (type ? ' is-' + type : '');
                statusBox.textContent = message;
            }

            function showToast(type, title, message) {
                if (!window.toastr) return;

                var variant = ['success', 'warning', 'error'].indexOf(type) >= 0 ? type : 'info';
                toastr[variant](escapeHtml(message || ''), escapeHtml(title || 'Informasi'));
            }

            function setResult(type, title, message, participant) {
                if (!resultCard) return;

                resultCard.hidden = false;
                resultCard.className = 'scan-result-card is-' + type;
                resultIcon.innerHTML = type === 'error'
                    ? '<i class="fa fa-times"></i>'
                    : (type === 'warning' ? '<i class="fa fa-exclamation"></i>' : '<i class="fa fa-check"></i>');
                resultTitle.textContent = title;
                resultMessage.textContent = message;
                resultMeta.innerHTML = participant
                    ? '<div><dt>Nama</dt><dd>' + escapeHtml(participant.name) + '</dd></div>' +
                        '<div><dt>NIM</dt><dd>' + escapeHtml(participant.nim) + '</dd></div>' +
                        '<div><dt>Prodi</dt><dd>' + escapeHtml(participant.program) + '</dd></div>'
                    : '';

                window.requestAnimationFrame(function () {
                    resultCard.classList.add('is-open');
                });
            }

            function renderSummary(summary) {
                Object.keys(summary || {}).forEach(function (key) {
                    var node = document.querySelector('[data-stat="' + key + '"]');
                    if (node) node.textContent = numberFormat.format(summary[key]);
                });
            }

            function renderLogs(logs) {
                logs = logs || [];
                if (!recentLogs) return;

                if (!logs.length) {
                    recentLogs.innerHTML = '<div class="empty-mobile-log">Belum ada aktivitas check-in.</div>';
                    return;
                }

                recentLogs.innerHTML = logs.slice(0, 8).map(function (log) {
                    return '<div class="mobile-log">' +
                        '<time>' + escapeHtml(log.time) + '</time>' +
                        '<div><strong>' + escapeHtml(log.name) + '</strong><span>' + escapeHtml(log.nim) + ' - ' + escapeHtml(log.program) + '</span><span>' + escapeHtml(log.status_label) + '</span></div>' +
                    '</div>';
                }).join('');
            }

            function render(payload) {
                livePayload = payload || livePayload || {};
                renderSummary(livePayload.summary || {});
                renderLogs(livePayload.logs || []);

                if (lastSync) {
                    lastSync.textContent = 'Sinkron ' + new Date().toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                    });
                }
            }

            async function submitCode(code, note) {
                code = String(code || '').trim();
                if (!code || busy) return;

                busy = true;
                setStatus('', 'Mencocokkan data mahasiswa...');

                try {
                    var response = await fetch(app.dataset.scanUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': app.dataset.csrf
                        },
                        body: JSON.stringify({
                            period_id: app.dataset.periodId,
                            scan_code: code,
                            manual_note: note || ''
                        })
                    });
                    var data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Check-in gagal diproses.');

                    if (data.ok) {
                        var isDuplicate = data.status === 'duplicate';
                        setStatus(isDuplicate ? '' : 'success', data.message);
                        showToast(
                            isDuplicate ? 'warning' : 'success',
                            isDuplicate ? 'Sudah check-in' : 'Check-in berhasil',
                            data.participant ? data.participant.name : data.message
                        );
                        setResult(
                            isDuplicate ? 'warning' : 'success',
                            isDuplicate ? 'Sudah check-in' : 'Check-in berhasil',
                            data.message,
                            data.participant
                        );

                        if (navigator.vibrate) navigator.vibrate(isDuplicate ? [80, 60, 80] : 100);
                    } else {
                        setStatus('error', data.message || 'Data tidak ditemukan.');
                        showToast('error', 'Data tidak ditemukan', data.message || 'QR atau NIM tidak cocok.');
                        setResult('error', 'Data tidak ditemukan', data.message || 'QR atau NIM tidak cocok.', null);
                        if (navigator.vibrate) navigator.vibrate([60, 50, 60]);
                    }

                    if (data.payload) render(data.payload);
                    if (quickInput) quickInput.value = '';
                } catch (error) {
                    setStatus('error', error.message || 'Check-in gagal diproses.');
                    showToast('error', 'Check-in gagal', error.message || 'Coba ulangi sekali lagi.');
                    setResult('error', 'Check-in gagal', error.message || 'Coba ulangi sekali lagi.', null);
                } finally {
                    busy = false;
                    if (cameraRunning && scanner && scanner.resume) {
                        window.setTimeout(function () {
                            try { scanner.resume(); } catch (error) {}
                        }, 900);
                    }
                }
            }

            function handleScanSuccess(decodedText) {
                var now = Date.now();
                var value = String(decodedText || '').trim();
                if (!value) return;
                if (value === lastCode && now - lastCodeAt < 2200) return;

                lastCode = value;
                lastCodeAt = now;

                if (scanner && scanner.pause) {
                    try { scanner.pause(true); } catch (error) {}
                }

                submitCode(value, 'Scan QR lewat kamera HP.');
            }

            async function startCamera() {
                if (cameraRunning || !startButton) return;

                if (!window.Html5Qrcode) {
                    setStatus('error', 'Scanner kamera tidak tersedia. Gunakan input NIM manual.');
                    showToast('error', 'Kamera tidak tersedia', 'Gunakan input NIM manual untuk check-in.');
                    return;
                }

                startButton.disabled = true;
                    setStatus('', 'Membuka kamera...');

                try {
                    scanner = scanner || new Html5Qrcode(readerId, { verbose: false });
                    var config = {
                        fps: 12,
                        qrbox: function (viewfinderWidth, viewfinderHeight) {
                            var size = Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.72);
                            return { width: size, height: size };
                        },
                        aspectRatio: 1
                    };

                    try {
                        await scanner.start({ facingMode: 'environment' }, config, handleScanSuccess);
                    } catch (firstError) {
                        var cameras = await Html5Qrcode.getCameras();
                        if (!cameras || !cameras.length) throw firstError;

                        var backCamera = cameras.find(function (camera) {
                            return /back|rear|environment|belakang/i.test(camera.label || '');
                        }) || cameras[cameras.length - 1];

                        await scanner.start(backCamera.id, config, handleScanSuccess);
                    }

                    cameraRunning = true;
                    cameraShell && cameraShell.classList.add('is-running');
                    placeholder && placeholder.classList.add('is-hidden');
                    stopButton.disabled = false;
                    setStatus('success', 'Kamera aktif. Posisikan QR di tengah layar.');
                } catch (error) {
                    setStatus('error', 'Kamera belum bisa dibuka. Izinkan akses kamera atau gunakan input NIM manual.');
                    showToast('error', 'Kamera belum terbuka', 'Izinkan akses kamera atau gunakan input NIM manual.');
                    startButton.disabled = false;
                    stopButton.disabled = true;
                }
            }

            async function stopCamera() {
                if (!scanner || !cameraRunning) return;

                try {
                    await scanner.stop();
                } catch (error) {}

                cameraRunning = false;
                cameraShell && cameraShell.classList.remove('is-running');
                placeholder && placeholder.classList.remove('is-hidden');
                startButton.disabled = false;
                stopButton.disabled = true;
                setStatus('', 'Kamera berhenti.');
            }

            async function refreshLive() {
                try {
                    var response = await fetch(app.dataset.liveUrl, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('Live failed');
                    render(await response.json());
                } catch (error) {
                    if (lastSync) lastSync.textContent = 'Menyambung ulang...';
                }
            }

            startButton && startButton.addEventListener('click', startCamera);
            stopButton && stopButton.addEventListener('click', stopCamera);

            if (quickForm) {
                quickForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    submitCode(quickInput ? quickInput.value : '', 'Input NIM di meja registrasi.');
                });
            }

            if (quickInput) {
                quickInput.addEventListener('input', function () {
                    quickInput.value = quickInput.value.replace(/\D/g, '');
                });
            }

            document.addEventListener('visibilitychange', function () {
                if (document.hidden) stopCamera();
            });

            render(livePayload);
            quickInput && quickInput.focus();
            window.setInterval(refreshLive, 4000);
        })();
    </script>
@endpush
