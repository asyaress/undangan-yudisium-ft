        <article class="panel card">
          <div class="section-kicker">Kategori Undangan</div>
          <h2 class="title">Pilih kategori untuk {{ $activeEvent?->archive_title }}</h2>
          <p class="line">Setiap kategori punya akses dan teks undangan sendiri. Pilih kategori yang sesuai sebelum membagikan link undangan.</p>

          @if ($categories->isNotEmpty())
            @include('pages.partials.category-picker', [
              'pickerId' => 'eventCategoryPicker',
              'pickerLabel' => 'Kategori undangan',
              'pickerButtonText' => 'Lanjutkan',
            ])
          @else
              <div class="empty-note">Belum ada kategori undangan untuk event ini.</div>
          @endif
        </article>

