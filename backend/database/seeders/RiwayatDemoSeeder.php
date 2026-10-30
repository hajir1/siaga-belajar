<?php

namespace Database\Seeders;

use App\Models\Mapel;
use App\Models\PilihanSoal;
use App\Models\SesiKuis;
use App\Models\User;
use App\Services\PenilaianService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Riwayat contoh (DATA DEMO) selama 6 pertemuan mingguan.
 * Setiap pertemuan: 4 soal per topik. Angka di profil = jumlah jawaban benar dari 4 soal.
 * Profil dirancang agar dashboard menampilkan kasus peringatan dini yang nyata:
 * - Andi: Pecahan turun 3 pertemuan berturut-turut dan nilainya di bawah 55.
 * - Citra: Aljabar dan Gerak dan Gaya konsisten di bawah 55.
 * - Eka: Suhu dan Kalor turun 3 pertemuan berturut-turut.
 */
class RiwayatDemoSeeder extends Seeder
{
    private const PERTEMUAN = 6;

    private const SOAL_PER_TOPIK = 4;

    /** [kode siswa => [mapel => ['_' => bawaan, topik => [6 angka]]]] */
    private const PROFIL = [
        'andi' => [
            'Matematika' => ['_' => [3, 3, 3, 3, 3, 3], 'Pecahan' => [4, 4, 4, 3, 2, 1]],
            'IPA' => ['_' => [3, 3, 2, 3, 3, 3]],
        ],
        'bela' => [
            'Matematika' => ['_' => [4, 3, 4, 4, 3, 4]],
            'IPA' => ['_' => [4, 4, 3, 4, 4, 4]],
        ],
        'citra' => [
            'Matematika' => ['_' => [3, 3, 3, 2, 3, 3], 'Aljabar' => [2, 1, 2, 1, 2, 1]],
            'IPA' => ['_' => [3, 3, 3, 3, 2, 3], 'Gerak dan Gaya' => [2, 2, 1, 2, 1, 2]],
        ],
        'dimas' => [
            'Matematika' => ['_' => [3, 2, 3, 3, 2, 3]],
            'IPA' => ['_' => [3, 2, 3, 3, 2, 3]],
        ],
        'eka' => [
            'Matematika' => ['_' => [3, 3, 4, 3, 3, 3]],
            'IPA' => ['_' => [3, 3, 3, 3, 3, 3], 'Suhu dan Kalor' => [3, 4, 4, 3, 2, 1]],
        ],
    ];

    public function run(PenilaianService $penilaian): void
    {
        mt_srand(8);

        $mapelList = Mapel::query()->with(['topik.soal' => fn ($q) => $q->where('status', 'terbit')])->get();

        foreach (self::PROFIL as $kode => $perMapel) {
            $siswa = User::query()->where('email', "{$kode}@siaga.test")->firstOrFail();

            foreach ($mapelList as $mapel) {
                $profil = $perMapel[$mapel->nama] ?? ['_' => array_fill(0, self::PERTEMUAN, 3)];

                for ($p = 0; $p < self::PERTEMUAN; $p++) {
                    $mulai = now()->startOfWeek()->subWeeks(self::PERTEMUAN - $p)->addDays(mt_rand(0, 3))->setTime(9, mt_rand(0, 50));
                    $sesi = SesiKuis::create(['siswa_id' => $siswa->id, 'mapel_id' => $mapel->id, 'mulai' => $mulai]);

                    $antrian = [];
                    foreach ($mapel->topik as $topik) {
                        $jumlahBenar = ($profil[$topik->nama] ?? $profil['_'])[$p];
                        $soal = $topik->soal->shuffle()->take(self::SOAL_PER_TOPIK)->values();
                        $hasil = array_merge(array_fill(0, $jumlahBenar, true), array_fill(0, self::SOAL_PER_TOPIK - $jumlahBenar, false));
                        shuffle($hasil);
                        foreach ($soal as $i => $s) {
                            $antrian[] = [$s, $hasil[$i]];
                        }
                    }
                    shuffle($antrian);

                    foreach ($antrian as $urutan => [$soal, $benar]) {
                        $pilihan = PilihanSoal::query()->where('soal_id', $soal->id)->where('benar', $benar)->inRandomOrder()->first();
                        $penilaian->nilai($sesi, $soal->id, $pilihan->id, (string) Str::uuid(), $mulai->copy()->addSeconds(40 * ($urutan + 1)));
                    }

                    $sesi->update(['selesai' => $mulai->copy()->addSeconds(40 * (count($antrian) + 1))]);

                    // Contoh kejadian pindah tab (Mode Ketat aktif di IPA) di pertemuan terakhir Dimas.
                    if ($kode === 'dimas' && $mapel->mode_ketat && $p === self::PERTEMUAN - 1) {
                        $sesi->kejadian()->createMany([
                            ['jenis' => 'pindah_tab', 'durasi_detik' => 48, 'waktu' => $mulai->copy()->addMinutes(3)],
                            ['jenis' => 'pindah_tab', 'durasi_detik' => 95, 'waktu' => $mulai->copy()->addMinutes(9)],
                            ['jenis' => 'blur', 'durasi_detik' => 12, 'waktu' => $mulai->copy()->addMinutes(11)],
                        ]);
                    }
                }
            }
        }
    }
}
