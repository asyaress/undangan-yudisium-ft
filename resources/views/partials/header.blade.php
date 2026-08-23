<header id="header" class="site-header header-style-1">
    <nav class="navigation navbar navbar-default">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="open-btn">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand invite-brand" href="{{ route('home') }}">
                    <img src="{{ asset('Unmul.png') }}" alt="Universitas Mulawarman">
                    <span>
                        <strong>Undangan Yudisium</strong>
                        <small>Fakultas Teknik</small>
                    </span>
                </a>
            </div>

            <div id="navbar" class="navbar-collapse collapse navbar-right navigation-holder">
                <button class="close-navbar"><i class="ti-close"></i></button>
                <ul class="nav navbar-nav invite-nav">
                    @if (($mode ?? 'invitation') === 'archive')
                        <li><a href="#arsip">Arsip</a></li>
                        <li><a href="#kategori">Kategori</a></li>
                        <li><a href="{{ route('checkin.form') }}">Check-in</a></li>
                        <li><a href="{{ route('login') }}">Admin</a></li>
                    @else
                        <li><a href="#detail">Detail</a></li>
                        <li><a href="#rsvp">Konfirmasi Kehadiran</a></li>
                        <li><a href="#lokasi">Lokasi</a></li>
                        <li><a href="{{ route('home') }}">Arsip</a></li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
</header>
