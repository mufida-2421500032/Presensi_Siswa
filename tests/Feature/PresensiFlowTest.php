<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Mapel;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiFlowTest extends TestCase
{
    use RefreshDatabase;

    private function guruDenganJadwal(): array
    {
        $user = User::factory()->create(['role' => User::ROLE_GURU]);
        $guru = Guru::factory()->create(['user_id' => $user->id]);
        $mapel = Mapel::factory()->create(['nama' => 'Matematika', 'kode' => 'MAT-01']);
        $jadwal = Jadwal::factory()->create([
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'kelas' => 'X IPA 1',
            'hari' => 'Senin',
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
        ]);

        return [$user, $guru, $jadwal];
    }

    public function test_guru_bisa_menyimpan_presensi_kelasnya(): void
    {
        [$user, $guru, $jadwal] = $this->guruDenganJadwal();

        $siswa1 = Siswa::factory()->create(['kelas' => 'X IPA 1']);
        $siswa2 = Siswa::factory()->create(['kelas' => 'X IPA 1']);
        $siswaLain = Siswa::factory()->create(['kelas' => 'X IPA 2']);

        $this->actingAs($user)
            ->post(route('guru.presensi.store'), [
                'jadwal_id' => $jadwal->id,
                'tanggal' => '2026-10-12',
                'status' => [
                    $siswa1->id => 'hadir',
                    $siswa2->id => 'sakit',
                    $siswaLain->id => 'hadir',
                ],
                'keterangan' => [
                    $siswa2->id => 'Demam',
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('presensi', [
            'jadwal_id' => $jadwal->id,
            'siswa_id' => $siswa1->id,
            'tanggal' => '2026-10-12',
            'status' => 'hadir',
        ]);

        $this->assertDatabaseHas('presensi', [
            'jadwal_id' => $jadwal->id,
            'siswa_id' => $siswa2->id,
            'status' => 'sakit',
            'keterangan' => 'Demam',
        ]);

        $this->assertDatabaseMissing('presensi', [
            'jadwal_id' => $jadwal->id,
            'siswa_id' => $siswaLain->id,
        ]);
    }

    public function test_menyimpan_presensi_ulang_akan_memperbarui_data_lama(): void
    {
        [$user, $guru, $jadwal] = $this->guruDenganJadwal();

        $siswa = Siswa::factory()->create(['kelas' => 'X IPA 1']);

        Presensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-10-12',
            'status' => 'alfa',
        ]);

        $this->actingAs($user)
            ->post(route('guru.presensi.store'), [
                'jadwal_id' => $jadwal->id,
                'tanggal' => '2026-10-12',
                'status' => [$siswa->id => 'hadir'],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('presensi', 1);
        $this->assertDatabaseHas('presensi', [
            'siswa_id' => $siswa->id,
            'status' => 'hadir',
        ]);
    }

    public function test_guru_tidak_bisa_mengisi_presensi_jadwal_guru_lain(): void
    {
        [$user] = $this->guruDenganJadwal();

        $guruLain = Guru::factory()->create();
        $jadwalLain = Jadwal::factory()->create([
            'guru_id' => $guruLain->id,
            'kelas' => 'X IPA 1',
            'hari' => 'Selasa',
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
        ]);

        $siswa = Siswa::factory()->create(['kelas' => 'X IPA 1']);

        $this->actingAs($user)
            ->post(route('guru.presensi.store'), [
                'jadwal_id' => $jadwalLain->id,
                'tanggal' => '2026-10-12',
                'status' => [$siswa->id => 'hadir'],
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('presensi', 0);
    }

    public function test_guru_tidak_bisa_mengubah_presensi_jadwal_guru_lain(): void
    {
        [$user] = $this->guruDenganJadwal();

        $guruLain = Guru::factory()->create();
        $jadwalLain = Jadwal::factory()->create(['guru_id' => $guruLain->id]);

        $presensi = Presensi::factory()->create(['jadwal_id' => $jadwalLain->id]);

        $this->actingAs($user)
            ->get(route('guru.presensi.edit', $presensi))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('guru.presensi.update', $presensi), ['status' => 'hadir', 'keterangan' => 'x'])
            ->assertForbidden();
    }

    public function test_guru_bisa_mengubah_dan_menghapus_presensinya(): void
    {
        [$user, $guru, $jadwal] = $this->guruDenganJadwal();

        $presensi = Presensi::factory()->create([
            'jadwal_id' => $jadwal->id,
            'status' => 'alfa',
            'tanggal' => '2026-10-12',
        ]);

        $this->actingAs($user)
            ->put(route('guru.presensi.update', $presensi), ['status' => 'izin', 'keterangan' => 'Acara keluarga'])
            ->assertRedirect();

        $this->assertDatabaseHas('presensi', [
            'id' => $presensi->id,
            'status' => 'izin',
            'keterangan' => 'Acara keluarga',
        ]);

        $this->actingAs($user)
            ->delete(route('guru.presensi.destroy', $presensi))
            ->assertRedirect();

        $this->assertDatabaseMissing('presensi', ['id' => $presensi->id]);
    }

    public function test_admin_bisa_menyimpan_presensi(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $jadwal = Jadwal::factory()->create(['kelas' => 'X IPA 1']);
        $siswa = Siswa::factory()->create(['kelas' => 'X IPA 1']);

        $this->actingAs($admin)
            ->post(route('admin.presensi.store'), [
                'jadwal_id' => $jadwal->id,
                'tanggal' => '2026-10-12',
                'status' => [$siswa->id => 'hadir'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('presensi', [
            'jadwal_id' => $jadwal->id,
            'siswa_id' => $siswa->id,
            'status' => 'hadir',
        ]);
    }
}