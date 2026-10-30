<?php

namespace App\Services;

/**
 * Perhitungan rating Elo untuk siswa dan soal.
 * Siswa yang menjawab benar soal yang lebih sulit dari ratingnya naik lebih banyak.
 */
class EloService
{
    public const RATING_AWAL_SISWA = 1000;

    private const K_SISWA = 32;

    private const K_SOAL = 16;

    private const MIN = 100;

    private const MAX = 3000;

    /** Peluang siswa menjawab benar (0..1). */
    public function peluangBenar(int $ratingSiswa, int $ratingSoal): float
    {
        return 1 / (1 + 10 ** (($ratingSoal - $ratingSiswa) / 400));
    }

    /**
     * @return array{siswa: int, soal: int}
     */
    public function hitung(int $ratingSiswa, int $ratingSoal, bool $benar): array
    {
        $harapan = $this->peluangBenar($ratingSiswa, $ratingSoal);
        $hasil = $benar ? 1 : 0;

        return [
            'siswa' => $this->batasi((int) round($ratingSiswa + self::K_SISWA * ($hasil - $harapan))),
            'soal' => $this->batasi((int) round($ratingSoal - self::K_SOAL * ($hasil - $harapan))),
        ];
    }

    private function batasi(int $nilai): int
    {
        return max(self::MIN, min(self::MAX, $nilai));
    }
}
