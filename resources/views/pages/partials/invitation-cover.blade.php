      <section class="panel cover" id="cover">
        <div class="cover-identity">
          <img src="{{ asset('Unmul.png') }}" alt="Lambang Universitas Mulawarman">
          <div>
            <strong>Universitas Mulawarman</strong>
            <span>Fakultas Teknik</span>
          </div>
        </div>
        <p class="label">Undangan resmi</p>
        <h1>Yudisium</h1>
        <p class="meta">{{ $coverText }}</p>
        <p class="guest-label">Kepada Yth.</p>
        <p class="guest" id="recipientText">{{ $recipientName }}</p>
        <button class="btn" id="openInvitation" type="button">Buka Undangan</button>
      </section>
