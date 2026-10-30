<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatatKejadianRequest;
use App\Http\Requests\KirimJawabanRequest;
use App\Http\Requests\MulaiSesiRequest;
use App\Http\Resources\SesiKuisResource;
use App\Http\Resources\SoalKuisResource;
use App\Models\Mapel;
use App\Models\SesiKuis;
use App\Services\NilaiTopikService;
use App\Services\PemilihanSoalService;
use App\Services\PenilaianService;
use App\Services\PeringatanDiniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class KuisController extends Controller
{
    public function __construct(
        private readonly PemilihanSoalService $pemilihan,
        private readonly PenilaianService $penilaian,
    ) {}

    public function mulai(MulaiSesiRequest $request): JsonResponse
    {
        $mapel = Mapel::findOrFail($request->integer('mapel_id'));
        $this->authorize('view', $mapel);

        $sesi = SesiKuis::create([
            'siswa_id' => $request->user()->id,
            'mapel_id' => $mapel->id,
            'topik_fokus_id' => $request->input('topik_id'),
            'mulai' => now(),
        ]);

        return (new SesiKuisResource($sesi->load('topikFokus:id,nama')))->response()->setStatusCode(201);
    }

    public function soalBerikutnya(SesiKuis $sesi): JsonResponse
    {
        $this->authorize('kerjakan', $sesi);
        abort_if($sesi->selesai !== null, 409, 'Sesi ini sudah selesai.');

        $soal = $this->pemilihan->berikutnya($sesi);

        if (! $soal) {
            return response()->json(['data' => null, 'message' => 'Belum ada soal terbit di mapel ini.']);
        }

        $soal->load(['topik:id,nama', 'pilihan']);

        // Mode Ketat: urutan pilihan diacak setiap kali soal diberikan.
        if ($sesi->mapel()->value('mode_ketat')) {
            $soal->setRelation('pilihan', $soal->pilihan->shuffle()->values());
        }

        return (new SoalKuisResource($soal))->response();
    }

    /** Mencatat pindah tab / blur (hanya jika Mode Ketat aktif). Ditampilkan ke guru sebagai "perlu dicek". */
    public function kejadian(CatatKejadianRequest $request, SesiKuis $sesi): JsonResponse
    {
        $this->authorize('kerjakan', $sesi);

        if (! $sesi->mapel()->value('mode_ketat')) {
            return response()->json(['data' => ['dicatat' => false]]);
        }

        $sesi->kejadian()->create([
            'jenis' => $request->input('jenis'),
            'durasi_detik' => $request->integer('durasi_detik'),
            'waktu' => now(),
        ]);

        return response()->json(['data' => ['dicatat' => true]], 201);
    }

    public function jawab(KirimJawabanRequest $request, SesiKuis $sesi): JsonResponse
    {
        $this->authorize('kerjakan', $sesi);

        $hasil = $this->penilaian->nilai(
            $sesi,
            $request->integer('soal_id'),
            $request->filled('pilihan_id') ? $request->integer('pilihan_id') : null,
            $request->string('id_unik_klien')->toString(),
            $request->filled('dijawab_pada') ? Carbon::parse($request->input('dijawab_pada')) : null,
            $request->boolean('waktu_habis'),
        );

        return response()->json([
            'data' => [
                'id_unik_klien' => $hasil['jawaban']->id_unik_klien,
                'benar' => $hasil['jawaban']->benar,
                'pilihan_benar_id' => $hasil['pilihan_benar_id'],
                'rating_baru' => $hasil['rating_baru'],
                'duplikat' => $hasil['duplikat'],
            ],
        ], $hasil['duplikat'] ? 200 : 201);
    }

    /**
     * Remedial otomatis: topik yang mendapat peringatan dini untuk siswa ini, di semua mapelnya.
     */
    public function rekomendasi(Request $request, NilaiTopikService $nilaiTopik, PeringatanDiniService $peringatan): JsonResponse
    {
        $siswa = $request->user();
        $mapelList = Mapel::query()->whereIn('pembuat_id', $siswa->idGuruPengajar())->orderBy('nama')->get(['id', 'nama']);
        $hasil = [];

        foreach ($mapelList as $mapel) {
            $data = $nilaiTopik->nilaiSiswa($siswa->id, $mapel->id);
            foreach ($nilaiTopik->topikMapel($mapel->id) as $topik) {
                if (! isset($data[$topik->id])) {
                    continue;
                }
                $p = $peringatan->periksa($data[$topik->id], $topik->nama);
                if ($p !== []) {
                    $hasil[] = [
                        'mapel_id' => $mapel->id,
                        'mapel' => $mapel->nama,
                        'topik_id' => $topik->id,
                        'topik' => $topik->nama,
                        'nilai' => $data[$topik->id]['nilai'],
                        'alasan' => array_column($p, 'pesan'),
                    ];
                }
            }
        }

        return response()->json(['data' => $hasil]);
    }

    public function selesai(SesiKuis $sesi): JsonResponse
    {
        $this->authorize('kerjakan', $sesi);

        if ($sesi->selesai === null) {
            $sesi->update(['selesai' => now()]);
        }

        $jawaban = $sesi->jawaban()->get(['benar']);

        return response()->json(['data' => [
            'sesi' => new SesiKuisResource($sesi),
            'jumlah_soal' => $jawaban->count(),
            'jumlah_benar' => $jawaban->where('benar', true)->count(),
        ]]);
    }
}
