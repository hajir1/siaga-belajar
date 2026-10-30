<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanSoalRequest;
use App\Http\Resources\SoalGuruResource;
use App\Models\Mapel;
use App\Models\Soal;
use App\Services\SoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SoalController extends Controller
{
    public function __construct(private readonly SoalService $soal) {}

    public function index(Request $request, Mapel $mapel): AnonymousResourceCollection
    {
        $this->authorize('update', $mapel);

        $request->validate([
            'topik_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:draf,terbit'],
        ]);

        $soal = $mapel->soal()
            ->with(['topik:id,nama', 'pilihan'])
            ->when($request->integer('topik_id'), fn ($q, $id) => $q->where('topik_id', $id))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->paginate(20);

        return SoalGuruResource::collection($soal);
    }

    public function show(Soal $soal): SoalGuruResource
    {
        $this->authorize('kelola', $soal);

        return new SoalGuruResource($soal->load(['topik:id,nama', 'pilihan']));
    }

    public function store(SimpanSoalRequest $request): JsonResponse
    {
        $this->authorize('update', Mapel::findOrFail($request->integer('mapel_id')));

        $soal = $this->soal->buat($request->validated(), $request->user()->id);

        return (new SoalGuruResource($soal))->response()->setStatusCode(201);
    }

    public function update(SimpanSoalRequest $request, Soal $soal): SoalGuruResource
    {
        $this->authorize('kelola', $soal);
        $this->authorize('update', Mapel::findOrFail($request->integer('mapel_id')));

        return new SoalGuruResource($this->soal->ubah($soal, $request->validated()));
    }

    public function destroy(Soal $soal): JsonResponse
    {
        $this->authorize('kelola', $soal);

        $soal->delete();

        return response()->json(['message' => 'Soal dihapus.']);
    }
}
