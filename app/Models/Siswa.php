<?php

namespace App\Models;

use Database\Factories\SiswaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'nisn', 'nama', 'kelas', 'jenis_kelamin', 'tanggal_lahir', 'alamat'])]
class Siswa extends Model
{
    /** @use HasFactory<SiswaFactory> */
    use HasFactory;

    protected $table = 'siswa';

    protected function tanggalLahir(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : Carbon::parse($value),
            set: fn ($value) => $value === null ? null : Carbon::parse($value)->toDateString(),
        );
    }

    public function emailAkun(): ?string
    {
        return User::where('id', $this->user_id)->value('email');
    }

    public function rekapPresensi(): array
    {
        $counts = Presensi::where('siswa_id', $this->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $rekap = [];

        foreach (array_keys(Presensi::STATUS) as $status) {
            $rekap[$status] = (int) ($counts[$status] ?? 0);
        }

        $rekap['total'] = array_sum($rekap);
        $rekap['persentase'] = $rekap['total'] > 0
            ? (int) round($rekap['hadir'] / $rekap['total'] * 100)
            : 0;

        return $rekap;
    }

    public function jenisKelaminLabel(): string
    {
        return $this->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    }
}