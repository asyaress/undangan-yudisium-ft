        @unless ($isStudentCategory && ! $participant)
        <div class="invitation-details-section card--full">
        <div class="details-divider" id="invitationDetails">Detail Undangan</div>

        <div class="invitation-details" id="invitationContentDetails">
        <article class="panel card card--white card--full">
          <h2 class="title">Dengan Hormat</h2>
          <p class="line" id="invitationLine">
            {{ $selectedCategory?->invitation_text ?: 'Dengan hormat, kami mengundang '.$invitationGreeting.' untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.' }}
          </p>
          <div class="mini-brand">
            <img data-logo="unmul" src="{{ asset('Unmul.png') }}" alt="Logo Universitas Mulawarman" />
          </div>
          <div class="details">
            <div class="detail-item">
              <span class="detail-label">Hari/Tanggal</span>
              <span class="detail-value">{{ $eventDateLabel }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Waktu</span>
              <span class="detail-value">{{ $eventTimeLabel }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Tempat</span>
              <span class="detail-value">{{ $eventLocation }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Alamat</span>
              <span class="detail-value">{{ $eventAddress }}</span>
            </div>
          </div>
        </article>

        <article class="panel card card--white watermark-card">
          <div class="event-strip">
            <h3>Susunan Acara</h3>
            <ol>
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
          </div>
        </article>

        <article class="panel card card--white watermark-card">
          <h3 class="title">Lokasi Acara</h3>
          <div class="map-wrap">
            <iframe
              title="Peta lokasi acara Yudisium Fakultas Teknik"
              src="https://maps.google.com/maps?q={{ $mapQuery }}&t=&z=16&ie=UTF8&iwloc=&output=embed"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
          <p class="location-text">
            Tempat: {{ $eventLocation }}<br />
            {{ $eventAddress }}
          </p>
          <a
            class="map-btn"
            href="https://www.google.com/maps/search/?api=1&query={{ $mapQuery }}"
            target="_blank"
            rel="noopener">
            Petunjuk ke lokasi
          </a>
        </article>

        <article class="panel card card--white card--full">
          <div class="auth-stack">
            <div class="auth-head">
              <p class="auth-date">{{ $signatureDateLabel }}</p>
              <p class="auth-salutation">Dekan Fakultas Teknik,</p>
            </div>
            <div class="signature-layer">
              <div class="signature-box">
                <img class="asset-image" id="ttdImage" src="{{ asset('ttd.png') }}" alt="Tanda tangan dekan" />
                <span class="asset-placeholder" id="ttdPlaceholder">Tambahkan file tanda tangan: ttd.png</span>
              </div>
              <div class="stamp-overlay">
                <img class="asset-image" id="stampImage" src="{{ asset('stempel.png') }}" alt="Stempel Fakultas Teknik" />
                <span class="asset-placeholder" id="stampPlaceholder">Tambahkan stempel: stempel.png</span>
              </div>
            </div>
            <p class="auth-name">{{ $signerName }}</p>
            <p class="auth-role">{{ $signerTitle }}</p>
          </div>
        </article>

        <article class="panel card card--white card--full note-card watermark-card">
          <h3 class="title">Catatan</h3>
          <ol class="notes-list">
            @foreach ($eventNotes as $eventNote)
              <li>{{ $eventNote }}</li>
            @endforeach
          </ol>
        </article>

        <article class="panel card card--white card--full closing-card">
          <p class="closing">
            {{ $selectedCategory?->closing_text ?: 'Atas kehadiran '.$invitationGreeting.', kami ucapkan terima kasih.' }}
          </p>
        </article>
        </div>
        </div>
        @endunless

        @if ($requiresRsvp || ($isStudentCategory && ! $participant))
        <article class="panel rsvp-card card--full watermark-card {{ $rsvpStatus === 'pending' && ! $rsvpClosed ? 'is-pulse' : '' }}" id="rsvpSection">
          <div class="rsvp-card-head">
            <div class="rsvp-badge">✓</div>
            <div>
              <h3>Konfirmasi Kehadiran</h3>
              <p>
                @if ($isStudentCategory)
                  Masukkan NIM terlebih dahulu. Setelah cocok dengan data panitia, undangan dan formulir konfirmasi akan terbuka.
                @else
                  Silakan mengisi konfirmasi kehadiran melalui formulir berikut.
                @endif
                @if ($rsvpDeadlineLabel && ! $rsvpClosed)
                  Batas waktu konfirmasi kehadiran: <strong>{{ $rsvpDeadlineLabel }}</strong> <b>WITA</b>.
                @endif
              </p>
            </div>
          </div>

          @if ($isStudentCategory)
            <div class="rsvp-steps">
              <div class="rsvp-step {{ ! $participant ? 'is-active' : '' }}">
                <span class="rsvp-step-num">1</span>
                <span>Masukkan NIM</span>
              </div>
              <div class="rsvp-step {{ $participant ? 'is-active' : '' }}">
                <span class="rsvp-step-num">2</span>
                <span>Buka undangan dan isi konfirmasi</span>
              </div>
            </div>
          @endif

          @if (session('success') && $rsvpStatus === 'pending')
            <div class="flash-note good">{{ session('success') }}</div>
          @endif

          @if (session('error'))
            <div class="flash-note error">{{ session('error') }}</div>
          @endif

          @if ($lookupError)
            <div class="flash-note error">{{ $lookupError }}</div>
          @endif

          @if ($rsvpClosed)
            <div class="flash-note error">Pengisian konfirmasi kehadiran telah ditutup{{ $rsvpDeadlineLabel ? ' sejak '.$rsvpDeadlineLabel : '' }}.</div>
          @endif

          @if ($isStudentCategory && ! $participant)
            <form method="post" action="{{ route('undangan.verify-nim') }}" class="invite-form" id="studentVerifyForm">
              @csrf
              <input type="hidden" name="event_id" value="{{ $activeEvent->id }}">
              <input type="hidden" name="category_slug" value="{{ $selectedCategory?->slug }}">
              <div class="field">
                <label for="nim">NIM Mahasiswa</label>
                <input
                  id="nim"
                  name="nim"
                  type="text"
                  value="{{ old('nim') }}"
                  placeholder="Contoh: 2200000001"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  maxlength="20"
                  autocomplete="off"
                  data-nim-only
                  data-warning-target="nimFilterNotice"
                  aria-describedby="nimHelp nimFilterNotice{{ $errors->has('nim') ? ' nimError' : '' }}"
                  @error('nim') aria-invalid="true" class="is-invalid" @enderror>
                <p class="form-hint" id="nimHelp">Gunakan angka NIM saja, tanpa huruf, spasi, atau tanda baca. Jika NIM diawali 0, tetap tuliskan 0.</p>
                <p class="field-warning" id="nimFilterNotice" hidden></p>
                @error('nim')
                  <p class="field-error" id="nimError">{{ $message }}</p>
                @enderror
              </div>
              <div class="action-row">
                <button class="btn" type="submit">Buka Undangan</button>
              </div>
            </form>
          @endif

          @if ($participant && ! $studentIdentityConfirmed)
            <div class="identity-panel">
              <h4>Data Mahasiswa</h4>
              <div class="identity-list">
                <div class="identity-row"><span>Nama</span><strong>{{ $participant->name }}</strong></div>
                <div class="identity-row"><span>NIM</span><strong>{{ $participant->nim }}</strong></div>
                <div class="identity-row"><span>Prodi</span><strong>{{ $participant->studyProgram?->name ?: ($participant->study_program ?: 'Program studi belum diisi') }}</strong></div>
              </div>
              <p>Apakah data ini benar?</p>
              <div class="action-row">
                <form method="post" action="{{ route('undangan.confirm-student') }}">
                  @csrf
                  <input type="hidden" name="event_id" value="{{ $activeEvent->id }}">
                  <input type="hidden" name="category_slug" value="{{ $selectedCategory?->slug }}">
                  <input type="hidden" name="participant_token" value="{{ $participant->invitation_token }}">
                  <button class="btn" type="submit">Ya, lanjutkan</button>
                </form>
                <form method="post" action="{{ route('undangan.clear-student') }}">
                  @csrf
                  <input type="hidden" name="event_id" value="{{ $activeEvent->id }}">
                  <input type="hidden" name="category_slug" value="{{ $selectedCategory?->slug }}">
                  <button class="btn ghost-btn" type="submit">Bukan saya</button>
                </form>
              </div>
            </div>
          @elseif ($participant)
            @if ($rsvpStatus !== 'pending')
              <div class="rsvp-success-panel" role="status" aria-live="polite">
                <span class="rsvp-success-icon" aria-hidden="true">
                  @if ($rsvpStatus === 'declined')
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18M9.5 14.5l5 5M14.5 14.5l-5 5"></path></svg>
                  @elseif ($rsvpStatus === 'represented')
                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M17 8l4 4-4 4M21 12h-7"></path></svg>
                  @else
                    <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>
                  @endif
                </span>
                <div class="rsvp-success-copy">
                  <strong class="rsvp-success-label">{{ match ($rsvpStatus) { 'declined' => 'Berhalangan Hadir', 'represented' => 'Diwakilkan', default => 'Bersedia Hadir' } }}</strong>
                  <p>
                    Konfirmasi {{ match ($rsvpStatus) { 'declined' => 'berhalangan hadir', 'represented' => 'diwakilkan', default => 'kehadiran' } }} tersimpan
                    @if ($rsvpRespondedAt)
                      <time datetime="{{ $rsvpRespondedAt->toIso8601String() }}">{{ $rsvpRespondedAt->locale('id')->translatedFormat('d F Y H:i') }} WITA</time>
                    @endif
                  </p>
                </div>
              </div>
            @elseif (! $rsvpClosed)
            <form method="post" action="{{ route('rsvp.participant') }}" class="invite-form" id="participantRsvpForm">
              @csrf
              <input type="hidden" name="event_id" value="{{ $activeEvent->id }}">
              <input type="hidden" name="participant_token" value="{{ $participant->invitation_token }}">
              <div class="pill-row">
                <span class="pill">{{ $participant->name }}</span>
                <span class="pill">{{ $participant->study_program ?: 'Program studi belum diisi' }}</span>
                <span class="pill {{ $rsvpStatus === 'attending' ? 'good' : ($rsvpStatus === 'declined' ? 'bad' : 'warn') }}">
                  {{ match ($rsvpStatus) { 'attending' => 'Sudah konfirmasi hadir', 'declined' => 'Berhalangan hadir', 'represented' => 'Diwakilkan', default => 'Belum konfirmasi' } }}
                </span>
              </div>
              <div class="field">
                <label>Status Kehadiran</label>
                <div class="radio-grid two-options">
                  <label class="radio-option">
                    <input type="radio" name="attendance" value="attending" @checked(old('attendance', $participant->rsvp_status) === 'attending') required>
                    <span class="radio-mark" aria-hidden="true"></span>
                    <span>Bersedia Hadir</span>
                  </label>
                  <label class="radio-option">
                    <input type="radio" name="attendance" value="declined" @checked(old('attendance', $participant->rsvp_status) === 'declined') required>
                    <span class="radio-mark" aria-hidden="true"></span>
                    <span>Berhalangan Hadir</span>
                  </label>
                </div>
              </div>
              <div class="field" id="participantNoteField" data-participant-note-field hidden>
                <label for="note" id="participantNoteLabel">Catatan berhalangan</label>
                <textarea id="note" name="note" data-declined-placeholder="Tuliskan alasan berhalangan hadir secara singkat." placeholder="Tuliskan alasan berhalangan hadir secara singkat.">{{ old('note') }}</textarea>
              </div>
              <div class="action-row">
                <button class="btn" type="submit">Simpan Konfirmasi</button>
              </div>
            </form>
            @elseif ($rsvpStatus === 'pending')
              <div class="empty-note">Konfirmasi kehadiran sudah ditutup sebelum data tersimpan.</div>
            @endif
          @elseif ($recipient)
            @if ($rsvpStatus !== 'pending')
              <div class="rsvp-success-panel" role="status" aria-live="polite">
                <span class="rsvp-success-icon" aria-hidden="true">
                  @if ($rsvpStatus === 'declined')
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18M9.5 14.5l5 5M14.5 14.5l-5 5"></path></svg>
                  @elseif ($rsvpStatus === 'represented')
                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="4"></circle><path d="M17 8l4 4-4 4M21 12h-7"></path></svg>
                  @else
                    <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>
                  @endif
                </span>
                <div class="rsvp-success-copy">
                  <strong class="rsvp-success-label">{{ match ($rsvpStatus) { 'declined' => 'Berhalangan Hadir', 'represented' => 'Diwakilkan', default => 'Bersedia Hadir' } }}</strong>
                  <p>
                    Konfirmasi {{ match ($rsvpStatus) { 'declined' => 'berhalangan hadir', 'represented' => 'diwakilkan', default => 'kehadiran' } }} tersimpan
                    @if ($rsvpRespondedAt)
                      <time datetime="{{ $rsvpRespondedAt->toIso8601String() }}">{{ $rsvpRespondedAt->locale('id')->translatedFormat('d F Y H:i') }} WITA</time>
                    @endif
                  </p>
                </div>
              </div>
            @else
            <form method="post" action="{{ route('rsvp.recipient') }}" class="invite-form" id="recipientRsvpForm">
              @csrf
              <input type="hidden" name="recipient_id" value="{{ $recipient->id }}">
              <input type="hidden" name="token" value="{{ $recipient->token }}">
              <div class="pill-row">
                <span class="pill">{{ $recipient->invitation_name }}</span>
                <span class="pill">{{ $recipient->category?->title }}</span>
                <span class="pill {{ $rsvpStatus === 'attending' ? 'good' : ($rsvpStatus === 'declined' ? 'bad' : 'warn') }}">
                  {{ match ($rsvpStatus) { 'attending' => 'Sudah konfirmasi hadir', 'declined' => 'Berhalangan hadir', 'represented' => 'Diwakilkan', default => 'Belum konfirmasi' } }}
                </span>
              </div>
              <div class="field">
                <label>Status Kehadiran</label>
                <div class="radio-grid">
                  <label class="radio-option">
                    <input type="radio" name="attendance" value="attending" @checked(old('attendance', $recipient->rsvp_status) === 'attending') required>
                    <span class="radio-mark" aria-hidden="true"></span>
                    <span>Bersedia Hadir</span>
                  </label>
                  <label class="radio-option">
                    <input type="radio" name="attendance" value="declined" @checked(old('attendance', $recipient->rsvp_status) === 'declined') required>
                    <span class="radio-mark" aria-hidden="true"></span>
                    <span>Berhalangan Hadir</span>
                  </label>
                  <label class="radio-option">
                    <input type="radio" name="attendance" value="represented" @checked(old('attendance', $recipient->rsvp_status) === 'represented') required>
                    <span class="radio-mark" aria-hidden="true"></span>
                    <span>Diwakilkan</span>
                  </label>
                </div>
              </div>
              <div class="field" id="recipientNoteField" data-conditional-note-field hidden>
                <label for="recipient-note" id="recipientNoteLabel">Catatan berhalangan</label>
                <textarea id="recipient-note" name="note" data-declined-placeholder="Tuliskan alasan berhalangan hadir secara singkat." placeholder="Tuliskan alasan berhalangan hadir secara singkat.">{{ old('note') }}</textarea>
              </div>
              <div class="field" id="recipientDelegateField" data-delegate-fields hidden>
                <label>Data Perwakilan</label>
                <input name="representative_name" value="{{ old('representative_name') }}" placeholder="Nama lengkap perwakilan">
                <input name="representative_position" value="{{ old('representative_position') }}" placeholder="Jabatan perwakilan">
              </div>
              <div class="action-row">
                <button class="btn" type="submit" @disabled($rsvpClosed)>Simpan Konfirmasi</button>
              </div>
            </form>
            @endif
          @elseif (! $isStudentCategory)
            <div class="empty-note">
              Link undangan belum valid. Pastikan Anda membuka link resmi yang dibagikan panitia.
            </div>
          @endif
        </article>
        @endif
