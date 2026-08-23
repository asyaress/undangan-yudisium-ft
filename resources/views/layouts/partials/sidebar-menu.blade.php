@php
    $isKelolaActive = request()->routeIs('admin.events.*', 'admin.participants.*', 'admin.study-programs.*', 'admin.categories.*');
    $isPrivateActive = request()->routeIs('admin.recipients.*');
    $isOpsActive = request()->routeIs('monitoring.*', 'checkin.*', 'admin.checkin.*', 'home', 'undangan.*');
@endphp
<nav id="left-sidebar-nav" class="sidebar-nav">
    <ul id="main-menu" class="metismenu li_animation_delay">
        <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}">
                <i class="fa fa-dashboard"></i><span>Dashboard</span>
            </a>
        </li>

        <li class="{{ $isKelolaActive ? 'active' : '' }}">
            <a href="#KelolaData" class="has-arrow">
                <i class="fa fa-database"></i><span>Kelola Data</span>
            </a>
            <ul>
                <li class="{{ request()->routeIs('admin.events.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.events.index') }}">Event Yudisium</a>
                </li>
                <li class="{{ request()->routeIs('admin.participants.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.participants.index') }}">Mahasiswa</a>
                </li>
                <li class="{{ request()->routeIs('admin.study-programs.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.study-programs.index') }}">Program Studi</a>
                </li>
                <li class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.categories.index') }}">Kategori Undangan</a>
                </li>
            </ul>
        </li>

        @if (isset($privateCategories) && $privateCategories->isNotEmpty() && isset($adminPeriods) && $adminPeriods->isNotEmpty())
            @php
                $currentPeriodId = request()->routeIs('admin.recipients.*')
                    ? (request()->integer('period_id') ?: ($activePeriod?->id))
                    : null;
                $currentCategorySlug = request()->routeIs('admin.recipients.index')
                    ? request()->route('categorySlug')
                    : null;
            @endphp
            <li class="{{ $isPrivateActive ? 'active' : '' }}">
                <a href="#UndanganPrivate" class="has-arrow">
                    <i class="fa fa-envelope"></i><span>Undangan Private</span>
                </a>
                <ul>
                    @foreach ($adminPeriods as $period)
                        @php
                            $isPeriodActive = $isPrivateActive && $currentPeriodId == $period->id;
                        @endphp
                        <li class="{{ $isPeriodActive ? 'active' : '' }}">
                            <a href="#UndanganPrivate-{{ $period->id }}" class="has-arrow">
                                <span>{{ $period->name }}</span>
                                @if ($period->is_active)
                                    <small class="text-success ml-1">(aktif)</small>
                                @endif
                            </a>
                            <ul>
                                @forelse ($privateCategories->where('period_id', $period->id) as $cat)
                                    <li class="{{ $isPeriodActive && $currentCategorySlug === $cat->slug ? 'active' : '' }}">
                                        <a href="{{ route('admin.recipients.index', ['categorySlug' => $cat->slug, 'period_id' => $period->id]) }}">
                                            {{ $cat->title }}
                                        </a>
                                    </li>
                                @empty
                                    <li><a href="{{ route('admin.categories.index', ['period_id' => $period->id]) }}">Atur kategori private</a></li>
                                @endforelse
                            </ul>
                        </li>
                    @endforeach
                </ul>
            </li>
        @endif

        <li class="{{ $isOpsActive ? 'active' : '' }}">
            <a href="#Operasional" class="has-arrow">
                <i class="fa fa-line-chart"></i><span>Operasional</span>
            </a>
            <ul>
                <li class="{{ request()->routeIs('monitoring.mahasiswa') ? 'active' : '' }}">
                    <a href="{{ route('monitoring.mahasiswa') }}">Monitoring Mahasiswa</a>
                </li>
                <li class="{{ request()->routeIs('monitoring.private') ? 'active' : '' }}">
                    <a href="{{ route('monitoring.private') }}">Monitoring Private</a>
                </li>
                <li class="{{ request()->routeIs('admin.checkin.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.checkin.manual.index') }}">Check-in Manual</a>
                </li>
                <li class="{{ request()->routeIs('home', 'undangan.*') ? 'active' : '' }}">
                    <a href="{{ route('home') }}">Arsip Publik</a>
                </li>
            </ul>
        </li>

        @guest
            <li class="{{ request()->routeIs('login*') ? 'active' : '' }}">
                <a href="{{ route('login') }}">
                    <i class="fa fa-lock"></i><span>Login Admin</span>
                </a>
            </li>
        @endguest
    </ul>
</nav>
