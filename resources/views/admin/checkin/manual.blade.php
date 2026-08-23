@extends('layouts.dashboard')

@section('title', 'Check-in Manual')
@section('breadcrumb_parent', 'Operasional')
@section('breadcrumb_active', 'Check-in Manual')

@section('content')
@include('layouts.partials.block-header')

<div class="row clearfix">
    <div class="col-lg-5 col-md-12">
        <div class="card">
            <div class="header"><h2>Backup Panitia</h2></div>
            <div class="card-body py-3">
                @if ($adminPeriods->isEmpty())
                    <p class="text-muted mb-0">Buat event terlebih dahulu.</p>
                @else
                    <form method="post" action="{{ route('admin.checkin.manual.search') }}">
                        @csrf
                        <div class="form-group">
                            <label>Event</label>
                            <select class="form-control" name="period_id">
                                @foreach ($adminPeriods as $p)
                                    <option value="{{ $p->id }}" @selected($period?->id === $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>NIM Mahasiswa</label>
                            <input class="form-control" name="nim" type="text" value="{{ old('nim', $lookupNim) }}" placeholder="Masukkan NIM peserta">
                        </div>
                        <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Cari Data</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7 col-md-12">
        <div class="card">
            <div class="header"><h2>Hasil Verifikasi</h2></div>
            <div class="card-body py-3">
                @if ($participant)
                    <h5 class="mb-1">{{ $participant->name }}</h5>
                    <p class="text-muted mb-3">{{ $participant->nim }} &middot; {{ $participant->studyProgram?->name ?: ($participant->study_program ?: '-') }}</p>

                    <table class="table table-sm mb-3">
                        <tr>
                            <td>Event</td>
                            <td><strong>{{ $participant->period?->name ?: '-' }}</strong></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                @if ($participant->checked_in_at)
                                    <span class="badge badge-success">Sudah check-in</span>
                                    <small class="text-muted ml-2">{{ $participant->checked_in_at?->format('d/m/Y H:i') }}</small>
                                @else
                                    <span class="badge badge-warning">Belum check-in</span>
                                @endif
                            </td>
                        </tr>
                    </table>

                    <form method="post" action="{{ route('admin.checkin.manual.confirm') }}">
                        @csrf
                        <input type="hidden" name="period_id" value="{{ $period?->id }}">
                        <input type="hidden" name="participant_id" value="{{ $participant->id }}">
                        <input type="hidden" name="nim" value="{{ $participant->nim }}">
                        <div class="form-group">
                            <label>Catatan Verifikasi Manual</label>
                            <textarea class="form-control" name="manual_note" rows="3" placeholder="Contoh: GPS gagal terbaca, mahasiswa hadir di meja registrasi." required>{{ old('manual_note') }}</textarea>
                        </div>
                        <button class="btn btn-success" type="submit" @disabled($participant->checked_in_at)>
                            <i class="fa fa-check"></i> Check-in Manual
                        </button>
                    </form>
                @elseif ($lookupError)
                    <div class="alert alert-warning mb-0">{{ $lookupError }}</div>
                @else
                    <p class="text-muted mb-0">Cari NIM untuk melakukan check-in manual saat GPS mahasiswa bermasalah.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row clearfix">
    <div class="col-lg-12">
        <div class="card">
            <div class="header">
                <h2>Log Lokasi Terakhir</h2>
                @if ($period)
                    <ul class="header-dropdown"><li><span class="badge badge-info">{{ $period->name }}</span></li></ul>
                @endif
            </div>
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Mahasiswa</th>
                                <th>Status</th>
                                <th>Jarak</th>
                                <th>Akurasi</th>
                                <th>Sumber</th>
                                <th>Admin</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td>{{ $log->attempted_at?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <strong>{{ $log->participant?->name ?: '-' }}</strong><br>
                                        <small class="text-muted">{{ $log->nim ?: $log->participant?->nim }}</small>
                                    </td>
                                    <td>
                                        @if ($log->status === 'accepted')
                                            <span class="badge badge-success">Diterima</span>
                                        @elseif ($log->status === 'manual_review')
                                            <span class="badge badge-warning">Perlu manual</span>
                                        @elseif ($log->status === 'duplicate')
                                            <span class="badge badge-info">Duplikat</span>
                                        @elseif ($log->status === 'failed_time')
                                            <span class="badge badge-secondary">Di luar waktu</span>
                                        @elseif ($log->status === 'rejected_location')
                                            <span class="badge badge-danger">Lokasi ditolak</span>
                                        @else
                                            <span class="badge badge-danger">Ditolak</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->distance_meter !== null ? number_format($log->distance_meter).' m' : '-' }}</td>
                                    <td>{{ $log->accuracy_meter !== null ? number_format($log->accuracy_meter).' m' : '-' }}</td>
                                    <td>{{ ucfirst($log->source) }}</td>
                                    <td>{{ $log->admin?->email ?: '-' }}</td>
                                    <td>
                                        {{ $log->message ?: '-' }}
                                        @if ($log->manual_note)
                                            <br><small class="text-muted">{{ $log->manual_note }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-muted">Belum ada log check-in.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">Koordinat mentah disimpan untuk audit, tetapi tabel ini menonjolkan jarak dan akurasi agar lebih aman dibaca operasional.</small>
            </div>
        </div>
    </div>
</div>
@endsection
