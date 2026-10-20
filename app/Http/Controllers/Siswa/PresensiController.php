<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresensiController extends Controller
{
    /**
     * Riwayat presensi siswa beserta rekap kehadiran.
     */
    public function index(Request $request): View
    {
        $request->validate([
            'status' => ['nullable', 'string'],
        ]);

        $siswa = $request->user()->profilSiswa();

        abort_if($siswa === null, 403, 'Akun ini belum terhubung dengan data siswa.');

        $presensis = Presensi::query()
            ->lengkap()
            ->where('presensi.siswa_id', $siswa->id)
            ->when(
                $request->filled('status') && array_key_exists($request->input('status'), Presensi::STATUS),
                fn ($query) => $query->where('presensi.status', $request->input('status')),
            )
            ->orderByDesc('presensi.tanggal')
            ->orderByDesc('presensi.id')
            ->paginate(15)
            ->withQueryString();

        $rekap = $siswa->rekapPresensi();

        return view('siswa.presensi.index', compact('siswa', 'presensis', 'rekap'));
    }
}