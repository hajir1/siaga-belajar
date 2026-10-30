<?php

namespace App\Services;

use App\Http\Requests\SimpanSoalRequest;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Ekspor nilai dan bank soal ke CSV, serta impor soal dari CSV.
 * Format soal: topik;teks;tingkat;status;benar;salah1;salah2;salah3;salah4;salah5
 * Pemisah titik koma agar langsung terbaca di Excel berbahasa Indonesia (koma juga diterima saat impor).
 */
class BerkasCsvService
{
    public const KOLOM_SOAL = ['topik', 'teks', 'tingkat', 'status', 'benar', 'salah1', 'salah2', 'salah3', 'salah4', 'salah5'];

    public const MAKS_BARIS = 300;

    public function __construct(
        private readonly DashboardGuruService $dashboard,
        private readonly SoalService $soal,
    ) {}

    /** @return array<int, array<int, string|int|null>> */
    public function barisNilai(User $guru, Mapel $mapel): array
    {
        $d = $this->dashboard->ringkasan($guru, $mapel);
        $baris = [['Siswa', ...collect($d['topik'])->pluck('nama')->all(), 'Peringatan', 'Pindah tab (perlu dicek)']];

        foreach ($d['siswa'] as $s) {
            $baris[] = [
                $s['nama'],
                ...array_map(fn ($t) => $t['nilai'], $s['topik']),
                implode(' | ', array_column($s['peringatan'], 'pesan')),
                $s['kejadian_pindah_tab']['jumlah'],
            ];
        }

        return $baris;
    }

    /** @return array<int, array<int, string>> */
    public function barisSoal(Mapel $mapel): array
    {
        $baris = [self::KOLOM_SOAL];

        $mapel->soal()->with(['topik:id,nama', 'pilihan'])->orderBy('topik_id')->orderBy('id')->each(function ($s) use (&$baris) {
            $benar = $s->pilihan->firstWhere('benar', true);
            $salah = $s->pilihan->where('benar', false)->pluck('teks')->values()->all();
            $baris[] = array_pad([$s->topik->nama, $s->teks, $s->tingkat, $s->status, $benar?->teks ?? '', ...$salah], count(self::KOLOM_SOAL), '');
        });

        return $baris;
    }

    /** CSV dengan BOM UTF-8 agar Excel membaca huruf seperti "°" dan "²" dengan benar. */
    public function keCsv(array $baris): string
    {
        $h = fopen('php://temp', 'r+');
        fwrite($h, "\xEF\xBB\xBF");
        foreach ($baris as $b) {
            fputcsv($h, array_map(fn ($v) => $this->amankanSel($v), $b), ';', '"', '');
        }
        rewind($h);
        $isi = stream_get_contents($h);
        fclose($h);

        return $isi;
    }

