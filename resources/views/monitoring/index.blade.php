@extends('layouts.dashboard')

@section('title', $title)
@section('breadcrumb_parent', 'Operasional')
@section('breadcrumb_active', $title)

@php
    $isStudent = $type === 'mahasiswa';
    $statusOptions = $isStudent
        ? ['all' => 'Semua', 'attending' => 'Hadir', 'declined' => 'Berhalangan', 'represented' => 'Diwakilkan', 'pending' => 'Belum Konfirmasi', 'checked_in' => 'Sudah check-in']
        : ['all' => 'Semua', 'attending' => 'Bersedia Hadir', 'declined' => 'Berhalangan', 'represented' => 'Diwakilkan', 'pending' => 'Belum Konfirmasi'];
    $query = request()->query();
@endphp

@section('page_actions')
    <a href="{{ route('monitoring.export', array_merge(['type' => $type], $query, ['format' => 'xls'])) }}" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</a>
    <a href="{{ route('monitoring.export', array_merge(['type' => $type], $query, ['format' => 'pdf'])) }}" class="btn btn-danger btn-sm" target="_blank"><i class="fa fa-file-pdf-o"></i> Export PDF</a>
    @if ($isStudent)
        <a href="{{ route('admin.checkin.manual.index') }}" class="btn btn-primary btn-sm"><i class="fa fa-check-square-o"></i> Check-in Manual</a>
    @endif
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/toastr/toastr.min.css') }}">
    <style>
        #toast-container.toast-top-right {
            right: 18px;
            top: 78px;
        }

        #toast-container > div {
            width: min(390px, calc(100vw - 36px));
            border-radius: 14px;
            padding: 16px 42px 16px 18px;
            background-image: none !important;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.18);
            opacity: 1;
        }

        #toast-container > .toast-success,
        #toast-container > .toast-info,
        #toast-container > .toast-warning,
        #toast-container > .toast-error {
            background-image: none !important;
        }

        #toast-container .toast-close-button {
            position: absolute;
            top: 12px;
            right: 14px;
            float: none;
            opacity: 0.55;
            text-shadow: none;
        }

        #toast-container > div:hover {
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.22);
            opacity: 1;
        }

        #toast-container .toast-title {
            display: block;
            margin-bottom: 4px;
            font-size: 0.95rem;
            line-height: 1.35;
        }

        #toast-container .toast-message {
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.88rem;
            line-height: 1.45;
        }

        #toast-container .toast-success {
            background-color: #047857;
        }

        #toast-container .toast-info {
            background-color: #2563eb;
        }

        #toast-container .toast-warning {
            background-color: #D9450B;
        }
    </style>
@endpush

@section('content')
@include('layouts.partials.block-header')

