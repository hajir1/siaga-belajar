<?php

namespace App\Services;

/**
 * Aturan peringatan dini per siswa per topik:
 * - "di_bawah_batas": nilai topik di bawah 55 (minimal MIN_JAWABAN jawaban agar tidak terlalu dini).
 * - "turun_beruntun": nilai pertemuan turun 3 kali berturut-turut (butuh 4 pertemuan terakhir).
 */
class PeringatanDiniService
{
    public const BATAS_NILAI = 55;

    public const TURUN_BERUNTUN = 3;

    public const MIN_JAWABAN = 3;

    /**
     * @param  array{nilai: int, jumlah: int, per_pertemuan: array<int, array{nilai: int}>}  $data
     * @return array<int, array{jenis: string, pesan: string}>
     */
    public function periksa(array $data, string $namaTopik): array
    {
        $peringatan = [];

        if ($data['jumlah'] >= self::MIN_JAWABAN && $data['nilai'] < self::BATAS_NILAI) {
            $peringatan[] = [
                'jenis' => 'di_bawah_batas',
                'pesan' => "Nilai {$namaTopik} {$data['nilai']}, di bawah ".self::BATAS_NILAI.'.',
            ];
        }

        if ($this->turunBeruntun(array_column($data['per_pertemuan'], 'nilai'))) {
            $peringatan[] = [
                'jenis' => 'turun_beruntun',
                'pesan' => "Nilai {$namaTopik} turun ".self::TURUN_BERUNTUN.' pertemuan berturut-turut.',
            ];
        }

        return $peringatan;
    }

    /** @param  array<int>  $nilaiPertemuan  urut dari yang terlama */
    public function turunBeruntun(array $nilaiPertemuan): bool
    {
        $akhir = array_slice($nilaiPertemuan, -(self::TURUN_BERUNTUN + 1));

        if (count($akhir) < self::TURUN_BERUNTUN + 1) {
            return false;
        }

        for ($i = 1; $i < count($akhir); $i++) {
            if ($akhir[$i] >= $akhir[$i - 1]) {
                return false;
            }
        }

        return true;
    }
}
