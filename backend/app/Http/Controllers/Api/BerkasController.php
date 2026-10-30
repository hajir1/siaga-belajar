<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mapel;
use App\Services\BerkasCsvService;
use App\Services\KualitasSoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BerkasController extends Controller
{
    public function __construct(private readonly BerkasCsvService $csv) {}

    public function eksporNilai(Request $request, Mapel $mapel): Response
    {
        $this->authorize('update', $mapel);

        return $this->unduh($this->csv->keCsv($this->csv->barisNilai($request->user(), $mapel)), "nilai-{$mapel->nama}");
    }

    public function eksporSoal(Mapel $mapel): Response
    {
        $this->authorize('update', $mapel);

        return $this->unduh($this->csv->keCsv($this->csv->barisSoal($mapel)), "bank-soal-{$mapel->nama}");
    }

    public function contohSoal(): Response
    {
        return $this->unduh($this->csv->keCsv([
            BerkasCsvService::KOLOM_SOAL,
            ['Pecahan', 'Hasil dari 1/2 + 1/4 adalah ...', 'mudah', 'draf', '3/4', '2/6', '1/6', '2/4', '', ''],
        ]), 'contoh-format-soal');
    }

    public function imporSoal(Request $request, Mapel $mapel): JsonResponse
    {
        $this->authorize('update', $mapel);

        $request->validate(['berkas' => ['required', 'file', 'max:1024', 'mimes:csv,txt']], [
            'berkas.mimes' => 'Berkas harus berformat CSV.',
            'berkas.max' => 'Ukuran berkas maksimal 1 MB.',
        ]);

        $hasil = $this->csv->imporSoal($mapel, $request->file('berkas'), $request->user()->id);

        return response()->json(['data' => $hasil, 'message' => "{$hasil['jumlah']} soal berhasil diimpor."], 201);
    }

    public function kualitasSoal(Mapel $mapel, KualitasSoalService $kualitas): JsonResponse
    {
        $this->authorize('update', $mapel);

        return response()->json(['data' => $kualitas->analisis($mapel)]);
    }

    private function unduh(string $isi, string $nama): Response
    {
        $berkas = Str::slug($nama).'-'.now()->format('Ymd').'.csv';

        return response($isi, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$berkas}\"",
            'Cache-Control' => 'no-store',
        ]);
    }
}
