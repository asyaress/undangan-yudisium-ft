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
                <h2>Tambah Manual</h2>
            </div>
            <div class="card-body py-3">
                @if ($adminPeriods->isEmpty())
                    <p class="text-muted mb-0">Buat event terlebih dahulu sebelum menambahkan mahasiswa.</p>
                @elseif ($studyPrograms->isEmpty())
                    <p class="text-muted mb-0">Tambahkan master program studi terlebih dahulu.</p>
                @else
                    <form method="post" action="{{ route('admin.participants.store') }}" class="manual-participant-form">
                        @csrf
                        <div class="row">
                            <div class="col-lg-4 col-md-6 form-group">
                                <label>Event</label>
                                <select class="form-control @error('period_id') is-invalid @enderror" name="period_id" required>
                                    @foreach ($adminPeriods as $p)
                                        <option value="{{ $p->id }}" @selected((int) old('period_id', $period?->id) === $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                @error('period_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-4 col-md-6 form-group">
                                <label>Program Studi</label>
                                <select class="form-control @error('study_program_id') is-invalid @enderror" name="study_program_id" required>
                                    <option value="">Pilih program studi</option>
                                    @foreach ($studyPrograms as $program)
                                        <option value="{{ $program->id }}" @selected((int) old('study_program_id') === $program->id)>{{ $program->name }}</option>
                                    @endforeach
                                </select>
                                @error('study_program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-4 col-md-6 form-group">
                                <label>No Urut</label>
                                <input class="form-control @error('sequence_number') is-invalid @enderror" name="sequence_number" type="number" min="1" value="{{ old('sequence_number') }}" placeholder="Otomatis jika kosong">
                                @error('sequence_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-4 col-md-6 form-group">
                                <label>NIM</label>
                                <input class="form-control @error('nim') is-invalid @enderror" name="nim" value="{{ old('nim') }}" inputmode="numeric" pattern="[0-9]*" autocomplete="off" data-numeric-only required>
                                @error('nim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-4 col-md-6 form-group">
                                <label>Nama Mahasiswa</label>
                                <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-4 col-md-6 form-group">
                                <label>Tanggal Lahir</label>
                                <input class="form-control @error('birth_date') is-invalid @enderror" name="birth_date" type="date" value="{{ old('birth_date') }}">
                                @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-4 col-md-6 form-group">
                                <label>Email</label>
                                <input class="form-control @error('email') is-invalid @enderror" name="email" type="email" value="{{ old('email') }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-4 col-md-6 form-group">
                                <label>No WhatsApp</label>
                                <input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" inputmode="tel">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-lg-4 col-md-12 form-group d-flex align-items-end">
                                <button class="btn btn-primary btn-block" type="submit">
                                    <i class="fa fa-plus"></i> Tambah Mahasiswa
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
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
                        <small class="text-muted">Bisa import template Excel standar, atau file absensi yang sudah dipisah per program studi. Untuk format absensi, urutan peserta mengikuti urutan file. Tanggal lahir tetap boleh kosong karena undangan mahasiswa dibuka dengan NIM.</small><br>
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

                <div class="student-link-box mb-3">
                    <div>
                        <strong>Link Undangan Mahasiswa</strong>
                        <p class="mb-0 text-muted">Satu link untuk seluruh mahasiswa. Mahasiswa akan memasukkan NIM sebelum undangan dan konfirmasi kehadiran terbuka.</p>
                    </div>
                    @if ($studentInvitationUrl)
                        <div class="input-group input-group-sm student-link-input">
                            <input id="studentInvitationUrl" class="form-control" type="text" value="{{ $studentInvitationUrl }}" readonly>
                            <div class="input-group-append">
                                <a class="btn btn-outline-secondary" href="{{ $studentInvitationUrl }}" target="_blank" rel="noopener">Buka</a>
                                <button class="btn btn-outline-secondary" type="button" data-copy="studentInvitationUrl">Salin</button>
                            </div>
                        </div>
                    @else
                        <span class="badge badge-warning">Kategori mahasiswa belum tersedia untuk event ini</span>
                    @endif
                </div>

                <form id="participantBulkDeleteForm" method="post" action="{{ route('admin.participants.destroy-selected') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="period_id" value="{{ $period?->id }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                </form>

                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                    <small class="text-muted">Data ditampilkan per program studi mengikuti urutan prodi dan urutan import di dalam prodi.</small>
                    <button class="btn btn-outline-danger btn-sm" type="submit" form="participantBulkDeleteForm" data-confirm-delete>
                        Hapus Terpilih
                    </button>
                </div>

                @forelse ($participantSections as $section)
                    <div class="participant-section mb-4">
                        <div class="participant-section-head">
                            <div>
                                <h5 class="mb-1">{{ $section['name'] }}</h5>
                                @if ($section['code'])
                                    <span class="text-muted">Kode prodi: {{ $section['code'] }}</span>
                                @endif
                            </div>
                            <span class="badge badge-light border">{{ $section['participants']->count() }} mahasiswa</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 participant-table">
                                <thead>
                                    <tr>
                                        <th style="width:42px;"><input type="checkbox" data-check-section></th>
                                        <th style="width:68px;">No</th>
                                        <th>NIM</th>
                                        <th>Nama</th>
                                        <th>Tanggal Lahir</th>
                                        <th>Konfirmasi Kehadiran</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($section['participants'] as $participant)
                                        <tr>
                                            <td><input type="checkbox" name="ids[]" value="{{ $participant->id }}" form="participantBulkDeleteForm" data-check-item="participant"></td>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $participant->nim ?: '-' }}</td>
                                            <td>
                                                <strong>{{ $participant->name }}</strong>
                                            </td>
                                            <td>{{ $participant->birth_date?->format('Y-m-d') ?: '-' }}</td>
                                            <td>
                                                @if ($participant->rsvp_status === 'attending')<span class="badge badge-success">Hadir</span>
                                                @elseif ($participant->rsvp_status === 'declined')<span class="badge badge-danger">Berhalangan</span>
                                                @elseif ($participant->rsvp_status === 'represented')<span class="badge badge-info">Diwakilkan</span>
                                                @else<span class="badge badge-warning">Belum</span>@endif
                                            </td>
                                            <td>
                                                <button class="btn btn-outline-danger btn-sm" type="submit" form="deleteParticipant{{ $participant->id }}" data-confirm-delete>Hapus</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="text-muted border rounded p-3">Belum ada data mahasiswa.</div>
                @endforelse

                @foreach ($participants as $participant)
                    <form id="deleteParticipant{{ $participant->id }}" method="post" action="{{ route('admin.participants.destroy-selected') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="only_id" value="{{ $participant->id }}">
                        <input type="hidden" name="period_id" value="{{ $period?->id }}">
                        <input type="hidden" name="q" value="{{ $search }}">
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('head')
    <style>
        .participant-section {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .participant-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            background: #fff7ed;
        }

        .participant-section-head h5 {
            color: #111827;
            font-weight: 800;
        }

        .participant-table thead th {
            border-top: 0;
            white-space: nowrap;
        }

        .participant-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .manual-participant-form label {
            color: #6b7280;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .student-link-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #d96c0f;
            border-radius: 8px;
            padding: 14px 16px;
            background: #fff;
        }

        .student-link-input {
            max-width: 620px;
            min-width: 320px;
        }

        @media (max-width: 767.98px) {
            .student-link-box {
                align-items: stretch;
                flex-direction: column;
            }

            .student-link-input {
                max-width: none;
                min-width: 0;
                width: 100%;
            }
        }
    </style>
@endpush

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

        document.querySelectorAll('[data-check-section]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var section = checkbox.closest('.participant-section');
                if (!section) return;

                section.querySelectorAll('[data-check-item="participant"]').forEach(function (item) {
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

        document.querySelectorAll('[data-numeric-only]').forEach(function (input) {
            input.addEventListener('input', function () {
                input.value = input.value.replace(/\D/g, '');
            });
        });
    </script>
@endpush
