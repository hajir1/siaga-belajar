<?php

namespace App\Services;

use App\Models\RatingSiswa;

class RatingService
{
    public function ambil(int $siswaId, int $mapelId): int
    {
        return (int) (RatingSiswa::query()
            ->where('siswa_id', $siswaId)
            ->where('mapel_id', $mapelId)
            ->value('rating') ?? EloService::RATING_AWAL_SISWA);
    }

    /** Ambil rating dengan kunci baris (dipanggil di dalam transaksi). */
    public function ambilTerkunci(int $siswaId, int $mapelId): int
    {
        $rating = RatingSiswa::query()
            ->where('siswa_id', $siswaId)
            ->where('mapel_id', $mapelId)
            ->lockForUpdate()
            ->value('rating');

        if ($rating === null) {
            RatingSiswa::query()->insertOrIgnore([
                'siswa_id' => $siswaId,
                'mapel_id' => $mapelId,
                'rating' => EloService::RATING_AWAL_SISWA,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return EloService::RATING_AWAL_SISWA;
        }

        return (int) $rating;
    }

    public function simpan(int $siswaId, int $mapelId, int $rating): void
    {
        RatingSiswa::query()
            ->where('siswa_id', $siswaId)
            ->where('mapel_id', $mapelId)
            ->update(['rating' => $rating, 'updated_at' => now()]);
    }
}
