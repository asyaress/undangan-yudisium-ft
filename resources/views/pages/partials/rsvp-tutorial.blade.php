  <div class="rsvp-spotlight" id="rsvpSpotlight" hidden aria-hidden="true"></div>
  <div
    class="rsvp-tutorial"
    id="rsvpTutorial"
    hidden
    role="dialog"
    aria-modal="true"
    aria-labelledby="rsvpTutorialTitle"
  >
    <div class="rsvp-tutorial-backdrop" aria-hidden="true"></div>
    <div class="rsvp-tutorial-card">
      <div class="rsvp-tutorial-copy">
        <span class="rsvp-tutorial-kicker">{{ $isStudentCategory && ! $participant ? 'Akses Mahasiswa' : 'Panduan Undangan' }}</span>
        <h3 id="rsvpTutorialTitle">{{ $isStudentCategory && ! $participant ? 'Masukkan NIM' : 'Panduan Undangan' }}</h3>
        <div class="rsvp-tutorial-typing">
          <p id="rsvpTutorialText"></p>
        </div>
        <div class="rsvp-tutorial-footer">
          <span class="rsvp-tutorial-progress" id="rsvpTutorialProgress"></span>
          <div class="rsvp-tutorial-actions">
            <button class="rsvp-tutorial-skip" type="button" id="rsvpTutorialSkip">Tutup</button>
            <button class="btn" type="button" id="rsvpTutorialNext" hidden>Lanjutkan</button>
          </div>
        </div>
      </div>
      <div class="rsvp-tutorial-visual" aria-hidden="true">
        <img src="{{ asset('tutorial-guide.png') }}" alt="">
      </div>
    </div>
  </div>

