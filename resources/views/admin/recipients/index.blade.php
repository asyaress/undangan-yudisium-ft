@extends('layouts.dashboard')

@section('title', $category->title)
@section('breadcrumb_parent', 'Undangan Private')
@section('breadcrumb_active', ($selectedPeriod?->name ?? 'Event').' - '.$category->title)

@section('page_actions')
    <a class="btn btn-primary" href="{{ route('admin.recipients.create', ['categorySlug' => $category->slug, 'period_id' => $periodId]) }}">
        <i class="fa fa-plus"></i> Tambah Penerima
    </a>
    <a class="btn btn-outline-secondary" href="{{ route('admin.recipients.template', ['categorySlug' => $category->slug, 'period_id' => $periodId]) }}">
        <i class="fa fa-download"></i> Template Excel
    </a>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/jquery-datatable/dataTables.bootstrap4.min.css') }}">
    <style>
        .recipient-tools {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: end;
        }

        .recipient-import {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px;
            background: #f8fafc;
        }

        .recipient-link {
            display: block;
            max-width: 420px;
            overflow: hidden;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.5;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .recipient-title {
            color: #111827;
            font-weight: 800;
            line-height: 1.35;
        }

        .recipient-muted {
            color: #6b7280;
            font-size: 12px;
            line-height: 1.5;
        }

        .recipient-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .recipients-table-card table.dataTable {
            border-collapse: collapse !important;
        }

        .recipients-table-card thead th {
            border-bottom: 1px solid #e5e7eb !important;
            color: #6b7280;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        @media (max-width: 768px) {
            .recipient-tools {
                grid-template-columns: 1fr;
            }

            .recipient-tools .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
@include('layouts.partials.block-header')

<div class="row clearfix">
    <div class="col-lg-2 col-md-4">
        <div class="card number-chart"><div class="card-body py-3"><span class="text-uppercase">Total</span><h4 class="mb-0 mt-2">{{ number_format($stats['total']) }}</h4></div></div>
    </div>
    <div class="col-lg-2 col-md-4">
        <div class="card number-chart"><div class="card-body py-3"><span class="text-uppercase">Konfirmasi Hadir</span><h4 class="mb-0 mt-2">{{ number_format($stats['attending']) }}</h4></div></div>
    </div>
    <div class="col-lg-2 col-md-4">
        <div class="card number-chart"><div class="card-body py-3"><span class="text-uppercase">Berhalangan</span><h4 class="mb-0 mt-2">{{ number_format($stats['declined']) }}</h4></div></div>
    </div>
    <div class="col-lg-2 col-md-4">
        <div class="card number-chart"><div class="card-body py-3"><span class="text-uppercase">Diwakilkan</span><h4 class="mb-0 mt-2">{{ number_format($stats['represented']) }}</h4></div></div>
    </div>
    <div class="col-lg-2 col-md-4">
        <div class="card number-chart"><div class="card-body py-3"><span class="text-uppercase">Belum Konfirmasi</span><h4 class="mb-0 mt-2">{{ number_format($stats['pending']) }}</h4></div></div>
    </div>
</div>

<div class="row clearfix">
    <div class="col-lg-12">
        <div class="card">
            <div class="header">
                <h2>Import & Filter</h2>
                @if ($selectedPeriod)
                    <ul class="header-dropdown"><li><span class="badge badge-info">{{ $selectedPeriod->name }}</span></li></ul>
                @endif
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

                <form method="get" class="row mb-3">
                    <div class="col-md-3 form-group">
                        <label>Event</label>
                        <select class="form-control" name="period_id" onchange="this.form.submit()">
                            @foreach ($adminPeriods as $p)
                                <option value="{{ $p->id }}" @selected($periodId == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Konfirmasi Kehadiran</label>
                        <select class="form-control" name="rsvp">
                            <option value="">Semua</option>
                            <option value="attending" @selected($rsvpFilter === 'attending')>Hadir</option>
                            <option value="declined" @selected($rsvpFilter === 'declined')>Berhalangan</option>
                            <option value="represented" @selected($rsvpFilter === 'represented')>Diwakilkan</option>
                            <option value="pending" @selected($rsvpFilter === 'pending')>Belum</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Cari</label>
                        <input class="form-control" name="q" value="{{ $search }}" placeholder="Nama atau catatan">
                    </div>
                    <div class="col-md-2 form-group d-flex align-items-end">
                        <button class="btn btn-outline-primary btn-block" type="submit">Filter</button>
                    </div>
                </form>

                @if ($adminPeriods->isNotEmpty())
                    <div class="recipient-import">
                        <form method="post" action="{{ route('admin.recipients.import', $category->slug) }}" enctype="multipart/form-data" class="recipient-tools">
                            @csrf
                            <input type="hidden" name="period_id" value="{{ $periodId }}">
                            <div class="form-group mb-0">
                                <label>Import Excel</label>
                                <input class="form-control" name="file" type="file" accept=".xlsx" required>
                                <small class="text-muted">Gunakan template Excel penerima private, lalu import file .xlsx.</small>
                            </div>
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fa fa-upload"></i> Import Penerima
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row clearfix">
    <div class="col-lg-12">
        <div class="card recipients-table-card">
            <div class="header">
                <h2>Daftar Penerima</h2>
                <ul class="header-dropdown"><li><span class="badge badge-info">{{ $recipients->count() }} penerima</span></li></ul>
            </div>
            <div class="card-body py-3">
                <div class="form-group">
                    <label>Link Massal</label>
                    <div class="input-group">
                        <textarea id="bulkRecipientLinks" class="form-control" rows="3" readonly>{{ $bulkLinks }}</textarea>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" data-copy="bulkRecipientLinks">Salin Semua</button>
                        </div>
                    </div>
                </div>

                <form id="recipientBulkDeleteForm" method="post" action="{{ route('admin.recipients.destroy-selected', $category->slug) }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="period_id" value="{{ $periodId }}">
                    <input type="hidden" name="rsvp" value="{{ $rsvpFilter }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                </form>

                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                    <small class="text-muted">Centang penerima yang ingin dihapus, lalu klik Hapus Terpilih.</small>
                    <button class="btn btn-outline-danger btn-sm" type="submit" form="recipientBulkDeleteForm" data-confirm-delete>
                        Hapus Terpilih
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="recipientsTable">
                        <thead>
                            <tr>
                                <th style="width:42px;"><input type="checkbox" data-check-all="recipient"></th>
                                <th>Penerima</th>
                                <th>Catatan</th>
                                <th>Konfirmasi</th>
                                <th>Link</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recipients as $recipient)
                                @php
                                    $linkId = 'link-recipient-'.$recipient->id;
                                    $inviteUrl = route('home', ['event' => $recipient->period?->slug, 'to' => $category->slug, 'ref' => $recipient->token]);
                                @endphp
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="{{ $recipient->id }}" form="recipientBulkDeleteForm" data-check-item="recipient"></td>
                                    <td>
                                        <div class="recipient-title">{{ $recipient->name }}</div>
                                        <div class="recipient-muted">{{ $recipient->invitation_name }}</div>
                                    </td>
                                    <td>{{ $recipient->context_note ?: '-' }}</td>
                                    <td>
                                        @if ($recipient->rsvp_status === 'attending')<span class="badge badge-success">Hadir</span>
                                        @elseif ($recipient->rsvp_status === 'declined')<span class="badge badge-danger">Berhalangan</span>
                                        @elseif ($recipient->rsvp_status === 'represented')<span class="badge badge-info">Diwakilkan</span>
                                        @else<span class="badge badge-warning">Belum</span>@endif
                                    </td>
                                    <td>
                                        <span id="{{ $linkId }}" class="recipient-link">{{ $inviteUrl }}</span>
                                    </td>
                                    <td class="text-right">
                                        <div class="recipient-actions">
                                            <a class="btn btn-outline-secondary btn-sm" href="{{ $inviteUrl }}" target="_blank" rel="noopener">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <button class="btn btn-outline-secondary btn-sm" type="button" data-copy="{{ $linkId }}">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                            <a class="btn btn-primary btn-sm" href="{{ route('admin.recipients.edit', ['categorySlug' => $category->slug, 'recipient' => $recipient, 'period_id' => $periodId]) }}">
                                                <i class="fa fa-pencil"></i> Edit
                                            </a>
                                            <button class="btn btn-outline-danger btn-sm" type="submit" form="deleteRecipient{{ $recipient->id }}" data-confirm-delete>
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach ($recipients as $recipient)
                    <form id="deleteRecipient{{ $recipient->id }}" method="post" action="{{ route('admin.recipients.destroy-selected', $category->slug) }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="only_id" value="{{ $recipient->id }}">
                        <input type="hidden" name="period_id" value="{{ $periodId }}">
                        <input type="hidden" name="rsvp" value="{{ $rsvpFilter }}">
                        <input type="hidden" name="q" value="{{ $search }}">
                    </form>
                @endforeach
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
            $('#recipientsTable').DataTable({
                pageLength: 10,
                order: [[1, 'asc']],
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ penerima',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ penerima',
                    infoEmpty: 'Belum ada penerima',
                    infoFiltered: '(difilter dari _MAX_ penerima)',
                    zeroRecords: 'Penerima tidak ditemukan',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Berikutnya',
                        previous: 'Sebelumnya'
                    }
                },
                columnDefs: [
                    { orderable: false, targets: [0, 4, 5] }
                ]
            });
        });

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
