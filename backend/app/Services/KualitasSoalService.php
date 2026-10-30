<?php

namespace App\Services;

use App\Models\Mapel;
use App\Models\Soal;
use Illuminate\Support\Facades\DB;

/**
 * Kalibrasi kualitas soal dari data jawaban siswa.
 * Hanya memberi tanda untuk ditinjau guru; tidak mengubah soal secara otomatis.
 */
class KualitasSoalService
{
    /** Minimal jawaban agar statistik cukup layak dibaca. */
    public const MIN_JAWABAN = 8;

    /** Rentang rating yang wajar per tingkat. Di luar rentang ini, tingkat yang dipilih guru mungkin kurang tepat. */
    private const RENTANG = ['mudah' => [700, 1000], 'sedang' => [925, 1175], 'sulit' => [1100, 1500]];

    /** @return array<int, array<string, mixed>> */
    public function analisis(Mapel $mapel): array
    {
        $soal = Soal::query()
            ->where('mapel_id', $mapel->id)
            ->with(['topik:id,nama', 'pilihan:id,soal_id,teks,benar'])
            ->orderBy('topik_id')
            ->orderBy('id')
            ->get();

        $statistik = DB::table('riwayat_jawaban')
            ->whereIn('soal_id', $soal->pluck('id'))
            ->groupBy('soal_id')
            ->get(['soal_id', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(CASE WHEN benar THEN 1 ELSE 0 END) as jumlah_benar')])
            ->keyBy('soal_id');

        $perPilihan = DB::table('riwayat_jawaban')
            ->whereIn('soal_id', $soal->pluck('id'))
            ->whereNotNull('pilihan_id')
            ->groupBy('pilihan_id')
            ->pluck(DB::raw('COUNT(*) as jumlah'), 'pilihan_id');

        return $soal->map(function (Soal $s) use ($statistik, $perPilihan) {
            $st = $statistik[$s->id] ?? null;
            $jumlah = (int) ($st->jumlah ?? 0);
            $persenBenar = $jumlah ? (int) round(100 * $st->jumlah_benar / $jumlah) : null;
            $jumlahSalah = $jumlah - (int) ($st->jumlah_benar ?? 0);

            $pengecoh = $s->pilihan->where('benar', false)->map(fn ($p) => [
                'id' => $p->id,
                'teks' => $p->teks,
                'dipilih' => (int) ($perPilihan[$p->id] ?? 0),
            ])->values();

            return [
                'id' => $s->id,
                'teks' => $s->teks,
                'topik' => $s->topik->nama,
                'tingkat' => $s->tingkat,
                'status' => $s->status,
                'rating_awal' => Soal::RATING_AWAL[$s->tingkat],
                'rating_sekarang' => $s->rating_elo,
                'jumlah_jawaban' => $jumlah,
                'persen_benar' => $persenBenar,
                'pengecoh' => $pengecoh,
                'catatan' => $jumlah >= self::MIN_JAWABAN ? $this->catatan($s, $persenBenar, $jumlahSalah, $pengecoh->all()) : [],
            ];
        })->values()->all();
    }

    /**
     * @param  array<int, array{teks: string, dipilih: int}>  $pengecoh
     * @return array<int, string>
     */
    private function catatan(Soal $s, int $persenBenar, int $jumlahSalah, array $pengecoh): array
    {
        $catatan = [];

        if ($persenBenar >= 90) {
            $catatan[] = 'Terlalu mudah: hampir semua siswa menjawab benar.';
        } elseif ($persenBenar <= 20) {
            $catatan[] = 'Terlalu sulit atau kunci perlu dicek: sangat sedikit yang benar.';
        }

        [$min, $max] = self::RENTANG[$s->tingkat];
        if ($s->rating_elo < $min || $s->rating_elo > $max) {
            $saran = $s->rating_elo < $min ? 'lebih mudah' : 'lebih sulit';
            $catatan[] = "Tingkat \"{$s->tingkat}\" mungkin kurang tepat: dari data, soal ini {$saran} dari perkiraan.";
        }

        if ($jumlahSalah >= 4) {
            foreach ($pengecoh as $p) {
                if ($p['dipilih'] === 0) {
                    $catatan[] = "Pengecoh \"{$p['teks']}\" tidak pernah dipilih. Pertimbangkan menggantinya.";
                }
            }
        }

        return $catatan;
    }
}
