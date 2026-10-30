<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\LaporanOrtuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LaporanOrtuController extends Controller
{
    public function anak(Request $request): AnonymousResourceCollection
    {
        return UserResource::collection($request->user()->anak()->orderBy('name')->get());
    }

    public function laporan(User $anak, LaporanOrtuService $laporan): JsonResponse
    {
        $this->authorize('lihatLaporan', $anak);

        return response()->json(['data' => $laporan->laporan($anak)]);
    }
}
