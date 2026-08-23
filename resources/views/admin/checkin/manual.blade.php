@extends('layouts.dashboard')

@section('title', 'Check-in Peserta')
@section('breadcrumb_parent', 'Operasional')
@section('breadcrumb_active', 'Check-in Peserta')

@section('content')
@include('layouts.partials.block-header')

<div
    class="checkin-console"
    id="checkinConsole"
    data-scan-url="{{ route('admin.checkin.manual.scan') }}"
    data-live-url="{{ route('admin.checkin.manual.live', ['period_id' => $period?->id]) }}"
    data-period-id="{{ $period?->id }}"
    data-csrf="{{ csrf_token() }}">
    <div class="checkin-topbar">
        <form class="event-switcher" method="get" action="{{ route('admin.checkin.manual.index') }}">
            <label for="period_id">Event</label>
            <select id="period_id" name="period_id" onchange="this.form.submit()">
                @foreach ($adminPeriods as $p)
                    <option value="{{ $p->id }}" @selected($period?->id === $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </form>
        <div class="live-chip" aria-live="polite">
            <span></span>
            <strong id="lastSync">Realtime aktif</strong>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card"><span>Total Peserta</span><strong data-stat="total">{{ number_format($livePayload['summary']['total'] ?? 0) }}</strong></div>
        <div class="stat-card"><span>Konfirmasi Hadir</span><strong data-stat="attending">{{ number_format($livePayload['summary']['attending'] ?? 0) }}</strong></div>
        <div class="stat-card"><span>Sudah Check-in</span><strong data-stat="checked_in">{{ number_format($livePayload['summary']['checked_in'] ?? 0) }}</strong></div>
        <div class="stat-card"><span>Belum Check-in</span><strong data-stat="remaining">{{ number_format($livePayload['summary']['remaining'] ?? 0) }}</strong></div>
    </div>

    <div class="console-grid">
        <section class="scan-panel">
            <div class="scan-heading">
                <span class="scan-icon"><i class="fa fa-qrcode"></i></span>
                <div>
                    <h3>Scanner Registrasi</h3>
                    <p>Scan QR kartu konfirmasi atau ketik NIM mahasiswa, lalu tekan Enter.</p>
                </div>
            </div>

            @if ($adminPeriods->isEmpty() || ! $period)
                <div class="scan-result is-error">Buat dan pilih event terlebih dahulu.</div>
            @else
                <form class="scan-form" id="scanForm">
                    <input
                        class="scan-input"
                        id="scanCode"
                        name="scan_code"
                        type="text"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="Scan QR atau ketik NIM"
                        autofocus>
                    <textarea
                        class="scan-note"
                        id="manualNote"
                        name="manual_note"
                        rows="2"
                        placeholder="Catatan opsional untuk check-in manual"></textarea>
                    <button class="scan-submit" type="submit">
                        <i class="fa fa-check"></i>
                        <span>Proses Check-in</span>
                    </button>
                </form>
                <div class="scan-result" id="scanResult">
                    Input scanner sudah aktif. Arahkan scanner ke kartu QR mahasiswa.
                </div>
            @endif
        </section>

        <section class="recent-panel">
            <div class="panel-head">
                <h3>Check-in Terbaru</h3>
                <span id="logCount">{{ count($livePayload['logs'] ?? []) }} log</span>
            </div>
            <div class="recent-list" id="recentLogs"></div>
        </section>
    </div>

    <section class="participant-panel">
        <div class="panel-head">
            <h3>Data Mahasiswa Realtime</h3>
            <div class="participant-tools">
                <input id="participantFilter" type="search" placeholder="Cari nama atau NIM">
            </div>
        </div>
        <div class="participant-list" id="participantList"></div>
    </section>
</div>
@endsection

@push('head')
    <style>
        .checkin-console {
            display: grid;
            gap: 16px;
        }

        .checkin-topbar,
        .event-switcher,
        .live-chip,
        .scan-heading,
        .panel-head,
        .participant-tools {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .checkin-topbar {
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .event-switcher label {
            margin: 0;
            color: #6b7280;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .event-switcher select,
        .participant-tools input {
            min-height: 40px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
            color: #111827;
            padding: 8px 12px;
            font-weight: 700;
        }

        .live-chip {
            color: #047857;
            font-size: 13px;
            font-weight: 800;
        }

        .live-chip span {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: #10b981;
            box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.12);
            animation: livePulse 1.5s ease-in-out infinite;
        }

        @keyframes livePulse {
            50% { transform: scale(0.78); opacity: 0.58; }
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .stat-card,
        .scan-panel,
        .recent-panel,
        .participant-panel {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .stat-card {
            padding: 16px;
        }

        .stat-card span {
            display: block;
            color: #6b7280;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .stat-card strong {
            display: block;
            margin-top: 8px;
            color: #111827;
            font-size: 32px;
            line-height: 1;
        }

        .console-grid {
            display: grid;
            grid-template-columns: minmax(320px, 0.9fr) minmax(0, 1.1fr);
            gap: 16px;
            align-items: stretch;
        }

        .scan-panel,
        .recent-panel,
        .participant-panel {
            overflow: hidden;
        }

        .scan-panel {
            padding: 18px;
        }

        .scan-heading {
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .scan-icon {
            display: inline-grid;
            place-items: center;
            width: 46px;
            height: 46px;
            border-radius: 10px;
            background: #fff7ed;
            color: #d9480f;
            font-size: 22px;
        }

        .scan-heading h3,
        .panel-head h3 {
            margin: 0;
            color: #111827;
            font-size: 18px;
            font-weight: 850;
        }

        .scan-heading p {
            margin: 5px 0 0;
            color: #6b7280;
            line-height: 1.6;
        }

        .scan-form {
            display: grid;
            gap: 10px;
        }

        .scan-input {
            width: 100%;
            min-height: 64px;
            border: 2px solid #d9480f;
            border-radius: 10px;
            padding: 12px 16px;
            color: #111827;
            font-size: 22px;
            font-weight: 850;
            letter-spacing: 0;
        }

        .scan-input:focus,
        .scan-note:focus,
        .participant-tools input:focus,
        .event-switcher select:focus {
            outline: 0;
            border-color: #d9480f;
            box-shadow: 0 0 0 4px rgba(217, 72, 15, 0.12);
        }

        .scan-note {
            width: 100%;
            resize: vertical;
            min-height: 58px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
            color: #111827;
        }

        .scan-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            border: 0;
            border-radius: 10px;
            background: #d9480f;
            color: #fff;
            font-weight: 850;
            cursor: pointer;
        }

        .scan-submit:disabled {
            opacity: 0.68;
            cursor: wait;
        }

        .scan-result {
            margin-top: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
            background: #f8fafc;
            color: #475467;
            line-height: 1.55;
        }

        .scan-result strong {
            color: #111827;
        }

        .scan-result.is-success {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .scan-result.is-warning {
            border-color: #fed7aa;
            background: #fff7ed;
            color: #9a3412;
        }

        .scan-result.is-error {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .panel-head {
            justify-content: space-between;
            padding: 14px 16px;
            border-bottom: 1px solid #eef0f4;
            background: #fafafa;
        }

        .panel-head span {
            color: #6b7280;
            font-size: 13px;
            font-weight: 800;
        }

        .recent-list,
        .participant-list {
            display: grid;
            gap: 8px;
            padding: 12px;
            max-height: 470px;
            overflow: auto;
        }

        .log-row,
        .student-row {
            display: grid;
            gap: 8px;
            border: 1px solid #edf0f4;
            border-radius: 8px;
            background: #fff;
            padding: 10px 12px;
        }

        .log-row {
            grid-template-columns: 70px minmax(0, 1fr) auto;
            align-items: center;
        }

        .log-time {
            color: #6b7280;
            font-weight: 800;
        }

        .row-person strong {
            display: block;
            color: #111827;
            font-weight: 850;
        }

        .row-person small {
            color: #6b7280;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            border-radius: 999px;
            padding: 5px 9px;
            background: #f3f4f6;
            color: #4b5563;
            font-size: 12px;
            font-weight: 850;
            white-space: nowrap;
        }

        .status-pill.ok {
            background: #ecfdf5;
            color: #047857;
        }

        .status-pill.warn {
            background: #fff7ed;
            color: #c2410c;
        }

        .status-pill.bad {
            background: #fef2f2;
            color: #b91c1c;
        }

        .student-section {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .student-section-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-bottom: 1px solid #eef0f4;
            background: #fafafa;
        }

        .student-section-head h4 {
            margin: 0;
            color: #111827;
            font-size: 15px;
            font-weight: 850;
        }

        .student-section-head span {
            color: #6b7280;
            font-size: 12px;
            font-weight: 800;
        }

        .student-section-body {
            display: grid;
            gap: 8px;
            padding: 10px;
        }

        .student-row {
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr) auto;
            align-items: center;
        }

        .student-row.is-checked {
            border-color: #bbf7d0;
            background: #fbfefc;
        }

        .empty-state {
            padding: 22px;
            text-align: center;
            color: #6b7280;
        }

        @media (max-width: 992px) {
            .console-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .checkin-topbar,
            .event-switcher,
            .panel-head,
            .student-section-head {
                align-items: stretch;
                flex-direction: column;
            }

            .event-switcher select,
            .participant-tools,
            .participant-tools input {
                width: 100%;
            }

            .log-row,
            .student-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            var app = document.getElementById('checkinConsole');
            if (!app) return;

            var scanForm = document.getElementById('scanForm');
            var scanInput = document.getElementById('scanCode');
            var manualNote = document.getElementById('manualNote');
            var scanResult = document.getElementById('scanResult');
            var recentLogs = document.getElementById('recentLogs');
            var participantList = document.getElementById('participantList');
            var participantFilter = document.getElementById('participantFilter');
            var lastSync = document.getElementById('lastSync');
            var logCount = document.getElementById('logCount');
            var numberFormat = new Intl.NumberFormat('id-ID');
            var livePayload = @json($livePayload);
            var filterText = '';
            var busy = false;

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, function (char) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
                });
            }

            function statusClass(status) {
                if (status === 'accepted' || status === 'attending') return 'ok';
                if (status === 'duplicate' || status === 'pending') return 'warn';
                return 'bad';
            }

            function checkinClass(row) {
                return row.checked_in ? 'ok' : 'warn';
            }

            function focusScanner() {
                if (scanInput && document.activeElement !== participantFilter) {
                    scanInput.focus();
                }
            }

            function setResult(type, message, participant) {
                if (!scanResult) return;

                scanResult.className = 'scan-result is-' + type;
                scanResult.innerHTML = participant
                    ? '<strong>' + escapeHtml(participant.name) + '</strong><br>' +
                        escapeHtml(participant.nim) + ' - ' + escapeHtml(participant.program) + '<br>' +
                        '<span>' + escapeHtml(message) + '</span>'
                    : escapeHtml(message);
            }

            function renderSummary(summary) {
                Object.keys(summary || {}).forEach(function (key) {
                    var node = document.querySelector('[data-stat="' + key + '"]');
                    if (node) node.textContent = numberFormat.format(summary[key]);
                });
            }

            function renderLogs(logs) {
                logs = logs || [];
                if (logCount) logCount.textContent = numberFormat.format(logs.length) + ' log';
                if (!recentLogs) return;
                if (!logs.length) {
                    recentLogs.innerHTML = '<div class="empty-state">Belum ada aktivitas check-in.</div>';
                    return;
                }

                recentLogs.innerHTML = logs.slice(0, 30).map(function (log) {
                    return '<div class="log-row">' +
                        '<div class="log-time">' + escapeHtml(log.time) + '</div>' +
                        '<div class="row-person"><strong>' + escapeHtml(log.name) + '</strong><small>' + escapeHtml(log.nim) + ' - ' + escapeHtml(log.program) + '</small></div>' +
                        '<span class="status-pill ' + statusClass(log.status) + '">' + escapeHtml(log.status_label) + '</span>' +
                    '</div>';
                }).join('');
            }

            function groupParticipants(participants) {
                var groups = new Map();
                participants.forEach(function (row) {
                    var key = row.program_key || 'tanpa-prodi';
                    if (!groups.has(key)) {
                        groups.set(key, {
                            title: row.program || 'Tanpa Program Studi',
                            rows: []
                        });
                    }
                    groups.get(key).rows.push(row);
                });
                return Array.from(groups.values());
            }

            function renderParticipants(participants) {
                participants = participants || [];
                if (!participantList) return;

                var needle = filterText.toLowerCase();
                var filtered = needle
                    ? participants.filter(function (row) {
                        return (row.name + ' ' + row.nim + ' ' + row.program).toLowerCase().indexOf(needle) !== -1;
                    })
                    : participants;

                if (!filtered.length) {
                    participantList.innerHTML = '<div class="empty-state">Tidak ada data mahasiswa sesuai filter.</div>';
                    return;
                }

                participantList.innerHTML = groupParticipants(filtered).map(function (group) {
                    var checked = group.rows.filter(function (row) { return row.checked_in; }).length;
                    return '<section class="student-section">' +
                        '<div class="student-section-head"><div><h4>' + escapeHtml(group.title) + '</h4><span>' + numberFormat.format(group.rows.length) + ' mahasiswa</span></div><span>' + numberFormat.format(checked) + ' check-in</span></div>' +
                        '<div class="student-section-body">' +
                        group.rows.map(function (row) {
                            return '<div class="student-row ' + (row.checked_in ? 'is-checked' : '') + '">' +
                                '<div class="row-person"><strong>' + escapeHtml(row.name) + '</strong><small>' + escapeHtml(row.sequence_number || '-') + ' / ' + escapeHtml(row.nim) + '</small></div>' +
                                '<span class="status-pill ' + statusClass(row.rsvp_status) + '">' + escapeHtml(row.rsvp_label) + '</span>' +
                                '<span class="status-pill ' + checkinClass(row) + '">' + (row.checked_in ? 'Sudah check-in' : 'Belum check-in') + '</span>' +
                            '</div>';
                        }).join('') +
                        '</div></section>';
                }).join('');
            }

            function render(payload) {
                livePayload = payload || livePayload || {};
                renderSummary(livePayload.summary || {});
                renderLogs(livePayload.logs || []);
                renderParticipants(livePayload.participants || []);
                if (lastSync) {
                    lastSync.textContent = 'Sinkron ' + new Date().toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                }
            }

            async function refreshLive() {
                try {
                    var response = await fetch(app.dataset.liveUrl, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('Live failed');
                    render(await response.json());
                } catch (error) {
                    if (lastSync) lastSync.textContent = 'Realtime tersambung ulang...';
                }
            }

            async function submitScan() {
                if (!scanInput || busy) return;
                var code = scanInput.value.trim();
                if (!code) {
                    setResult('warning', 'Scan QR atau masukkan NIM terlebih dahulu.');
                    focusScanner();
                    return;
                }

                busy = true;
                scanForm.querySelector('button[type="submit"]').disabled = true;
                setResult('warning', 'Memproses check-in...');

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
                            manual_note: manualNote ? manualNote.value : ''
                        })
                    });
                    var data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Check-in gagal.');

                    if (data.ok) {
                        setResult(data.status === 'duplicate' ? 'warning' : 'success', data.message, data.participant);
                    } else {
                        setResult('error', data.message || 'Data tidak ditemukan.');
                    }

                    if (data.payload) render(data.payload);
                    scanInput.value = '';
                } catch (error) {
                    setResult('error', error.message || 'Check-in gagal diproses.');
                } finally {
                    busy = false;
                    scanForm.querySelector('button[type="submit"]').disabled = false;
                    window.setTimeout(focusScanner, 80);
                }
            }

            if (scanForm) {
                scanForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    submitScan();
                });
            }

            if (participantFilter) {
                participantFilter.addEventListener('input', function () {
                    filterText = participantFilter.value || '';
                    renderParticipants((livePayload && livePayload.participants) || []);
                });
            }

            document.addEventListener('click', function (event) {
                if (event.target.closest('input, textarea, select, button, a')) return;
                focusScanner();
            });

            render(livePayload);
            focusScanner();
            window.setInterval(refreshLive, 4000);
        })();
    </script>
@endpush
