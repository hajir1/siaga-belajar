<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun demo. Tidak ada pendaftaran bebas: semua akun dibuat lewat seeder.
 * Kata sandi semua akun demo: "siaga123".
 */
class AkunDemoSeeder extends Seeder
{
    public const SANDI = 'siaga123';

    public const SISWA = [
        'andi' => 'Andi Pratama',
        'bela' => 'Bela Safitri',
        'citra' => 'Citra Lestari',
        'dimas' => 'Dimas Saputra',
        'eka' => 'Eka Wulandari',
    ];

    public function run(): void
    {
        $guru = $this->akun('guru@siaga.test', 'Bu Rina Hartati', User::GURU);

        $kelas = Kelas::create(['nama' => 'Kelas 8A (data demo)', 'guru_id' => $guru->id]);

        $siswa = collect(self::SISWA)->map(
            fn ($nama, $kode) => $this->akun("{$kode}@siaga.test", $nama, User::SISWA)
        );
        $kelas->siswa()->attach($siswa->pluck('id'));

        $ortu = $this->akun('ortu@siaga.test', 'Ibu Sari (orang tua Andi)', User::ORTU);
        $ortu->anak()->attach($siswa['andi']->id);
    }

    private function akun(string $email, string $nama, string $peran): User
    {
        return User::create([
            'name' => $nama,
            'email' => $email,
            'password' => self::SANDI,
            'role' => $peran,
            'email_verified_at' => now(),
        ]);
    }
}
