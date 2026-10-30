<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanMapelRequest;
use App\Http\Requests\SimpanTopikRequest;
use App\Http\Resources\MapelResource;
use App\Http\Resources\TopikResource;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MapelController extends Controller
{
    /** Guru: mapel buatannya. Siswa: mapel dari guru yang mengajarnya. */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $pembuat = $user->isGuru() ? [$user->id] : $user->idGuruPengajar();

        $mapel = Mapel::query()
            ->whereIn('pembuat_id', $pembuat)
            ->withCount(['soal' => fn ($q) => $q->where('status', 'terbit')])
            ->with(['topik' => fn ($q) => $q->orderBy('id')])
            ->orderBy('nama')
            ->get();

        return MapelResource::collection($mapel);
    }

    public function show(Mapel $mapel): MapelResource
    {
        $this->authorize('view', $mapel);

        return new MapelResource($mapel->load(['topik' => fn ($q) => $q->orderBy('id')])
            ->loadCount(['soal' => fn ($q) => $q->where('status', 'terbit')]));
    }

    public function store(SimpanMapelRequest $request): MapelResource
    {
        $this->authorize('create', Mapel::class);

        $mapel = Mapel::create([...$request->validated(), 'pembuat_id' => $request->user()->id]);

        return new MapelResource($mapel->load('topik'));
    }

    public function update(SimpanMapelRequest $request, Mapel $mapel): MapelResource
    {
        $this->authorize('update', $mapel);

        $mapel->update($request->validated());

        return new MapelResource($mapel->load('topik'));
    }

    public function storeTopik(SimpanTopikRequest $request, Mapel $mapel): TopikResource
    {
        $this->authorize('update', $mapel);

        return new TopikResource($mapel->topik()->create($request->validated()));
    }
}
