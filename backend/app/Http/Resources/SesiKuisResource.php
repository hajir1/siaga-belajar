<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SesiKuisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mapel_id' => $this->mapel_id,
            'topik_fokus' => $this->whenLoaded('topikFokus', fn () => $this->topikFokus ? ['id' => $this->topikFokus->id, 'nama' => $this->topikFokus->nama] : null),
            'mulai' => $this->mulai?->toIso8601String(),
            'selesai' => $this->selesai?->toIso8601String(),
        ];
    }
}
