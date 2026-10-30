<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mapel;
use App\Services\DashboardGuruService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardGuruController extends Controller
{
    public function __invoke(Request $request, Mapel $mapel, DashboardGuruService $dashboard): JsonResponse
    {
        $this->authorize('update', $mapel);

        return response()->json(['data' => $dashboard->ringkasan($request->user(), $mapel)]);
    }
}
