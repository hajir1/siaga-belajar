<?php

namespace App\Services;

use App\Models\Topik;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menghitung nilai per topik dari riwayat jawaban, plus tren per pertemuan (sesi kuis).
 *
 * Nilai topik = persentase benar dari JENDELA jawaban terakhir di topik itu (0-100).
 * Nilai pertemuan = persentase benar di satu sesi untuk topik itu.
 */
class NilaiTopikService
{
    public const JENDELA = 10;

    /**
     * Data mentah per siswa per topik.
     *
     * @param  array<int>  $siswaIds
     * @return array<int, array<int, array{nilai: int, jumlah: int, per_pertemuan: array<int, array{sesi_id: int, tanggal: string, nilai: int}>}>>
     *                                                                                                                                             [siswa_id][topik_id]
     */
    public function hitung(array $siswaIds, int $mapelId): array
    {
        if ($siswaIds === []) {
            return [];
        }

        $baris = DB::table('riwayat_jawaban as r')
            ->join('soal as s', 's.id', '=', 'r.soal_id')
            ->join('sesi_kuis as k', 'k.id', '=', 'r.sesi_id')
            ->whereIn('r.siswa_id', $siswaIds)
            ->where('s.mapel_id', $mapelId)
            ->orderBy('k.mulai')
            ->orderBy('r.dijawab_pada')
            ->orderBy('r.id')
            ->get(['r.siswa_id', 's.topik_id', 'r.sesi_id', 'k.mulai', 'r.benar']);

        $hasil = [];

        foreach ($baris->groupBy('siswa_id') as $siswaId => $perSiswa) {
            foreach ($perSiswa->groupBy('topik_id') as $topikId => $jawaban) {
                $terakhir = $jawaban->slice(-self::JENDELA);

                $perPertemuan = $jawaban->groupBy('sesi_id')->map(fn (Collection $j, $sesiId) => [
                    'sesi_id' => (int) $sesiId,
                    'tanggal' => substr((string) $j->first()->mulai, 0, 10),
                    'nilai' => $this->persen($j),
                ])->values()->all();

                $hasil[(int) $siswaId][(int) $topikId] = [
                    'nilai' => $this->persen($terakhir),
                    'jumlah' => $jawaban->count(),
                    'per_pertemuan' => $perPertemuan,
                ];
            }
        }

        return $hasil;
    }

    /**
     * @return array<int, array{nilai: int, jumlah: int, per_pertemuan: array}>
     */
    public function nilaiSiswa(int $siswaId, int $mapelId): array
    {
        return $this->hitung([$siswaId], $mapelId)[$siswaId] ?? [];
    }

    /**
     * @return Collection<int, Topik>
     */
    public function topikMapel(int $mapelId): Collection
    {
        return Topik::query()->where('mapel_id', $mapelId)->orderBy('id')->get(['id', 'nama']);
    }

    /** @param  Collection<int, object>  $jawaban */
    private function persen(Collection $jawaban): int
    {
        $n = $jawaban->count();

        return $n === 0 ? 0 : (int) round(100 * $jawaban->filter(fn ($j) => (bool) $j->benar)->count() / $n);
    }
}
