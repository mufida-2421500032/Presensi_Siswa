<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Presensi Siswa') &middot; {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
<header class="bg-white border-bottom shadow-sm py-3 mb-4">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a class="text-decoration-none fw-semibold fs-5 text-primary" href="{{ route('home') }}">
            {{ config('app.name') }}
        </a>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary btn-sm" href="{{ route('ortu.index') }}">
                Cek Presensi Anak
            </a>
            @auth
                @php
                    $dashboardUrl = auth()->user()->isAdmin()
                        ? route('admin.dashboard')
                        : (auth()->user()->isGuru() ? route('guru.dashboard') : route('siswa.jadwal.index'));
                @endphp
                <a class="btn btn-primary btn-sm" href="{{ $dashboardUrl }}">
                    Dashboard
                </a>
            @else
                <a class="btn btn-primary btn-sm" href="{{ route('login') }}">
                    Masuk
                </a>
            @endauth
        </div>
    </div>
</header>

<main class="container pb-5">
    @include('partials.flash')
    @include('partials.errors')
    @yield('content')
</main>

<footer class="text-center text-muted small py-4">
    {{ config('app.name') }} &middot; Sistem Presensi Siswa
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>