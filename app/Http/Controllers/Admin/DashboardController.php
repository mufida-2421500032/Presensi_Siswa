<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Mapel;
use App\Models\Presensi;
use App\Models\Siswa;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'guru' => Guru::count(),
            'siswa' => Siswa::count(),
            'mapel' => Mapel::count(),
            'jadwal' => Jadwal::count(),
            'presensi' => Presensi::count(),
            'hadir_hari_ini' => Presensi::whereDate('tanggal', today())->where('status', 'hadir')->count(),
        ];

        $jadwalHariIni = Jadwal::query()
            ->lengkap()
            ->where('jadwal.hari', Jadwal::hariIni())
            ->urut()
            ->get();

        $presensiTerbaru = Presensi::query()
            ->lengkap()
            ->orderByDesc('presensi.tanggal')
            ->orderByDesc('presensi.id')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'jadwalHariIni', 'presensiTerbaru'));
    }
}