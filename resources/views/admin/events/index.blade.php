@extends('layouts.dashboard')

@section('title', 'Event Yudisium')
@section('breadcrumb_parent', 'Kelola Data')
@section('breadcrumb_active', 'Event Yudisium')

@section('page_actions')
    <a class="btn btn-primary" href="{{ route('admin.events.create') }}">
        <i class="fa fa-plus"></i> Tambah Event
    </a>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/jquery-datatable/dataTables.bootstrap4.min.css') }}">
    <style>
        .events-table-card .dataTables_wrapper .row:first-child,
        .events-table-card .dataTables_wrapper .row:last-child {
            align-items: center;
        }

        .events-table-card table.dataTable {
            border-collapse: collapse !important;
        }

        .events-table-card thead th {
            border-bottom: 1px solid #e5e7eb !important;
            color: #6b7280;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .event-title {
            color: #111827;
            font-weight: 800;
            line-height: 1.35;
        }

        .event-subtitle,
        .event-link {
            color: #6b7280;
            font-size: 12px;
            line-height: 1.5;
        }

        .event-link {
            display: block;
            max-width: 360px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .metric-stack {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .metric-pill {
            min-width: 72px;
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
        }

        .metric-pill span {
            display: block;
            color: #6b7280;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .metric-pill strong {
            display: block;
            color: #111827;
            font-size: 18px;
            line-height: 1.15;
        }

        .event-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .event-status {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
    </style>
@endpush

@section('content')
@include('layouts.partials.block-header')

<div class="row clearfix">
    <div class="col-lg-12">
        <div class="card events-table-card">
            <div class="header">
                <h2>Daftar Event</h2>
                <ul class="header-dropdown">
                    <li><span class="badge badge-info">{{ $periods->count() }} event</span></li>
                </ul>
            </div>
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="eventsTable">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Jadwal & Lokasi</th>
                                <th>Status</th>
                                <th>Statistik</th>
                                <th>Link</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($periods as $period)
                                @php
                                    $invitationLinkId = 'invitation-link-'.$period->id;
                                    $checkinLinkId = 'checkin-link-'.$period->id;
                                    $invitationUrl = route('undangan.show', ['slug' => $period->slug, 'to' => 'yudisiawan', 'preview' => 'open']);
                                    $checkinUrl = route('checkin.form', ['slug' => $period->slug]);
                                    $attendingCount = $period->participant_attending_count + $period->recipient_attending_count;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="event-title">{{ $period->archive_title }}</div>
                                        <div class="event-subtitle">{{ $period->slug }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $period->event_date?->translatedFormat('d F Y') ?: 'Tanggal belum diatur' }}</div>
                                        <div class="event-subtitle">{{ $period->location ?: 'Lokasi belum diatur' }}</div>
                                    </td>
                                    <td>
                                        <div class="event-status">
                                            <span class="badge {{ $period->is_active ? 'badge-success' : 'badge-warning' }}">
                                                {{ $period->is_active ? 'Aktif' : 'Arsip' }}
                                            </span>
                                            <span class="badge {{ $period->is_published ? 'badge-info' : 'badge-secondary' }}">
                                                {{ $period->is_published ? 'Publik' : 'Private' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="metric-stack">
                                            <div class="metric-pill"><span>Yudisiawan</span><strong>{{ number_format($period->participants_count) }}</strong></div>
                                            <div class="metric-pill"><span>Private</span><strong>{{ number_format($period->recipients_count) }}</strong></div>
                                            <div class="metric-pill"><span>Hadir</span><strong>{{ number_format($attendingCount) }}</strong></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span id="{{ $invitationLinkId }}" class="event-link">{{ $invitationUrl }}</span>
                                        <span id="{{ $checkinLinkId }}" class="event-link">{{ $checkinUrl }}</span>
                                    </td>
                                    <td class="text-right">
                                        <div class="event-actions">
                                            <a class="btn btn-outline-secondary btn-sm" href="{{ $invitationUrl }}" target="_blank" rel="noopener">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <button class="btn btn-outline-secondary btn-sm" type="button" data-copy="{{ $invitationLinkId }}">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm" type="button" data-copy="{{ $checkinLinkId }}">
                                                <i class="fa fa-check-square-o"></i>
                                            </button>
                                            <a class="btn btn-primary btn-sm" href="{{ route('admin.events.edit', $period) }}">
                                                <i class="fa fa-pencil"></i> Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets-template/assets/bundles/datatablescripts.bundle.js') }}"></script>
    <script src="{{ asset('assets-template/assets/vendor/jquery-datatable/dataTables.bootstrap4.min.js') }}"></script>
    @include('admin.partials.copy-script')
    <script>
        $(function () {
            $('#eventsTable').DataTable({
                pageLength: 10,
                order: [[0, 'asc']],
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ event',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ event',
                    infoEmpty: 'Belum ada event',
                    infoFiltered: '(difilter dari _MAX_ event)',
                    zeroRecords: 'Event tidak ditemukan',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Berikutnya',
                        previous: 'Sebelumnya'
                    }
                },
                columnDefs: [
                    { orderable: false, targets: [3, 4, 5] }
                ]
            });
        });
    </script>
@endpush
