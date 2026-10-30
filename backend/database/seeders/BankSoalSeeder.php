<?php

namespace Database\Seeders;

use App\Models\Mapel;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Database\Seeder;

class BankSoalSeeder extends Seeder
{
    public function run(): void
    {
        $guru = User::query()->where('role', User::GURU)->firstOrFail();
        $bank = require __DIR__.'/data/bank_soal.php';

        mt_srand(2026);

        foreach ($bank as $namaMapel => $daftarTopik) {
            $mapel = Mapel::create([
                'nama' => $namaMapel,
                'pembuat_id' => $guru->id,
                // IPA memakai Mode Ketat agar dashboard menampilkan contoh "perlu dicek".
                // Matematika sengaja mati agar bisa dinyalakan saat demo.
                'mode_ketat' => $namaMapel === 'IPA',
                'mode_pengerjaan' => 'online',
                'proteksi_seleksi_teks' => true,
                'batas_waktu_per_soal' => null,
            ]);

            foreach ($daftarTopik as $namaTopik => $daftarSoal) {
                $topik = $mapel->topik()->create(['nama' => $namaTopik]);

                foreach ($daftarSoal as [$teks, $tingkat, $pilihan]) {
                    $soal = Soal::create([
                        'mapel_id' => $mapel->id,
                        'topik_id' => $topik->id,
                        'teks' => $teks,
                        'tingkat' => $tingkat,
                        'rating_elo' => Soal::RATING_AWAL[$tingkat],
                        'status' => 'terbit',
                        'pembuat_id' => $guru->id,
                    ]);

                    $baris = array_map(
                        fn ($teksPilihan, $i) => ['teks' => $teksPilihan, 'benar' => $i === 0],
                        $pilihan,
                        array_keys($pilihan),
                    );
                    shuffle($baris);
                    $soal->pilihan()->createMany($baris);
                }
            }
        }
    }
}
