<?php

namespace App\Services;

use App\Models\PilihanSoal;
use App\Models\RiwayatJawaban;
use App\Models\SesiKuis;
use App\Models\Soal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Menilai jawaban di server. Klien tidak pernah tahu kunci sebelum menjawab.
 * id_unik_klien membuat pengiriman ulang (misalnya saat sinkron) tidak tercatat dua kali.
 */
class PenilaianService
{
    public function __construct(
        private readonly EloService $elo,
        private readonly RatingService $rating,
    ) {}

    /**
     * @return array{jawaban: RiwayatJawaban, duplikat: bool, rating_baru: int, pilihan_benar_id: int}
     */
    public function nilai(SesiKuis $sesi, int $soalId, ?int $pilihanId, string $idUnikKlien, ?Carbon $dijawabPada = null, bool $waktuHabis = false): array
    {
        $siswaId = $sesi->siswa_id;

        $sudahAda = RiwayatJawaban::query()
            ->where('siswa_id', $siswaId)
            ->where('id_unik_klien', $idUnikKlien)
            ->first();

        if ($sudahAda) {
            return $this->hasil($sudahAda, true, $this->rating->ambil($siswaId, $sesi->mapel_id));
        }

        $soal = Soal::query()
            ->where('id', $soalId)
            ->where('mapel_id', $sesi->mapel_id)
            ->where('status', 'terbit')
            ->first();

        if (! $soal) {
            throw ValidationException::withMessages(['soal_id' => 'Soal tidak valid.']);
        }

        // Waktu habis (Mode Ketat dengan batas waktu): dicatat sebagai jawaban salah tanpa pilihan.
        if ($waktuHabis) {
            if (! $sesi->mapel()->value('batas_waktu_per_soal')) {
                throw ValidationException::withMessages(['waktu_habis' => 'Mapel ini tidak memakai batas waktu.']);
            }
            $pilihan = null;
        } else {
            $pilihan = $pilihanId
                ? PilihanSoal::query()->where('id', $pilihanId)->where('soal_id', $soal->id)->first()
                : null;

            if (! $pilihan) {
                throw ValidationException::withMessages(['pilihan_id' => 'Pilihan tidak valid.']);
            }
        }

        $sekarang = now();
        $dijawabPada = $dijawabPada && $dijawabPada->lessThanOrEqualTo($sekarang) ? $dijawabPada : $sekarang;

        return DB::transaction(function () use ($sesi, $soal, $pilihan, $idUnikKlien, $dijawabPada, $sekarang, $siswaId, $waktuHabis) {
            $ratingSiswa = $this->rating->ambilTerkunci($siswaId, $sesi->mapel_id);
            $soal = Soal::query()->lockForUpdate()->find($soal->id);
            $benar = $pilihan !== null && (bool) $pilihan->benar;

            $baru = $this->elo->hitung($ratingSiswa, $soal->rating_elo, $benar);

            $jawaban = RiwayatJawaban::create([
                'sesi_id' => $sesi->id,
                'siswa_id' => $siswaId,
                'soal_id' => $soal->id,
                'pilihan_id' => $pilihan?->id,
                'benar' => $benar,
                'waktu_habis' => $waktuHabis,
                'rating_sebelum' => $ratingSiswa,
                'id_unik_klien' => $idUnikKlien,
                'dijawab_pada' => $dijawabPada,
                'disinkron_pada' => $sekarang,
            ]);

            $this->rating->simpan($siswaId, $sesi->mapel_id, $baru['siswa']);
            $soal->update(['rating_elo' => $baru['soal']]);

            return $this->hasil($jawaban, false, $baru['siswa']);
        });
    }

    /**
     * @return array{jawaban: RiwayatJawaban, duplikat: bool, rating_baru: int, pilihan_benar_id: int}
     */
    private function hasil(RiwayatJawaban $jawaban, bool $duplikat, int $rating): array
    {
        return [
            'jawaban' => $jawaban,
            'duplikat' => $duplikat,
            'rating_baru' => $rating,
            'pilihan_benar_id' => (int) PilihanSoal::query()
                ->where('soal_id', $jawaban->soal_id)
                ->where('benar', true)
                ->value('id'),
        ];
    }
}
