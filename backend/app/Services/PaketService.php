<?php

namespace App\Services;

use App\Models\KejadianKuis;
use App\Models\Mapel;
use App\Models\SesiKuis;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Mode Paket untuk koneksi tidak stabil.
 * Paket berisi soal terbit satu mapel TANPA kunci jawaban. Jawaban dinilai server saat sinkron.
 */
class PaketService
{
    public function __construct(private readonly PenilaianService $penilaian) {}

    /** @return array<string, mixed> */
    public function paket(Mapel $mapel): array
    {
        $soal = Soal::query()
            ->where('mapel_id', $mapel->id)
            ->where('status', 'terbit')
            ->with(['topik:id,nama', 'pilihan:id,soal_id,teks'])
            ->get()
            ->shuffle()
            ->map(fn (Soal $s) => [
                'id' => $s->id,
                'teks' => $s->teks,
                'topik' => $s->topik->nama,
                'pilihan' => $s->pilihan->shuffle()->map(fn ($p) => ['id' => $p->id, 'teks' => $p->teks])->values(),
            ])
            ->values();

        return [
            'mapel' => [
                'id' => $mapel->id,
                'nama' => $mapel->nama,
                'mode_ketat' => $mapel->mode_ketat,
                'proteksi_seleksi_teks' => $mapel->proteksi_seleksi_teks,
                'batas_waktu_per_soal' => $mapel->batas_waktu_per_soal,
            ],
            'dibuat_pada' => now()->toIso8601String(),
            'soal' => $soal,
        ];
    }

    /** @return array{jumlah_soal: int, perkiraan_ukuran_byte: int} */
    public function info(Mapel $mapel): array
    {
        $paket = $this->paket($mapel);

        return [
            'jumlah_soal' => count($paket['soal']),
            'perkiraan_ukuran_byte' => strlen((string) json_encode(['data' => $paket])),
        ];
    }

    /**
     * Menyimpan dan menilai satu sesi paket. Aman diulang: sesi, jawaban, dan kejadian memakai id unik dari perangkat.
     *
     * @param  array<string, mixed>  $data  hasil validasi SinkronPaketRequest
     * @return array<string, mixed>
     */
    public function sinkron(User $siswa, Mapel $mapel, array $data): array
    {
        $sesi = SesiKuis::query()->firstOrCreate(
            ['siswa_id' => $siswa->id, 'id_unik_klien' => $data['id_sesi_klien']],
            ['mapel_id' => $mapel->id, 'mulai' => $this->waktuAman($data['mulai'])],
        );

        if ($sesi->mapel_id !== $mapel->id) {
            throw ValidationException::withMessages(['id_sesi_klien' => 'Sesi ini milik mapel lain.']);
        }

        $hasil = [];
        $rating = null;

        foreach ($data['jawaban'] as $j) {
            try {
                $r = $this->penilaian->nilai(
                    $sesi,
                    (int) $j['soal_id'],
                    isset($j['pilihan_id']) ? (int) $j['pilihan_id'] : null,
                    $j['id_unik_klien'],
                    Carbon::parse($j['dijawab_pada']),
                    (bool) ($j['waktu_habis'] ?? false),
                );
                $rating = $r['rating_baru'];
                $hasil[] = [
                    'id_unik_klien' => $j['id_unik_klien'],
                    'status' => $r['duplikat'] ? 'duplikat' : 'tersimpan',
                    'benar' => $r['jawaban']->benar,
                    'pilihan_benar_id' => $r['pilihan_benar_id'],
                ];
            } catch (ValidationException) {
                // Misalnya soal sudah dihapus/ditarik guru sejak paket diunduh.
                $hasil[] = ['id_unik_klien' => $j['id_unik_klien'], 'status' => 'ditolak', 'benar' => null, 'pilihan_benar_id' => null];
            }
        }

        if ($sesi->mapel()->value('mode_ketat')) {
            foreach ($data['kejadian'] ?? [] as $k) {
                KejadianKuis::query()->firstOrCreate(
                    ['sesi_id' => $sesi->id, 'id_unik_klien' => $k['id_unik_klien']],
                    ['jenis' => $k['jenis'], 'durasi_detik' => $k['durasi_detik'], 'waktu' => $this->waktuAman($k['waktu'])],
                );
            }
        }

        if (! empty($data['selesai']) && $sesi->selesai === null) {
            $sesi->update(['selesai' => $this->waktuAman($data['selesai'])]);
        }

        return ['sesi_id' => $sesi->id, 'rating_baru' => $rating, 'hasil' => $hasil];
    }

    /** Waktu dari perangkat tidak boleh di masa depan. */
    private function waktuAman(string $waktu): Carbon
    {
        $t = Carbon::parse($waktu);

        return $t->isFuture() ? now() : $t;
    }
}
