@extends('layouts.app')

@section('title', 'Presensi')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h3 mb-0">Presensi</h1>
        <a href="{{ route('guru.presensi.create') }}" class="btn btn-primary">
            Input Presensi
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('guru.presensi.index') }}" class="row g-2">
                <div class="col-md-5">
                    <select name="jadwal_id" class="form-select">
                        <option value="">Semua Jadwal</option>
                        @foreach ($jadwals as $jadwal)
                            <option value="{{ $jadwal->id }}" @selected((int) request('jadwal_id') === $jadwal->id)>
                                {{ $jadwal->kelas }} &mdash; {{ $jadwal->hari }} {{ $jadwal->jam() }} &mdash; {{ $jadwal->mapel_nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" name="tanggal" value="{{ request('tanggal') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        @foreach (\App\Models\Presensi::STATUS as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-outline-primary" type="submit">Cari</button>
                </div>
                <div class="col-md-auto">
                    <a href="{{ route('guru.presensi.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px">#</th>
                        <th>Tanggal</th>
                        <th>Kelas</th>
                        <th>Mapel</th>
                        <th>Jam</th>
                        <th>Siswa</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($presensis as $presensi)
                        <tr>
                            <td>{{ $presensis->firstItem() + $loop->index }}</td>
                            <td>{{ $presensi->tanggal->format('d/m/Y') }}</td>
                            <td><span class="badge text-bg-light text-dark">{{ $presensi->jadwal_kelas }}</span></td>
                            <td>{{ $presensi->mapel_nama }}</td>
                            <td>{{ $presensi->jadwalJam() }}</td>
                            <td>{{ $presensi->siswa_nama }}</td>
                            <td>
                                <span class="badge text-bg-{{ $presensi->badgeStatus() }}">{{ $presensi->statusLabel() }}</span>
                            </td>
                            <td>{{ $presensi->keterangan ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('guru.presensi.edit', $presensi) }}" class="btn btn-sm btn-outline-warning">
                                    Ubah
                                </a>
                                <form method="POST" action="{{ route('guru.presensi.destroy', $presensi) }}" class="d-inline"
                                      onsubmit="return confirm('Hapus catatan presensi {{ $presensi->siswa_nama }} tanggal {{ $presensi->tanggal->format('d/m/Y') }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Belum ada data presensi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $presensis->links() }}
        </div>
    </div>
@endsection