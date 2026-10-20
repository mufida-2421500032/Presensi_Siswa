@extends('layouts.app')

@section('title', 'Ubah Presensi')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 fw-semibold">Ubah Catatan Presensi</div>
                <div class="card-body">
                    <dl class="row mb-4">
                        <dt class="col-sm-4">Siswa</dt>
                        <dd class="col-sm-8">{{ $presensi->siswa_nama }} (<code>{{ $presensi->siswa_nisn }}</code>)</dd>

                        <dt class="col-sm-4">Jadwal</dt>
                        <dd class="col-sm-8">
                            {{ $presensi->jadwal_kelas }} &mdash; {{ $presensi->mapel_nama }}
                            ({{ $presensi->jadwal_hari }}, {{ $presensi->jadwalJam() }})
                        </dd>

                        <dt class="col-sm-4">Guru</dt>
                        <dd class="col-sm-8">{{ $presensi->guru_nama }}</dd>

                        <dt class="col-sm-4">Tanggal</dt>
                        <dd class="col-sm-8">{{ $presensi->tanggal->format('d/m/Y') }}</dd>
                    </dl>

                    <form method="POST" action="{{ route('admin.presensi.update', $presensi) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                @foreach (\App\Models\Presensi::STATUS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $presensi->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <input type="text" class="form-control" id="keterangan" name="keterangan"
                                   value="{{ old('keterangan', $presensi->keterangan) }}" placeholder="Opsional">
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">
                                Simpan
                            </button>
                            <a href="{{ route('admin.presensi.index') }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection