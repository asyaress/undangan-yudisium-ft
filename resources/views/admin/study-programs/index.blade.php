@extends('layouts.dashboard')

@section('title', 'Program Studi')
@section('breadcrumb_parent', 'Kelola Data')
@section('breadcrumb_active', 'Program Studi')

@section('content')
@include('layouts.partials.block-header')

<div class="row clearfix">
    <div class="col-lg-12">
        <div class="card">
            <div class="header"><h2>Tambah Program Studi</h2></div>
            <div class="card-body py-3">
                <form method="post" action="{{ route('admin.study-programs.store') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-2 form-group">
                            <label>Kode</label>
                            <input class="form-control" name="code" placeholder="01" maxlength="2" required>
                        </div>
                        <div class="col-md-5 form-group">
                            <label>Nama Prodi</label>
                            <input class="form-control" name="name" placeholder="Teknik Sipil" required>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Urutan</label>
                            <input class="form-control" name="sort_order" type="number" min="0" placeholder="1">
                        </div>
                        <div class="col-md-2 form-group d-flex align-items-end">
                            <label class="fancy-checkbox mb-2">
                                <input name="is_active" type="checkbox" value="1" checked>
                                <span>Aktif</span>
                            </label>
                        </div>
                        <div class="col-md-1 form-group d-flex align-items-end">
                            <button class="btn btn-primary btn-block" type="submit">Tambah</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row clearfix">
    <div class="col-lg-12">
        <div class="card">
            <div class="header">
                <h2>Daftar Program Studi</h2>
                <ul class="header-dropdown"><li><span class="badge badge-info">{{ $studyPrograms->count() }} prodi</span></li></ul>
            </div>
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Prodi</th>
                                <th>Urutan</th>
                                <th>Mahasiswa</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($studyPrograms as $studyProgram)
                                <tr>
                                    <form method="post" action="{{ route('admin.study-programs.update', $studyProgram) }}">
                                        @csrf
                                        @method('PUT')
                                        <td style="width:110px;"><input class="form-control" name="code" value="{{ $studyProgram->code }}" maxlength="2" required></td>
                                        <td><input class="form-control" name="name" value="{{ $studyProgram->name }}" required></td>
                                        <td style="width:120px;"><input class="form-control" name="sort_order" type="number" min="0" value="{{ $studyProgram->sort_order }}"></td>
                                        <td>{{ number_format($studyProgram->participants_count) }}</td>
                                        <td>
                                            <label class="fancy-checkbox mb-0">
                                                <input name="is_active" type="checkbox" value="1" @checked($studyProgram->is_active)>
                                                <span>Aktif</span>
                                            </label>
                                        </td>
                                        <td><button class="btn btn-primary btn-sm" type="submit">Simpan</button></td>
                                    </form>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">Belum ada program studi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
