@extends('layouts.guest')

@section('title', 'Beranda')

@section('content')
    <div class="p-4 p-md-5 mb-4 bg-primary text-white rounded-4 shadow-sm">
        <div class="py-3">
            <h1 class="display-6 fw-bold">
                Sistem Presensi Siswa
            </h1>
            <p class="lead mb-4">
                Aplikasi sederhana untuk mengelola jadwal, mata pelajaran, dan presensi siswa.
                Admin mengelola data, guru mengisi presensi, siswa dan orang tua memantau kehadiran.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('login') }}" class="btn btn-light btn-lg text-primary fw-semibold">
                    Masuk
                </a>
                <a href="{{ route('ortu.index') }}" class="btn btn-outline-light btn-lg">
                    Cek Presensi Anak
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Manajemen Data</h2>
                    <p class="text-muted mb-0">
                        Admin mengelola data guru, siswa, mata pelajaran, jadwal, dan presensi lengkap dengan CRUD.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Presensi oleh Guru</h2>
                    <p class="text-muted mb-0">
                        Guru mengisi kehadiran siswa per jadwal dan tanggal, serta melihat jadwal, mapel, dan siswa yang diajar.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Pantauan Siswa</h2>
                    <p class="text-muted mb-0">
                        Siswa dapat melihat jadwal kelas, daftar mata pelajaran, dan riwayat presensinya sendiri.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Akses Orang Tua</h2>
                    <p class="text-muted mb-0">
                        Orang tua dapat memantau kehadiran anak cukup dengan memasukkan NISN, tanpa perlu login.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection