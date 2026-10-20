<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Presensi;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PresensiController extends Controller
{
    /**
     * Daftar presensi dengan filter jadwal, tanggal, dan status.
     */
    public function index(Request $request): View
    {
        $request->validate([
            'jadwal_id' => ['nullable', 'integer'],
            'tanggal' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $presensis = Presensi::query()
            ->lengkap()
            ->when($request->filled('jadwal_id'), fn ($query) => $query->where('presensi.jadwal_id', $request->integer('jadwal_id')))
            ->when($request->filled('tanggal'), fn ($query) => $query->whereDate('presensi.tanggal', $request->date('tanggal')))
            ->when($request->filled('status'), fn ($query) => $query->where('presensi.status', $request->input('status')))
            ->orderByDesc('presensi.tanggal')
            ->orderByDesc('presensi.id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.presensi.index', [
            'presensis' => $presensis,
            'jadwals' => $this->jadwalList(),
        ]);
    }

    /**
     * Form input presensi per jadwal dan tanggal.
     */
    public function create(Request $request): View
    {
        $request->validate([
            'jadwal_id' => ['nullable', 'integer'],
            'tanggal' => ['nullable', 'date'],
        ]);

        $tanggal = ($request->filled('tanggal') ? $request->date('tanggal') : today())->toDateString();

        $selectedJadwal = null;
        $siswas = collect();
        $presensiAda = collect();

        if ($request->filled('jadwal_id')) {
            $selectedJadwal = Jadwal::query()
                ->lengkap()
                ->where('jadwal.id', $request->integer('jadwal_id'))
                ->first();

            if ($selectedJadwal !== null) {
                $siswas = Siswa::where('kelas', $selectedJadwal->kelas)->orderBy('nama')->get();

                $presensiAda = Presensi::where('jadwal_id', $selectedJadwal->id)
                    ->whereDate('tanggal', $tanggal)
                    ->get()
                    ->keyBy('siswa_id');
            }
        }

        return view('admin.presensi.create', [
            'jadwals' => $this->jadwalList(),
            'selectedJadwal' => $selectedJadwal,
            'siswas' => $siswas,
            'presensiAda' => $presensiAda,
            'tanggal' => $tanggal,
        ]);
    }

    /**
     * Simpan presensi satu jadwal untuk satu tanggal.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jadwal_id' => ['required', 'exists:jadwal,id'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'array', 'min:1'],
            'status.*' => ['required', Rule::in(array_keys(Presensi::STATUS))],
            'keterangan' => ['nullable', 'array'],
            'keterangan.*' => ['nullable', 'string', 'max:255'],
        ]);

        $jadwal = Jadwal::findOrFail($data['jadwal_id']);

        $siswaIds = Siswa::where('kelas', $jadwal->kelas)
            ->whereIn('id', array_keys($data['status']))
            ->pluck('id');

        foreach ($siswaIds as $siswaId) {
            Presensi::updateOrCreate(
                [
                    'jadwal_id' => $jadwal->id,
                    'siswa_id' => $siswaId,
                    'tanggal' => $data['tanggal'],
                ],
                [
                    'status' => $data['status'][$siswaId],
                    'keterangan' => $data['keterangan'][$siswaId] ?? null,
                ],
            );
        }

        return redirect()
            ->route('admin.presensi.index', ['jadwal_id' => $jadwal->id, 'tanggal' => $data['tanggal']])
            ->with('success', 'Presensi berhasil disimpan.');
    }

    /**
     * Form ubah satu catatan presensi.
     */
    public function edit(Presensi $presensi): View
    {
        $presensi = Presensi::query()
            ->lengkap()
            ->where('presensi.id', $presensi->id)
            ->firstOrFail();

        return view('admin.presensi.edit', compact('presensi'));
    }

    /**
     * Perbarui satu catatan presensi.
     */
    public function update(Request $request, Presensi $presensi): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Presensi::STATUS))],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $presensi->update($data);

        return redirect()
            ->route('admin.presensi.index', ['jadwal_id' => $presensi->jadwal_id, 'tanggal' => $presensi->tanggal->toDateString()])
            ->with('success', 'Presensi berhasil diperbarui.');
    }

    /**
     * Hapus satu catatan presensi.
     */
    public function destroy(Presensi $presensi): RedirectResponse
    {
        $presensi->delete();

        return redirect()
            ->route('admin.presensi.index')
            ->with('success', 'Presensi berhasil dihapus.');
    }

    /**
     * Daftar jadwal untuk dropdown.
     *
     * @return Collection<int, Jadwal>
     */
    private function jadwalList(): Collection
    {
        return Jadwal::query()
            ->lengkap()
            ->orderBy('jadwal.kelas')
            ->urut()
            ->get();
    }
}