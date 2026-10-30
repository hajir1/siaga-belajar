<?php

namespace App\Services;

use App\Models\Mapel;
use App\Models\RiwayatJawaban;
use App\Models\User;

/**
 * Laporan sederhana untuk orang tua: ringkasan kalimat dan saran pendampingan per topik.
 */
class LaporanOrtuService
{
    public function __construct(
        private readonly NilaiTopikService $nilaiTopik,
        private readonly PeringatanDiniService $peringatan,
    ) {}

    /** @return array<string, mixed> */
    public function laporan(User $anak): array
    {
        $guruIds = $anak->idGuruPengajar();
        $mapelList = Mapel::query()->whereIn('pembuat_id', $guruIds)->orderBy('nama')->get();

        $perMapel = $mapelList->map(fn (Mapel $m) => $this->laporanMapel($anak, $m))->values();

        $seminggu = RiwayatJawaban::query()
            ->where('siswa_id', $anak->id)
            ->where('dijawab_pada', '>=', now()->subDays(7))
            ->count();

        return [
            'anak' => ['id' => $anak->id, 'nama' => $anak->name],
            'soal_7_hari' => $seminggu,
            'mapel' => $perMapel,
        ];
    }

    /** @return array<string, mixed> */
    private function laporanMapel(User $anak, Mapel $mapel): array
    {
        $data = $this->nilaiTopik->nilaiSiswa($anak->id, $mapel->id);
        $topik = $this->nilaiTopik->topikMapel($mapel->id);
        $namaDepan = explode(' ', $anak->name)[0];

        $baris = $topik->filter(fn ($t) => isset($data[$t->id]))->map(function ($t) use ($data, $namaDepan) {
            $d = $data[$t->id];
            $status = $this->status($d['nilai']);

            return [
                'topik_id' => $t->id,
                'topik' => $t->nama,
                'nilai' => $d['nilai'],
                'jumlah_jawaban' => $d['jumlah'],
                'status' => $status,
                'peringatan' => $this->peringatan->periksa($d, $t->nama) !== [],
                'saran' => $this->saran($status, $t->nama, $namaDepan),
            ];
        })->values();

        return [
            'mapel_id' => $mapel->id,
            'mapel' => $mapel->nama,
            'ringkasan' => $this->ringkasan($namaDepan, $mapel->nama, $baris->all()),
            'topik' => $baris,
        ];
    }

    private function status(int $nilai): string
    {
        return match (true) {
            $nilai >= 75 => 'baik',
            $nilai >= PeringatanDiniService::BATAS_NILAI => 'cukup',
            default => 'perlu_bantuan',
        };
    }

    private function saran(string $status, string $topik, string $nama): string
    {
        return match ($status) {
            'baik' => "{$nama} sudah paham {$topik}. Beri pujian dan minta {$nama} menjelaskan satu soal kepada Anda.",
            'cukup' => "{$nama} mulai paham {$topik}. Ajak latihan 10 menit dua kali seminggu dan tanyakan bagian yang masih sulit.",
            default => "{$nama} masih kesulitan di {$topik}. Dampingi saat latihan, ajak membaca ulang catatan, dan sampaikan ke guru agar dibantu.",
        };
    }

    /** @param  array<int, array{topik: string, status: string}>  $baris */
    private function ringkasan(string $nama, string $mapel, array $baris): string
    {
        if ($baris === []) {
            return "{$nama} belum mengerjakan latihan {$mapel}.";
        }

        $kuat = array_column(array_filter($baris, fn ($b) => $b['status'] === 'baik'), 'topik');
        $lemah = array_column(array_filter($baris, fn ($b) => $b['status'] === 'perlu_bantuan'), 'topik');

        $kalimat = "Di {$mapel}, ";
        $kalimat .= $kuat ? "{$nama} sudah baik di ".implode(', ', $kuat).'. ' : "{$nama} masih terus berlatih. ";
        $kalimat .= $lemah ? 'Perlu didampingi di '.implode(', ', $lemah).'.' : 'Belum ada topik yang mengkhawatirkan.';

        return $kalimat;
    }
}