    /**
     * Impor semua-atau-tidak-sama-sekali: jika ada baris yang salah, tidak ada yang disimpan.
     *
     * @return array{jumlah: int, topik_baru: array<int, string>}
     */
    public function imporSoal(Mapel $mapel, UploadedFile $berkas, int $guruId): array
    {
        $baris = $this->baca($berkas);
        $galat = [];
        $siap = [];
        $aturan = (new SimpanSoalRequest)->rules();
        unset($aturan['mapel_id'], $aturan['topik_id']);

        foreach ($baris as $i => $kolom) {
            $nomor = $i + 2; // baris 1 = judul kolom
            $topik = trim($kolom['topik'] ?? '');
            $pilihan = [['teks' => trim($kolom['benar'] ?? ''), 'benar' => true]];
            foreach (['salah1', 'salah2', 'salah3', 'salah4', 'salah5'] as $k) {
                if (trim($kolom[$k] ?? '') !== '') {
                    $pilihan[] = ['teks' => trim($kolom[$k]), 'benar' => false];
                }
            }
            $data = [
                'teks' => trim($kolom['teks'] ?? ''),
                'tingkat' => strtolower(trim($kolom['tingkat'] ?? '')),
                'status' => strtolower(trim($kolom['status'] ?? '')) ?: 'draf',
                'pilihan' => $pilihan,
            ];

            $v = Validator::make($data, $aturan);
            if ($topik === '' || mb_strlen($topik) > 100) {
                $galat[] = "Baris {$nomor}: kolom topik wajib diisi (maks. 100 karakter).";
            }
            if ($v->fails()) {
                $galat[] = "Baris {$nomor}: ".implode(' ', $v->errors()->all());
            }
            $siap[] = [...$data, 'topik' => $topik];
        }

        if ($galat !== []) {
            throw ValidationException::withMessages(['berkas' => array_slice($galat, 0, 20)]);
        }

        return DB::transaction(function () use ($mapel, $siap, $guruId) {
            $topikBaru = [];
            foreach ($siap as $s) {
                $topik = $mapel->topik()->firstOrCreate(['nama' => $s['topik']]);
                if ($topik->wasRecentlyCreated) {
                    $topikBaru[] = $topik->nama;
                }
                $this->soal->buat([...$s, 'mapel_id' => $mapel->id, 'topik_id' => $topik->id], $guruId);
            }

            return ['jumlah' => count($siap), 'topik_baru' => array_values(array_unique($topikBaru))];
        });
    }

    /** @return array<int, array<string, string>> */
    private function baca(UploadedFile $berkas): array
    {
        $isi = (string) file_get_contents($berkas->getRealPath());
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);
        if (! mb_check_encoding($isi, 'UTF-8')) {
            $isi = mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
        }

        $barisMentah = preg_split('/\r\n|\n|\r/', trim($isi));
        $pemisah = substr_count($barisMentah[0] ?? '', ';') >= substr_count($barisMentah[0] ?? '', ',') ? ';' : ',';

        $h = fopen('php://temp', 'r+');
        fwrite($h, $isi);
        rewind($h);
        $judul = array_map(fn ($k) => strtolower(trim((string) $k)), fgetcsv($h, null, $pemisah, '"', '') ?: []);

        if (array_diff(['topik', 'teks', 'tingkat', 'benar', 'salah1'], $judul) !== []) {
            fclose($h);
            throw ValidationException::withMessages(['berkas' => 'Judul kolom tidak sesuai. Unduh contoh format lalu isi ulang.']);
        }

        $hasil = [];
        while (($b = fgetcsv($h, null, $pemisah, '"', '')) !== false) {
            if ($b === [null] || implode('', $b) === '') {
                continue;
            }
            $b = array_map(fn ($v) => $this->pulihkanSel((string) $v), $b);
            $hasil[] = array_combine($judul, array_pad(array_slice($b, 0, count($judul)), count($judul), ''));
            if (count($hasil) > self::MAKS_BARIS) {
                fclose($h);
                throw ValidationException::withMessages(['berkas' => 'Maksimal '.self::MAKS_BARIS.' soal per berkas.']);
            }
        }
        fclose($h);

        if ($hasil === []) {
            throw ValidationException::withMessages(['berkas' => 'Berkas tidak berisi soal.']);
        }

        return $hasil;
    }

    /**
     * Cegah CSV/formula injection di Excel: sel yang diawali = + @ atau tab diberi awalan petik.
     * Tanda minus hanya diamankan jika bukan angka, agar jawaban seperti "-3" tetap utuh.
     */
    private function amankanSel(mixed $v): string
    {
        $v = (string) $v;
        if ($v !== '' && (in_array($v[0], ['=', '+', '@', "\t", "\r"], true) || ($v[0] === '-' && ! is_numeric($v)))) {
            return "'".$v;
        }

        return $v;
    }

    private function pulihkanSel(string $v): string
    {
        return preg_match("/^'[=+@\\-\t]/", $v) ? substr($v, 1) : $v;
    }
}
