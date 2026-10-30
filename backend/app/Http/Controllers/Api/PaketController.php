<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SinkronPaketRequest;
use App\Models\Mapel;
use App\Services\PaketService;
use Illuminate\Http\JsonResponse;

class PaketController extends Controller
{
    public function __construct(private readonly PaketService $paket) {}

    /** Estimasi ukuran sebelum siswa mengunduh. */
    public function info(Mapel $mapel): JsonResponse
    {
        $this->authorize('view', $mapel);

        return response()->json(['data' => $this->paket->info($mapel)]);
    }

    /** Paket soal tanpa kunci jawaban. */
    public function unduh(Mapel $mapel): JsonResponse
    {
        $this->authorize('view', $mapel);

        return response()->json(['data' => $this->paket->paket($mapel)]);
    }

    public function sinkron(SinkronPaketRequest $request): JsonResponse
    {
        $mapel = Mapel::findOrFail($request->integer('mapel_id'));
        $this->authorize('view', $mapel);

        return response()->json(['data' => $this->paket->sinkron($request->user(), $mapel, $request->validated())]);
    }
}
