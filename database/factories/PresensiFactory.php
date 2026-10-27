<?php

namespace Database\Factories;

use App\Models\Jadwal;
use App\Models\Presensi;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Presensi>
 */
class PresensiFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jadwal_id' => Jadwal::factory(),
            'siswa_id' => Siswa::factory(),
            'tanggal' => fake()->dateTimeBetween('-1 month', 'now'),
            'status' => fake()->randomElement(array_keys(Presensi::STATUS)),
            'keterangan' => null,
        ];
    }
}