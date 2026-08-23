        <article class="panel card">
          <div class="section-kicker">Arsip Yudisium</div>
          <h2 class="title">Rekam Event</h2>
          <p class="line">Ringkasan singkat setiap event dan status kehadirannya.</p>

          <div class="archive-grid">
            @forelse ($events as $event)
              @php
                $archiveStartsAt = $event->archive_starts_at;
                $archiveCountdownTarget = $archiveStartsAt?->toIso8601String();
              @endphp
              <div class="archive-card" data-archive-card data-start-at="{{ $archiveCountdownTarget }}">
                <div class="archive-card-top">
                  <div>
                    <span class="section-kicker mb-2" style="margin-bottom: 8px;">{{ $event->archiveStatus }}</span>
                    <h3>{{ $event->archive_title }}</h3>
                  </div>
                  <div class="archive-card-date">
                    @if ($archiveStartsAt)
                      {{ $archiveStartsAt->locale('id')->translatedFormat('d F Y H:i') }} WITA
                    @else
                      Jadwal belum diatur
                    @endif
                  </div>
                </div>

                <div class="archive-metrics">
                  <div class="archive-metric">
                    <span>Mahasiswa Yudisium</span>
                    <strong data-count-up data-target="{{ $event->participants_count }}">0</strong>
                  </div>
                  <div class="archive-metric">
                    <span>Hadir Aktual</span>
                    <strong data-count-up data-target="{{ $event->checked_in_participants_count }}">0</strong>
                  </div>
                </div>

                <div class="archive-status-row">
                  <span class="pill {{ $event->isArchiveUpcoming() ? 'warn' : 'good' }}">
                    {{ $event->isArchiveUpcoming() ? 'Belum mulai' : 'Tersimpan' }}
                  </span>
                </div>

                @if ($archiveStartsAt && now()->lt($archiveStartsAt))
                  <div class="archive-countdown" data-countdown-target="{{ $archiveCountdownTarget }}">
                    <span class="archive-countdown-label">Countdown menuju acara</span>
                    <div class="archive-countdown-grid">
                      <div class="archive-countdown-unit"><strong data-countdown-days>00</strong><span>Hari</span></div>
                      <div class="archive-countdown-unit"><strong data-countdown-hours>00</strong><span>Jam</span></div>
                      <div class="archive-countdown-unit"><strong data-countdown-minutes>00</strong><span>Menit</span></div>
                      <div class="archive-countdown-unit"><strong data-countdown-seconds>00</strong><span>Detik</span></div>
                    </div>
                  </div>
                @endif

                <div class="archive-card-actions">
                  <a class="link-btn" href="{{ route('undangan.show', $event->slug) }}">Buka Event</a>
                </div>
              </div>
            @empty
              <div class="archive-item">
                <h3>Belum ada event dipublikasikan</h3>
                <p>Admin dapat menambahkan event dari dashboard agar arsip undangan mulai tampil di halaman ini.</p>
              </div>
            @endforelse
          </div>
        </article>

