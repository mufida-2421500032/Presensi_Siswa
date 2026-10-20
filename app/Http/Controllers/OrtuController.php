<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrtuController extends Controller
{
    /**
     * Halaman pencarian data presensi siswa berdasarkan NISN untuk orang tua.
     */
    public function index(Request $request): View
    {
        $siswa = null;
        $presensis = null;
        $rekap = null;

        if ($request->filled('nisn')) {
            $request->validate([
                'nisn' => ['string', 'max:20'],
            ]);

            $siswa = Siswa::where('nisn', $request->string('nisn')->trim()->toString())->first();

            if ($siswa === null) {
                session()->flash('error', 'Data siswa dengan NISN tersebut tidak ditemukan.');
            } else {
                $presensis = Presensi::query()
                    ->lengkap()
                    ->where('presensi.siswa_id', $siswa->id)
                    ->orderByDesc('presensi.tanggal')
                    ->orderByDesc('presensi.id')
                    ->paginate(15)
                    ->withQueryString();

                $rekap = $siswa->rekapPresensi();
            }
        }

        return view('ortu.index', compact('siswa', 'presensis', 'rekap'));
    }
}