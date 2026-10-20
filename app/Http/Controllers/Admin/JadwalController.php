<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Mapel;
use App\Models\Presensi;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'hari' => ['nullable', 'string'],
            'kelas' => ['nullable', 'string'],
        ]);

        $jadwals = Jadwal::query()
            ->lengkap()
            ->when($request->filled('hari'), fn ($query) => $query->where('jadwal.hari', $request->input('hari')))
            ->when($request->filled('kelas'), fn ($query) => $query->where('jadwal.kelas', $request->input('kelas')))
            ->urut()
            ->paginate(15)
            ->withQueryString();

        return view('admin.jadwal.index', [
            'jadwals' => $jadwals,
            'kelasList' => $this->kelasList(),
        ]);
    }

    public function create(): View
    {
        return view('admin.jadwal.form', [
            'jadwal' => null,
            'mapelList' => Mapel::orderBy('nama')->get(),
            'guruList' => Guru::orderBy('nama')->get(),
            'kelasList' => $this->kelasList(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        Jadwal::create($data);

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Jadwal $jadwal): View
    {
        return view('admin.jadwal.form', [
            'jadwal' => $jadwal,
            'mapelList' => Mapel::orderBy('nama')->get(),
            'guruList' => Guru::orderBy('nama')->get(),
            'kelasList' => $this->kelasList(),
        ]);
    }

    public function update(Request $request, Jadwal $jadwal): RedirectResponse
    {
        $data = $this->validatedData($request, $jadwal);

        $jadwal->update($data);

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Jadwal $jadwal): RedirectResponse
    {
        if (Presensi::where('jadwal_id', $jadwal->id)->exists()) {
            return back()->with('error', 'Jadwal tidak bisa dihapus karena sudah memiliki data presensi.');
        }

        $jadwal->delete();

        return redirect()->route('admin.jadwal.index')->with('success', 'Jadwal berhasil dihapus.');
    }

    /**
     * Validasi data jadwal termasuk cek bentrok guru dan kelas.
     *
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Jadwal $jadwal = null): array
    {
        $data = $request->validate([
            'mapel_id' => ['required', 'exists:mapel,id'],
            'guru_id' => ['required', 'exists:guru,id'],
            'kelas' => ['required', 'string', 'max:50'],
            'hari' => ['required', Rule::in(Jadwal::HARI)],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
        ]);

        $bentrokGuru = Jadwal::query()
            ->where('guru_id', $data['guru_id'])
            ->where('hari', $data['hari'])
            ->where('jam_mulai', $data['jam_mulai'])
            ->when($jadwal !== null, fn ($query) => $query->whereKeyNot($jadwal->id))
            ->exists();

        if ($bentrokGuru) {
            throw ValidationException::withMessages([
                'jam_mulai' => 'Guru sudah memiliki jadwal lain pada hari dan jam tersebut.',
            ]);
        }

        $bentrokKelas = Jadwal::query()
            ->where('kelas', $data['kelas'])
            ->where('hari', $data['hari'])
            ->where('jam_mulai', $data['jam_mulai'])
            ->when($jadwal !== null, fn ($query) => $query->whereKeyNot($jadwal->id))
            ->exists();

        if ($bentrokKelas) {
            throw ValidationException::withMessages([
                'jam_mulai' => 'Kelas sudah memiliki jadwal lain pada hari dan jam tersebut.',
            ]);
        }

        return $data;
    }

    /**
     * Daftar kelas yang tersedia (dari data siswa dan jadwal).
     *
     * @return Collection<int, string>
     */
    private function kelasList(): Collection
    {
        return Siswa::query()
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->merge(Jadwal::query()->distinct()->pluck('kelas'))
            ->unique()
            ->sort()
            ->values();
    }
}