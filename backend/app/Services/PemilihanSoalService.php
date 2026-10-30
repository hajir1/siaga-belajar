<?php

namespace App\Services;

use App\Models\RiwayatJawaban;
use App\Models\SesiKuis;
use App\Models\Soal;
use Illuminate\Support\Collection;

/**
 * Memilih soal berikutnya untuk kuis adaptif:
 * 1. Soal yang dijawab salah di sesi ini muncul lagi setelah beberapa soal lain.
 * 2. Topik lemah lebih sering dipilih.
 * 3. Dalam topik, pilih soal yang ratingnya paling dekat dengan rating siswa.
 * 4. Soal yang sama tidak pernah muncul dua kali berturut-turut.
 * Sesi remedial (topik_fokus_id terisi) hanya memakai satu topik dan mulai dari soal sedikit lebih mudah.
 */
class PemilihanSoalService
{
    /** Jarak minimal (jumlah soal lain) sebelum soal yang salah diulang. */
    public const JEDA_ULANG = 3;

    /** Soal yang baru dikerjakan di sesi ini tidak dipilih lagi (kecuali untuk pengulangan). */
    private const JENDELA_HINDARI = 8;

    /** Peluang memilih topik terlemah dibanding topik lain. */
    private const PELUANG_TOPIK_LEMAH = 0.7;

    /** Remedial: target rating sedikit di bawah rating siswa agar siswa membangun kepercayaan diri. */
    private const SELISIH_REMEDIAL = 100;

    public function __construct(
        private readonly RatingService $rating,
        private readonly NilaiTopikService $nilaiTopik,
    ) {}

    public function berikutnya(SesiKuis $sesi): ?Soal
    {
        $jawabanSesi = RiwayatJawaban::query()
            ->where('sesi_id', $sesi->id)
            ->orderByDesc('id')
            ->get(['id', 'soal_id', 'benar']);

        $terakhirId = $jawabanSesi->first()?->soal_id;

        if ($ulang = $this->soalUntukDiulang($jawabanSesi, $sesi->mapel_id)) {
            return $ulang;
        }

        $pool = Soal::query()
            ->where('mapel_id', $sesi->mapel_id)
            ->where('status', 'terbit')
            ->when($sesi->topik_fokus_id, fn ($q, $topikId) => $q->where('topik_id', $topikId))
            ->when($terakhirId, fn ($q) => $q->where('id', '!=', $terakhirId))
            ->get(['id', 'topik_id', 'rating_elo']);

        if ($pool->isEmpty()) {
            return null;
        }

        $baruDikerjakan = $jawabanSesi->take(self::JENDELA_HINDARI)->pluck('soal_id')->all();
        $segar = $pool->whereNotIn('id', $baruDikerjakan);
        if ($segar->isNotEmpty()) {
            $pool = $segar;
        }

        $topikId = $this->pilihTopik($sesi, $pool);
        $kandidat = $pool->where('topik_id', $topikId);
        $ratingSiswa = $this->rating->ambil($sesi->siswa_id, $sesi->mapel_id)
            - ($sesi->topik_fokus_id ? self::SELISIH_REMEDIAL : 0);

        $terpilih = $kandidat
            ->sortBy(fn ($s) => abs($s->rating_elo - $ratingSiswa))
            ->take(3)
            ->random();

        return Soal::query()->find($terpilih->id);
    }

    /**
     * Soal yang salah dijawab di sesi ini, sudah lewat JEDA_ULANG soal, dan belum dijawab benar setelahnya.
     *
     * @param  Collection<int, RiwayatJawaban>  $jawabanSesi  urut terbaru dulu
     */
    private function soalUntukDiulang(Collection $jawabanSesi, int $mapelId): ?Soal
    {
        $sudahDilihat = [];
        $jumlahMuncul = $jawabanSesi->countBy('soal_id');

        foreach ($jawabanSesi as $posisi => $jawaban) {
            if (isset($sudahDilihat[$jawaban->soal_id])) {
                continue;
            }
            $sudahDilihat[$jawaban->soal_id] = true;

            // $posisi = jumlah soal yang dijawab setelah jawaban ini.
            // Diulang paling banyak sekali agar siswa tidak terjebak di satu soal.
            if (! $jawaban->benar && $posisi >= self::JEDA_ULANG && $jumlahMuncul[$jawaban->soal_id] < 2) {
                $soal = Soal::query()
                    ->where('id', $jawaban->soal_id)
                    ->where('mapel_id', $mapelId)
                    ->where('status', 'terbit')
                    ->first();

                if ($soal) {
                    return $soal;
                }
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Soal>  $pool
     */
    private function pilihTopik(SesiKuis $sesi, Collection $pool): int
    {
        $topikTersedia = $pool->pluck('topik_id')->unique()->values();

        if ($topikTersedia->count() === 1) {
            return (int) $topikTersedia->first();
        }

        $nilai = $this->nilaiTopik->nilaiSiswa($sesi->siswa_id, $sesi->mapel_id);

        // Topik yang belum pernah dikerjakan dianggap perlu dicoba (nilai 50).
        $terlemah = $topikTersedia
            ->sortBy(fn ($id) => $nilai[$id]['nilai'] ?? 50)
            ->first();

        if (mt_rand() / mt_getrandmax() < self::PELUANG_TOPIK_LEMAH) {
            return (int) $terlemah;
        }

        return (int) $topikTersedia->random();
    }
}
