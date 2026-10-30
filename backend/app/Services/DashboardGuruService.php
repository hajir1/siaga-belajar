<?php

namespace App\Services;

use App\Models\Mapel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardGuruService
{
    public function __construct(
        private readonly NilaiTopikService $nilaiTopik,
        private readonly PeringatanDiniService $peringatan,
        private readonly RatingService $rating,
    ) {}

    /**
     * Matriks siswa x topik untuk satu mapel, berisi nilai dan peringatan.
     *
     * @return array<string, mixed>
     */
    public function ringkasan(User $guru, Mapel $mapel): array
    {
        $siswa = User::query()
            ->where('role', User::SISWA)
            ->whereHas('kelas', fn ($q) => $q->where('guru_id', $guru->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $topik = $this->nilaiTopik->topikMapel($mapel->id);
        $data = $this->nilaiTopik->hitung($siswa->pluck('id')->all(), $mapel->id);
        $kejadian = $this->jumlahKejadian($siswa->pluck('id')->all(), $mapel->id);

        $barisSiswa = $siswa->map(function (User $s) use ($topik, $data, $mapel, $kejadian) {
            $perTopik = [];
            $semuaPeringatan = [];

            foreach ($topik as $t) {
                $d = $data[$s->id][$t->id] ?? null;
                $p = $d ? $this->peringatan->periksa($d, $t->nama) : [];
                $semuaPeringatan = [...$semuaPeringatan, ...array_map(fn ($x) => $x + ['topik_id' => $t->id], $p)];

                $perTopik[] = [
                    'topik_id' => $t->id,
                    'nilai' => $d['nilai'] ?? null,
                    'jumlah_jawaban' => $d['jumlah'] ?? 0,
                    'tren' => $d ? array_column(array_slice($d['per_pertemuan'], -5), 'nilai') : [],
                    'peringatan' => $p,
                ];
            }

            return [
                'id' => $s->id,
                'nama' => $s->name,
                'rating' => $this->rating->ambil($s->id, $mapel->id),
                'topik' => $perTopik,
                'peringatan' => $semuaPeringatan,
                'kejadian_pindah_tab' => $kejadian[$s->id] ?? ['jumlah' => 0, 'durasi_detik' => 0],
            ];
        })->values();

        $ringkasanKelas = $this->ringkasanKelas($topik, $barisSiswa->all());

        return [
            'mapel' => ['id' => $mapel->id, 'nama' => $mapel->nama, 'mode_ketat' => $mapel->mode_ketat],
            'ringkasan_kelas' => $ringkasanKelas,
            'kalimat_kelas' => $this->kalimatKelas($ringkasanKelas),
            'topik' => $topik->map(fn ($t) => ['id' => $t->id, 'nama' => $t->nama])->values(),
            'siswa' => $barisSiswa,
            'jumlah_siswa_berperingatan' => $barisSiswa->filter(fn ($s) => $s['peringatan'] !== [])->count(),
        ];
    }

    /**
     * Peta kelemahan kelas per topik: rata-rata dan persentase siswa yang nilainya di bawah batas.
     *
     * @param  Collection<int, object>  $topik
     * @param  array<int, array<string, mixed>>  $barisSiswa
     * @return array<int, array<string, mixed>>
     */
    private function ringkasanKelas(Collection $topik, array $barisSiswa): array
    {
        return $topik->map(function ($t) use ($barisSiswa) {
            $nilai = collect($barisSiswa)
                ->map(fn ($s) => collect($s['topik'])->firstWhere('topik_id', $t->id))
                ->filter(fn ($x) => $x && $x['nilai'] !== null && $x['jumlah_jawaban'] >= PeringatanDiniService::MIN_JAWABAN)
                ->pluck('nilai');

            $lemah = $nilai->filter(fn ($n) => $n < PeringatanDiniService::BATAS_NILAI)->count();

            return [
                'topik_id' => $t->id,
                'topik' => $t->nama,
                'jumlah_siswa' => $nilai->count(),
                'rata_rata' => $nilai->isEmpty() ? null : (int) round($nilai->avg()),
                'jumlah_lemah' => $lemah,
                'persen_lemah' => $nilai->isEmpty() ? 0 : (int) round(100 * $lemah / $nilai->count()),
            ];
        })->values()->all();
    }

    /**
     * Kalimat ringkas, misalnya "40% kelas lemah di Pecahan (2 dari 5 siswa)."
     *
     * @param  array<int, array<string, mixed>>  $ringkasan
     * @return array<int, string>
     */
    private function kalimatKelas(array $ringkasan): array
    {
        $lemah = collect($ringkasan)->filter(fn ($r) => $r['jumlah_lemah'] > 0)->sortByDesc('persen_lemah')->values();

        if ($lemah->isEmpty()) {
            $berdata = collect($ringkasan)->filter(fn ($r) => $r['rata_rata'] !== null);

            return $berdata->isEmpty()
                ? ['Belum ada cukup data latihan di kelas ini.']
                : ['Tidak ada topik dengan siswa di bawah '.PeringatanDiniService::BATAS_NILAI.'. Kelas dalam kondisi baik.'];
        }

        $kalimat = $lemah->take(3)->map(fn ($r) => "{$r['persen_lemah']}% kelas lemah di {$r['topik']} ({$r['jumlah_lemah']} dari {$r['jumlah_siswa']} siswa).")->all();

        $terkuat = collect($ringkasan)->filter(fn ($r) => $r['rata_rata'] !== null)->sortByDesc('rata_rata')->first();
        if ($terkuat && $terkuat['jumlah_lemah'] === 0) {
            $kalimat[] = "Topik paling dikuasai: {$terkuat['topik']} (rata-rata {$terkuat['rata_rata']}).";
        }

        return $kalimat;
    }

    /**
     * @param  array<int>  $siswaIds
     * @return array<int, array{jumlah: int, durasi_detik: int}>
     */
    private function jumlahKejadian(array $siswaIds, int $mapelId): array
    {
        if ($siswaIds === []) {
            return [];
        }

        return DB::table('kejadian_kuis as e')
            ->join('sesi_kuis as k', 'k.id', '=', 'e.sesi_id')
            ->whereIn('k.siswa_id', $siswaIds)
            ->where('k.mapel_id', $mapelId)
            ->groupBy('k.siswa_id')
            ->get(['k.siswa_id', DB::raw('COUNT(*) as jumlah'), DB::raw('SUM(e.durasi_detik) as durasi')])
            ->mapWithKeys(fn ($r) => [(int) $r->siswa_id => ['jumlah' => (int) $r->jumlah, 'durasi_detik' => (int) $r->durasi]])
            ->all();
    }
}
