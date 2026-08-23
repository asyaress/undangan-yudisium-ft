@extends('layouts.dashboard')

@section('title', 'Mahasiswa')
@section('breadcrumb_parent', 'Kelola Data')
@section('breadcrumb_active', 'Mahasiswa')

@section('content')
@include('layouts.partials.block-header')

<div class="row clearfix">
    <div class="col-lg-4 col-md-6">
        <div class="card number-chart"><div class="card-body py-3"><span class="text-uppercase">Total</span><h4 class="mb-0 mt-2">{{ number_format($stats['total']) }}</h4></div></div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="card number-chart"><div class="card-body py-3"><span class="text-uppercase">Konfirmasi Hadir</span><h4 class="mb-0 mt-2">{{ number_format($stats['attending']) }}</h4></div></div>
    </div>
    <div class="col-lg-4 col-md-12">
        <div class="card number-chart"><div class="card-body py-3"><span class="text-uppercase">Check-in</span><h4 class="mb-0 mt-2">{{ number_format($stats['checked_in']) }}</h4></div></div>
    </div>
</div>

<div class="row clearfix">
    <div class="col-lg-12">
        <div class="card">
            <div class="header">
                <h2>Import Excel</h2>
                <ul class="header-dropdown">
                    <li><a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.participants.template') }}">Download Template Excel</a></li>
                </ul>
            </div>
            <div class="card-body py-3">
                @if (session('import_errors'))
                    <div class="alert alert-warning">
                        <strong>Baris bermasalah:</strong>
                        <ul class="mb-0">
                            @foreach (session('import_errors') as $importError)
                                <li>{{ $importError }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($adminPeriods->isEmpty())
                    <p class="text-muted mb-0">Buat event terlebih dahulu.</p>
                @else
                    <form method="post" action="{{ route('admin.participants.import') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Event</label>
                                <select class="form-control" name="period_id">
                                    @foreach ($adminPeriods as $p)
                                        <option value="{{ $p->id }}" @selected($period?->id === $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>File</label>
                                <input class="form-control" name="file" type="file" accept=".xlsx" required>
                            </div>
                        </div>
                        <small class="text-muted">Bisa import template Excel standar, atau file absensi yang sudah dipisah per program studi. Untuk format absensi, urutan peserta mengikuti urutan file dan tanggal lahir boleh kosong.</small><br>
                        <button class="btn btn-primary mt-2" type="submit">Import</button>
                    </form>

                    @if ($studyPrograms->isNotEmpty())
                        <div class="mt-3">
                            <strong class="d-block mb-2">Kode Prodi</strong>
                            <div class="row">
                                @foreach ($studyPrograms as $program)
                                    <div class="col-md-4 col-sm-6 mb-1">
                                        <span class="badge badge-light border">{{ $program->code }}</span>
                                        <span class="text-muted">{{ $program->name }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row clearfix">
    <div class="col-lg-12">
        <div class="card">
            <div class="header">
                <h2>Daftar Mahasiswa</h2>
                @if ($period)
                    <ul class="header-dropdown"><li><span class="badge badge-info">{{ $period->name }}</span></li></ul>
                @endif
            </div>
            <div class="card-body py-3">
                <form method="get" class="row mb-3">
                    <div class="col-md-4 form-group">
                        <label>Event</label>
                        <select class="form-control" name="period_id" onchange="this.form.submit()">
                            @foreach ($adminPeriods as $p)
                                <option value="{{ $p->id }}" @selected($period?->id === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Cari</label>
                        <input class="form-control" name="q" value="{{ $search }}" placeholder="NIM atau nama">
                    </div>
                    <div class="col-md-2 form-group d-flex align-items-end">
                        <button class="btn btn-outline-primary btn-block" type="submit">Filter</button>
                    </div>
                </form>

                <form id="participantBulkDeleteForm" method="post" action="{{ route('admin.participants.destroy-selected') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="period_id" value="{{ $period?->id }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                </form>

                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                    <small class="text-muted">Centang data yang ingin dihapus, lalu klik Hapus Terpilih.</small>
                    <button class="btn btn-outline-danger btn-sm" type="submit" form="participantBulkDeleteForm" data-confirm-delete>
                        Hapus Terpilih
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:42px;"><input type="checkbox" data-check-all="participant"></th>
                                <th>No</th>
                                <th>NIM</th>
                                <th>Nama</th>
                                <th>Tanggal Lahir</th>
                                <th>Prodi</th>
                                <th>Konfirmasi Kehadiran</th>
                                <th>Link Undangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($participants as $participant)
                                @php
                                    $linkId = 'link-participant-'.$participant->id;
                                    $inviteUrl = route('home', ['event' => $participant->period?->slug, 'to' => 'yudisiawan', 'ref' => $participant->invitation_token]);
                                @endphp
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="{{ $participant->id }}" form="participantBulkDeleteForm" data-check-item="participant"></td>
                                    <td>{{ $participant->sequence_number ?: '-' }}</td>
                                    <td>{{ $participant->nim }}</td>
                                    <td>
                                        <strong>{{ $participant->name }}</strong>
                                    </td>
                                    <td>{{ $participant->birth_date?->format('Y-m-d') ?: '-' }}</td>
                                    <td>{{ $participant->studyProgram?->name ?: ($participant->study_program ?: '-') }}</td>
                                    <td>
                                        @if ($participant->rsvp_status === 'attending')<span class="badge badge-success">Hadir</span>
                                        @elseif ($participant->rsvp_status === 'declined')<span class="badge badge-danger">Berhalangan</span>
                                        @elseif ($participant->rsvp_status === 'represented')<span class="badge badge-info">Diwakilkan</span>
                                        @else<span class="badge badge-warning">Belum</span>@endif
                                    </td>
                                    <td style="min-width:280px;">
                                        <div class="input-group input-group-sm">
                                            <input id="{{ $linkId }}" class="form-control" type="text" value="{{ $inviteUrl }}" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" data-copy="{{ $linkId }}">Salin</button>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline-danger btn-sm" type="submit" form="deleteParticipant{{ $participant->id }}" data-confirm-delete>Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-muted">Belum ada data mahasiswa.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @foreach ($participants as $participant)
                    <form id="deleteParticipant{{ $participant->id }}" method="post" action="{{ route('admin.participants.destroy-selected') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="only_id" value="{{ $participant->id }}">
                        <input type="hidden" name="period_id" value="{{ $period?->id }}">
                        <input type="hidden" name="q" value="{{ $search }}">
                    </form>
                @endforeach

                @if ($participants->hasPages())
                    <div class="admin-pagination">{{ $participants->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('admin.partials.copy-script')
    <script>
        document.querySelectorAll('[data-check-all]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var group = checkbox.getAttribute('data-check-all');
                document.querySelectorAll('[data-check-item="' + group + '"]').forEach(function (item) {
                    item.checked = checkbox.checked;
                });
            });
        });

        document.querySelectorAll('[data-confirm-delete]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                if (!confirm('Hapus data yang dipilih? Tindakan ini tidak bisa dibatalkan.')) {
                    event.preventDefault();
                }
            });
        });
    </script>
@endpush
