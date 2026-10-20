<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Guru;
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
     * Daftar presensi yang pernah diinput guru, dengan filter.
     */
    public function index(Request $request): View
    {
        $request->validate([
            'jadwal_id' => ['nullable', 'integer'],
            'tanggal' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $guru = $request->user()->profilGuru();

        abort_if($guru === null, 403, 'Akun ini belum terhubung dengan data guru.');

        $presensis = Presensi::query()
            ->lengkap()
            ->where('jadwal.guru_id', $guru->id)
            ->when($request->filled('jadwal_id'), fn ($query) => $query->where('presensi.jadwal_id', $request->integer('jadwal_id')))
            ->when($request->filled('tanggal'), fn ($query) => $query->whereDate('presensi.tanggal', $request->date('tanggal')))
            ->when($request->filled('status'), fn ($query) => $query->where('presensi.status', $request->input('status')))
            ->orderByDesc('presensi.tanggal')
            ->orderByDesc('presensi.id')
            ->paginate(20)
            ->withQueryString();

        return view('guru.presensi.index', [
            'presensis' => $presensis,
            'jadwals' => $this->jadwalList($guru),
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

        $guru = $request->user()->profilGuru();

        abort_if($guru === null, 403, 'Akun ini belum terhubung dengan data guru.');

        $tanggal = ($request->filled('tanggal') ? $request->date('tanggal') : today())->toDateString();

        $selectedJadwal = null;
        $siswas = collect();
        $presensiAda = collect();

        if ($request->filled('jadwal_id')) {
            $selectedJadwal = Jadwal::query()
                ->lengkap()
                ->where('jadwal.id', $request->integer('jadwal_id'))
                ->where('jadwal.guru_id', $guru->id)
                ->first();

            if ($selectedJadwal !== null) {
                $siswas = Siswa::where('kelas', $selectedJadwal->kelas)->orderBy('nama')->get();

                $presensiAda = Presensi::where('jadwal_id', $selectedJadwal->id)
                    ->whereDate('tanggal', $tanggal)
                    ->get()
                    ->keyBy('siswa_id');
            }
        }

        return view('guru.presensi.create', [
            'jadwals' => $this->jadwalList($guru),
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
        $guru = $request->user()->profilGuru();

        abort_if($guru === null, 403, 'Akun ini belum terhubung dengan data guru.');

        $data = $request->validate([
            'jadwal_id' => ['required', 'exists:jadwal,id'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'array', 'min:1'],
            'status.*' => ['required', Rule::in(array_keys(Presensi::STATUS))],
            'keterangan' => ['nullable', 'array'],
            'keterangan.*' => ['nullable', 'string', 'max:255'],
        ]);

        $jadwal = Jadwal::where('id', $data['jadwal_id'])
            ->where('guru_id', $guru->id)
            ->firstOrFail();

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
            ->route('guru.presensi.index', ['jadwal_id' => $jadwal->id, 'tanggal' => $data['tanggal']])
            ->with('success', 'Presensi berhasil disimpan.');
    }

    /**
     * Form ubah satu catatan presensi milik guru.
     */
    public function edit(Request $request, Presensi $presensi): View
    {
        $this->pastikanMilikGuru($request, $presensi);

        $presensi = Presensi::query()
            ->lengkap()
            ->where('presensi.id', $presensi->id)
            ->firstOrFail();

        return view('guru.presensi.edit', compact('presensi'));
    }

    /**
     * Perbarui satu catatan presensi milik guru.
     */
    public function update(Request $request, Presensi $presensi): RedirectResponse
    {
        $this->pastikanMilikGuru($request, $presensi);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Presensi::STATUS))],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $presensi->update($data);

        return redirect()
            ->route('guru.presensi.index', ['jadwal_id' => $presensi->jadwal_id, 'tanggal' => $presensi->tanggal->toDateString()])
            ->with('success', 'Presensi berhasil diperbarui.');
    }

    /**
     * Hapus satu catatan presensi milik guru.
     */
    public function destroy(Request $request, Presensi $presensi): RedirectResponse
    {
        $this->pastikanMilikGuru($request, $presensi);

        $presensi->delete();

        return redirect()
            ->route('guru.presensi.index')
            ->with('success', 'Presensi berhasil dihapus.');
    }

    /**
     * Pastikan catatan presensi berasal dari jadwal milik guru yang login.
     */
    private function pastikanMilikGuru(Request $request, Presensi $presensi): void
    {
        $guru = $request->user()->profilGuru();

        abort_if($guru === null, 403, 'Akun ini belum terhubung dengan data guru.');

        $pemilik = Jadwal::where('id', $presensi->jadwal_id)->value('guru_id');

        abort_if($pemilik !== $guru->id, 403, 'Anda tidak berhak mengubah presensi ini.');
    }

    /**
     * Daftar jadwal milik guru untuk dropdown.
     *
     * @return Collection<int, Jadwal>
     */
    private function jadwalList(Guru $guru): Collection
    {
        return Jadwal::query()
            ->lengkap()
            ->where('jadwal.guru_id', $guru->id)
            ->orderBy('jadwal.kelas')
            ->urut()
            ->get();
    }
}