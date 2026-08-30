@php
  $firstCategory = $categories->first();
  $initialUrl = $firstCategory && $activeEvent
    ? route('home', ['event' => $activeEvent->slug, 'to' => $firstCategory->slug])
    : '#';

  $accessCopy = function ($category) {
      if ($category->usesNimAccess()) {
          return 'Kategori mahasiswa. Pengunjung akan diminta memasukkan NIM sebelum undangan dan konfirmasi terbuka.';
      }

      if ($category->usesPrivateAccess()) {
          return 'Kategori private. Undangan perlu link personal dengan token unik dari dashboard admin.';
      }

      if ($category->usesNipAccess()) {
          return 'Kategori semi private. Penerima akan diminta memasukkan NIP sebelum undangan dan konfirmasi terbuka.';
      }

      if ($category->usesNameAccess()) {
          return 'Kategori semi private. Penerima akan diminta memasukkan nama sebelum undangan dan konfirmasi terbuka.';
      }

      return 'Kategori umum. Undangan bisa dibuka langsung tanpa NIM, token, atau verifikasi tambahan.';
  };
@endphp

<form
  class="category-picker"
  method="get"
  action="{{ $activeEvent ? route('undangan.show', $activeEvent->slug) : route('home') }}"
  data-category-picker>
  <div class="category-picker-grid">
    <div class="category-select-control">
      <label for="{{ $pickerId }}">{{ $pickerLabel }}</label>
      <select id="{{ $pickerId }}" name="to" data-category-select>
        @foreach ($categories as $category)
          @php
            $categoryUrl = route('home', ['event' => $activeEvent?->slug, 'to' => $category->slug]);
            $displayUrl = $categoryUrl.($category->usesPrivateAccess() ? '&ref=TOKEN_UNIK' : '');
            $isGuardedCategory = $category->usesPrivateAccess() || $category->usesRecipientLookupAccess();
          @endphp
          <option
            value="{{ $category->slug }}"
            data-title="{{ $category->title }}"
            data-recipient="{{ $category->recipient_label }}"
            data-access="{{ $category->access_mode_label }}"
            data-rsvp="{{ $category->requiresRsvp() ? 'Konfirmasi kehadiran' : 'Tanpa konfirmasi kehadiran' }}"
            data-note="{{ $accessCopy($category) }}"
            data-url="{{ $categoryUrl }}"
            data-display-url="{{ $displayUrl }}"
            data-link-label="{{ $category->usesPrivateAccess() ? 'Format link private' : 'Link undangan' }}"
            data-access-kind="{{ $isGuardedCategory ? 'private' : 'open' }}"
            data-rsvp-enabled="{{ $category->requiresRsvp() ? '1' : '0' }}">
            {{ $category->title }}
          </option>
        @endforeach
      </select>
    </div>
    <button class="btn" type="submit">{{ $pickerButtonText }}</button>
  </div>

  <div class="category-preview" data-category-preview>
    <div>
      <h3 data-category-title>{{ $firstCategory?->title }}</h3>
      <p data-category-recipient>{{ $firstCategory?->recipient_label }}</p>
    </div>
    <div class="pill-row">
      <span class="pill {{ ($firstCategory?->usesPrivateAccess() || $firstCategory?->usesRecipientLookupAccess()) ? 'warn' : 'good' }}" data-category-access>
        {{ $firstCategory?->access_mode_label }}
      </span>
      <span class="pill {{ $firstCategory?->requiresRsvp() ? 'warn' : '' }}" data-category-rsvp>
        {{ $firstCategory?->requiresRsvp() ? 'Konfirmasi kehadiran' : 'Tanpa konfirmasi kehadiran' }}
      </span>
    </div>
    <div class="category-preview-note" data-category-note>
      {{ $firstCategory ? $accessCopy($firstCategory) : 'Pilih kategori undangan terlebih dahulu.' }}
    </div>
    <div class="category-link-box">
      <label data-category-link-label>{{ $firstCategory?->usesPrivateAccess() ? 'Format link private' : 'Link undangan' }}</label>
      <div class="category-link-row">
        <input type="text" value="{{ $firstCategory?->usesPrivateAccess() ? $initialUrl.'&ref=TOKEN_UNIK' : $initialUrl }}" readonly data-category-url-input>
        <a class="btn ghost-btn" href="{{ $initialUrl }}" target="_blank" rel="noopener" data-category-open-link>Buka Link</a>
        <button class="btn ghost-btn" type="button" data-category-copy>Salin Link</button>
      </div>
    </div>
    <div class="category-submit-row">
      <button class="btn" type="submit">{{ $pickerButtonText }}</button>
    </div>
  </div>
</form>
