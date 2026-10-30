<?php

namespace App\Services;

use App\Models\Soal;
use Illuminate\Support\Facades\DB;

class SoalService
{
    /** @param  array<string, mixed>  $data  hasil validasi SimpanSoalRequest */
    public function buat(array $data, int $pembuatId): Soal
    {
        return DB::transaction(function () use ($data, $pembuatId) {
            $soal = Soal::create([
                'mapel_id' => $data['mapel_id'],
                'topik_id' => $data['topik_id'],
                'teks' => $data['teks'],
                'tingkat' => $data['tingkat'],
                'rating_elo' => Soal::RATING_AWAL[$data['tingkat']],
                'status' => $data['status'],
                'pembuat_id' => $pembuatId,
            ]);

            $this->simpanPilihan($soal, $data['pilihan']);

            return $soal->load(['topik:id,nama', 'pilihan']);
        });
    }

    /** @param  array<string, mixed>  $data */
    public function ubah(Soal $soal, array $data): Soal
    {
        return DB::transaction(function () use ($soal, $data) {
            $atribut = collect($data)->only(['mapel_id', 'topik_id', 'teks', 'tingkat', 'status'])->all();

            // Rating hanya di-reset jika guru mengganti tingkat kesulitan.
            if ($data['tingkat'] !== $soal->tingkat) {
                $atribut['rating_elo'] = Soal::RATING_AWAL[$data['tingkat']];
            }

            $soal->update($atribut);

            // Pilihan diganti seluruhnya; riwayat lama tetap aman karena pilihan_id di riwayat bisa null.
            $soal->pilihan()->delete();
            $this->simpanPilihan($soal, $data['pilihan']);

            return $soal->load(['topik:id,nama', 'pilihan']);
        });
    }

    /** @param  array<int, array{teks: string, benar: mixed}>  $pilihan */
    private function simpanPilihan(Soal $soal, array $pilihan): void
    {
        foreach ($pilihan as $p) {
            $soal->pilihan()->create([
                'teks' => $p['teks'],
                'benar' => filter_var($p['benar'], FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
