@extends('layouts.dashboard')

@section('title', 'Kategori Undangan')
@section('breadcrumb_parent', 'Kelola Data')
@section('breadcrumb_active', 'Kategori Undangan')

@section('page_actions')
    @if ($selectedPeriod)
        <a class="btn btn-primary" href="{{ route('admin.categories.create', ['period_id' => $selectedPeriod->id]) }}">
            <i class="fa fa-plus"></i> Tambah Kategori
        </a>
    @endif
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets-template/assets/vendor/jquery-datatable/dataTables.bootstrap4.min.css') }}">
    <style>
        .category-context {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: end;
        }

        .categories-table-card table.dataTable {
            border-collapse: collapse !important;
        }

        .categories-table-card thead th {
            border-bottom: 1px solid #e5e7eb !important;
            color: #6b7280;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .category-title {
            color: #111827;
            font-weight: 800;
            line-height: 1.35;
        }

        .category-muted,
        .category-link {
            color: #6b7280;
            font-size: 12px;
            line-height: 1.5;
        }

        .category-link {
            display: block;
            max-width: 380px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .category-actions,
        .category-badges {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .order-note {
            margin-top: 8px;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .category-context {
                grid-template-columns: 1fr;
            }

            .category-context .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
@include('layouts.partials.block-header')

<div class="row clearfix">
    <div class="col-lg-12">
        <div class="card">
            <div class="header"><h2>Konteks Event</h2></div>
            <div class="card-body py-3">
                <form method="get" action="{{ route('admin.categories.index') }}" class="category-context">
                    <div class="form-group mb-0">
                        <label>Pilih Event Yudisium</label>
                        <select class="form-control" name="period_id" onchange="this.form.submit()">
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected($selectedPeriodId == $period->id)>
                                    {{ $period->name }}{{ $period->is_active ? ' - aktif' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="order-note">Urutan menentukan posisi kategori di daftar/dropdown undangan. Kategori baru otomatis ditempatkan paling bawah.</div>
                    </div>
                    @if ($selectedPeriod)
                        <a class="btn btn-outline-secondary" href="{{ route('undangan.show', $selectedPeriod->slug) }}" target="_blank" rel="noopener">
                            <i class="fa fa-eye"></i> Preview Event
                        </a>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

@if (! $selectedPeriod)
    <div class="row clearfix">
        <div class="col-lg-12">
            <div class="alert alert-warning mb-0">Buat event yudisium terlebih dahulu sebelum menambahkan kategori undangan.</div>
        </div>
    </div>
@else
    <div class="row clearfix">
        <div class="col-lg-12">
            <div class="card categories-table-card">
                <div class="header">
                    <h2>Daftar Kategori</h2>
                    <ul class="header-dropdown">
                        <li><span class="badge badge-info">{{ $categories->count() }} kategori</span></li>
                    </ul>
                </div>
                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="categoriesTable">
                            <thead>
                                <tr>
                                    <th>Urutan</th>
                                    <th>Kategori</th>
                                    <th>Akses</th>
                                    <th>Konfirmasi</th>
                                    <th>Link</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($categories as $category)
                                    @php
                                        $categoryInviteUrl = route('home', ['event' => $selectedPeriod->slug, 'to' => $category->slug]);
                                        $categoryShareUrl = $category->usesPrivateAccess()
                                            ? $categoryInviteUrl.'&ref=TOKEN_UNIK'
                                            : $categoryInviteUrl;
                                        $categoryLinkId = 'category-link-'.$category->id;
                                    @endphp
                                    <tr>
                                        <td>{{ $category->sort_order ?: $loop->iteration }}</td>
                                        <td>
                                            <div class="category-title">{{ $category->title }}</div>
                                            <div class="category-muted">{{ $category->recipient_label }}</div>
                                            <div class="category-muted">Slug otomatis: {{ $category->slug }}</div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $category->usesPrivateAccess() ? 'badge-warning' : 'badge-info' }}">
                                                {{ $category->access_mode_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $category->requiresRsvp() ? 'badge-success' : 'badge-secondary' }}">
                                                {{ $category->requiresRsvp() ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span id="{{ $categoryLinkId }}" class="category-link">{{ $categoryShareUrl }}</span>
                                        </td>
                                        <td class="text-right">
                                            <div class="category-actions">
                                                @unless ($category->usesPrivateAccess())
                                                    <a class="btn btn-outline-secondary btn-sm" href="{{ $categoryInviteUrl }}" target="_blank" rel="noopener">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                @endunless
                                                <button class="btn btn-outline-secondary btn-sm" type="button" data-copy="{{ $categoryLinkId }}">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                                <a class="btn btn-primary btn-sm" href="{{ route('admin.categories.edit', $category) }}">
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
@endif
@endsection

@push('scripts')
    <script src="{{ asset('assets-template/assets/bundles/datatablescripts.bundle.js') }}"></script>
    <script src="{{ asset('assets-template/assets/vendor/jquery-datatable/dataTables.bootstrap4.min.js') }}"></script>
    @include('admin.partials.copy-script')
    <script>
        $(function () {
            var table = $('#categoriesTable');
            if (!table.length) return;

            table.DataTable({
                pageLength: 10,
                order: [[0, 'asc']],
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ kategori',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ kategori',
                    infoEmpty: 'Belum ada kategori',
                    infoFiltered: '(difilter dari _MAX_ kategori)',
                    zeroRecords: 'Kategori tidak ditemukan',
                    paginate: {
                        first: 'Pertama',
                        last: 'Terakhir',
                        next: 'Berikutnya',
                        previous: 'Sebelumnya'
                    }
                },
                columnDefs: [
                    { orderable: false, targets: [4, 5] }
                ]
            });
        });
    </script>
@endpush