<style>
    .monitor-shell {
        display: grid;
        gap: 14px;
    }

    .monitor-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .monitor-tab {
        border: 1px solid #dfe3ea;
        border-radius: 12px;
        padding: 10px 14px;
        background: #fff;
        color: #4b5563;
        font-weight: 700;
        text-decoration: none;
    }

    .monitor-tab.active {
        background: #F5530D;
        border-color: #F5530D;
        color: #fff;
        box-shadow: 0 8px 20px rgba(245, 83, 13, 0.16);
    }

    .monitor-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .monitor-stat {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
        padding: 16px;
        min-height: 112px;
        display: grid;
        align-content: space-between;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .monitor-stat span {
        color: #6b7280;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .monitor-stat strong {
        display: block;
        color: #111827;
        font-size: 2rem;
        line-height: 1;
        margin-top: 8px;
    }

    .monitor-stat small {
        color: #6b7280;
        margin-top: 8px;
    }

    .monitor-panel {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .monitor-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid #eef0f4;
    }

    .monitor-panel-head h2 {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: #111827;
    }

    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #047857;
        font-size: 0.82rem;
        font-weight: 800;
    }

    .live-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        background: #10b981;
        box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.12);
        animation: livePulse 1.5s ease-in-out infinite;
    }

    @keyframes livePulse {
        50% { transform: scale(0.78); opacity: 0.6; }
    }

    .monitor-filter {
        padding: 16px;
    }

    .monitor-filter .form-control {
        border-radius: 10px;
    }

    .status-pills {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
    }

    .status-pill {
        border: 1px solid #dfe3ea;
        background: #fff;
        color: #4b5563;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 0.82rem;
        font-weight: 800;
        text-decoration: none;
    }

    .status-pill.active {
        border-color: rgba(245, 83, 13, 0.32);
        background: #fff3ee;
        color: #D9450B;
    }

    .monitor-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        border-bottom: 1px solid #eef0f4;
        background: #fafafa;
    }

    .sound-btn {
        border: 1px solid #dfe3ea;
        border-radius: 999px;
        background: #fff;
        color: #4b5563;
        padding: 8px 12px;
        font-weight: 800;
        cursor: pointer;
    }

    .sound-btn.is-on {
        border-color: rgba(4, 120, 87, 0.24);
        color: #047857;
        background: rgba(4, 120, 87, 0.07);
    }

    .monitor-list {
        display: grid;
        gap: 8px;
        padding: 12px;
        max-height: 68vh;
        overflow: auto;
    }

    .monitor-row {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.9fr) minmax(0, 0.8fr) minmax(0, 0.8fr);
        gap: 10px;
        align-items: center;
        border: 1px solid #edf0f4;
        border-radius: 12px;
        background: #fff;
        padding: 12px;
    }

    .monitor-row.is-new {
        animation: rowFlash 1.6s ease-out;
    }

    @keyframes rowFlash {
        0% { background: #fff3ee; border-color: rgba(245, 83, 13, 0.36); }
        100% { background: #fff; border-color: #edf0f4; }
    }

    .row-name strong {
        color: #111827;
        font-weight: 800;
    }

    .row-name small,
    .row-muted {
        color: #6b7280;
        font-size: 0.82rem;
    }

    .badge-soft {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 0.78rem;
        font-weight: 800;
        background: #f3f4f6;
        color: #4b5563;
    }

    .badge-soft.good {
        background: rgba(4, 120, 87, 0.09);
        color: #047857;
    }

    .badge-soft.bad {
        background: rgba(185, 28, 28, 0.09);
        color: #b91c1c;
    }

    .badge-soft.warn {
        background: #fff3ee;
        color: #D9450B;
    }

    .monitor-empty {
        padding: 28px;
        text-align: center;
        color: #6b7280;
    }

    @media (max-width: 992px) {
        .monitor-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .monitor-row {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 640px) {
        .monitor-stat-grid,
        .monitor-row {
            grid-template-columns: 1fr;
        }

        .monitor-panel-head,
        .monitor-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<div class="monitor-shell" id="monitorApp"
     data-live-url="{{ route('monitoring.live', array_merge(['type' => $type], $query)) }}"
     data-type="{{ $type }}">
    <div class="monitor-tabs">
        <a class="monitor-tab {{ $isStudent ? 'active' : '' }}" href="{{ route('monitoring.mahasiswa', $query) }}"><i class="fa fa-graduation-cap"></i> Monitoring Mahasiswa</a>
        <a class="monitor-tab {{ ! $isStudent ? 'active' : '' }}" href="{{ route('monitoring.private', $query) }}"><i class="fa fa-envelope-open-o"></i> Monitoring Private</a>
    </div>

    <div class="monitor-stat-grid">
        <div class="monitor-stat"><span>Total Data</span><strong data-stat="total">{{ number_format($summary['total']) }}</strong><small>{{ $isStudent ? 'Mahasiswa yudisium' : 'Penerima private' }}</small></div>
        <div class="monitor-stat"><span>Konfirmasi Hadir</span><strong data-stat="attending">{{ number_format($summary['attending']) }}</strong><small data-rate="responded">{{ $summary['responded_rate'] }}% sudah merespons</small></div>
        <div class="monitor-stat"><span>Berhalangan</span><strong data-stat="declined">{{ number_format($summary['declined']) }}</strong><small>Konfirmasi tidak hadir</small></div>
        <div class="monitor-stat"><span>Diwakilkan</span><strong data-stat="represented">{{ number_format($summary['represented']) }}</strong><small>Hadir melalui perwakilan</small></div>
        <div class="monitor-stat"><span>{{ $isStudent ? 'Sudah Check-in' : 'Belum Konfirmasi' }}</span><strong data-stat="{{ $isStudent ? 'checked_in' : 'pending' }}">{{ number_format($isStudent ? $summary['checked_in'] : $summary['pending']) }}</strong><small>{{ $isStudent ? $summary['checkin_rate'].'% sudah check-in' : 'Menunggu respons' }}</small></div>
    </div>

    <div class="monitor-panel">
        <div class="monitor-panel-head">
            <h2>Filter {{ $title }}</h2>
            <span class="live-indicator"><span class="live-dot"></span> Live tanpa refresh</span>
        </div>
        <form class="monitor-filter" method="get" action="{{ $isStudent ? route('monitoring.mahasiswa') : route('monitoring.private') }}">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Event Yudisium</label>
                    <select class="form-control" name="period_id">
                        @foreach ($periods as $period)
                            <option value="{{ $period->id }}" @selected((int) $filters['period_id'] === $period->id)>{{ $period->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Kategori</label>
                    <select class="form-control" name="category">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected($filters['category'] === $category->slug)>{{ $category->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Status</label>
                    <select class="form-control" name="status">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Cari</label>
                    <input class="form-control" name="q" value="{{ $filters['q'] }}" placeholder="{{ $isStudent ? 'NIM, nama, prodi' : 'Nama, kategori, catatan' }}">
                </div>
            </div>
            <div class="status-pills">
                @foreach ($statusOptions as $status => $label)
                    <a class="status-pill {{ $filters['status'] === $status ? 'active' : '' }}" href="{{ ($isStudent ? route('monitoring.mahasiswa') : route('monitoring.private')).'?'.http_build_query(array_merge(request()->except('status'), ['status' => $status])) }}">{{ $label }}</a>
                @endforeach
                <button class="btn btn-primary btn-sm ml-auto" type="submit"><i class="fa fa-search"></i> Terapkan</button>
            </div>
        </form>
    </div>

    <div class="monitor-panel">
        <div class="monitor-panel-head">
            <h2>Data Live</h2>
            <span class="text-muted" id="lastSync">Sinkronisasi awal</span>
        </div>
        <div class="monitor-toolbar">
            <div>
                <strong id="visibleCount">{{ number_format($resultRows->count()) }}</strong>
                <span class="text-muted">baris tampil</span>
            </div>
            <button class="sound-btn" type="button" id="soundToggle"><i class="fa fa-volume-up"></i> Aktifkan bunyi</button>
        </div>
        <div class="monitor-list" id="monitorRows"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets-template/assets/vendor/toastr/toastr.js') }}"></script>
<script>
    (() => {
      const app = document.getElementById("monitorApp");
      if (!app) return;

      const liveUrl = app.dataset.liveUrl;
      const monitorType = app.dataset.type;
      const rowContainer = document.getElementById("monitorRows");
      const lastSync = document.getElementById("lastSync");
      const visibleCount = document.getElementById("visibleCount");
      const soundToggle = document.getElementById("soundToggle");
      const numberFormat = new Intl.NumberFormat("id-ID");
      let rows = @json($resultRows->values());
      let knownState = new Map();
      let soundEnabled = false;
      let audioContext = null;
      let initialized = false;

      const statusClass = (status) => {
        if (status === "attending") return "good";
        if (status === "declined") return "bad";
        if (status === "represented") return "warn";
        return "warn";
      };

      const escapeHtml = (value) => String(value ?? "").replace(/[&<>"']/g, (char) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      }[char]));

      const playBeep = () => {
        if (!soundEnabled) return;
        audioContext = audioContext || new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gain = audioContext.createGain();
        oscillator.type = "sine";
        oscillator.frequency.setValueAtTime(880, audioContext.currentTime);
        gain.gain.setValueAtTime(0.0001, audioContext.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.12, audioContext.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.22);
        oscillator.connect(gain);
        gain.connect(audioContext.destination);
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.24);
      };

      const showToast = (row, message, variant = "success") => {
        if (!window.toastr) return;

        toastr.options = {
          closeButton: true,
          progressBar: true,
          newestOnTop: true,
          preventDuplicates: false,
          positionClass: "toast-top-right",
          timeOut: 5200,
          extendedTimeOut: 1200,
          showDuration: 220,
          hideDuration: 220,
        };

        const detail = `${escapeHtml(row.name)} - ${escapeHtml(row.category || row.context || "-")}`;
        toastr[variant](`<span>${detail}</span>`, escapeHtml(message));
      };

      const rowSignature = (row) => `${row.rsvp_status}|${row.responded_at || ""}|${row.checked_in ? "1" : "0"}|${row.checked_in_at || ""}`;

      const detectChanges = (nextRows) => {
        const changed = new Set();
        nextRows.forEach((row) => {
          const signature = rowSignature(row);
          const previous = knownState.get(row.id);
          if (initialized && previous && previous !== signature) {
            changed.add(row.id);
            if (row.rsvp_status === "attending") showToast(row, "Konfirmasi hadir baru masuk", "success");
            if (row.rsvp_status === "declined") showToast(row, "Konfirmasi berhalangan baru masuk", "warning");
            if (row.rsvp_status === "represented") showToast(row, "Konfirmasi diwakilkan baru masuk", "info");
            if (monitorType === "mahasiswa" && row.checked_in) showToast(row, "Check-in baru tercatat", "info");
            playBeep();
          }
          knownState.set(row.id, signature);
        });
        initialized = true;
        return changed;
      };

      const renderSummary = (summary) => {
        Object.entries(summary).forEach(([key, value]) => {
          const node = document.querySelector(`[data-stat="${key}"]`);
          if (node) node.textContent = numberFormat.format(value);
        });
        const respondedRate = document.querySelector("[data-rate='responded']");
        if (respondedRate) respondedRate.textContent = `${summary.responded_rate}% sudah merespons`;
      };

      const renderRows = (changed = new Set()) => {
        visibleCount.textContent = numberFormat.format(rows.length);
        if (!rows.length) {
          rowContainer.innerHTML = '<div class="monitor-empty">Belum ada data sesuai filter.</div>';
          return;
        }

        rowContainer.innerHTML = rows.map((row) => {
          const checkin = monitorType === "mahasiswa"
            ? `<div><span class="badge-soft ${row.checked_in ? "good" : ""}">${row.checked_in ? "Sudah check-in" : "Belum check-in"}</span><div class="row-muted">${escapeHtml(row.checked_in_at_label)}</div></div>`
            : `<div><span class="badge-soft">${escapeHtml(row.category)}</span><div class="row-muted">${escapeHtml(row.context)}</div></div>`;
          const meta = monitorType === "mahasiswa"
            ? `${row.sequence_number || "-"} / ${escapeHtml(row.nim || "-")}`
            : escapeHtml(row.context || "-");

          return `
            <div class="monitor-row ${changed.has(row.id) ? "is-new" : ""}">
              <div class="row-name">
                <strong>${escapeHtml(row.name)}</strong>
                <small class="d-block">${meta}</small>
              </div>
              <div>
                <span class="badge-soft">${escapeHtml(row.context)}</span>
                ${row.note ? `<div class="row-muted">${escapeHtml(row.note)}</div>` : ""}
              </div>
              <div>
                <span class="badge-soft ${statusClass(row.rsvp_status)}">${escapeHtml(row.rsvp_label)}</span>
                <div class="row-muted">${escapeHtml(row.responded_at_label)}</div>
              </div>
              ${checkin}
            </div>
          `;
        }).join("");
      };

      const poll = async () => {
        try {
          const response = await fetch(liveUrl, { headers: { "Accept": "application/json" } });
          if (!response.ok) throw new Error("Live request failed");
          const payload = await response.json();
          rows = payload.rows || [];
          const changed = detectChanges(rows);
          renderSummary(payload.summary || {});
          renderRows(changed);
          lastSync.textContent = `Terakhir sinkron ${new Date().toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit", second: "2-digit" })}`;
        } catch (error) {
          lastSync.textContent = "Live terputus, mencoba ulang...";
        }
      };

      soundToggle?.addEventListener("click", async () => {
        soundEnabled = !soundEnabled;
        soundToggle.classList.toggle("is-on", soundEnabled);
        soundToggle.innerHTML = soundEnabled
          ? '<i class="fa fa-volume-up"></i> Bunyi aktif'
          : '<i class="fa fa-volume-up"></i> Aktifkan bunyi';
        if (soundEnabled) {
          audioContext = audioContext || new (window.AudioContext || window.webkitAudioContext)();
          await audioContext.resume();
          playBeep();
        }
      });

      detectChanges(rows);
      renderRows();
      poll();
      window.setInterval(poll, 6000);
    })();
</script>
@endpush
