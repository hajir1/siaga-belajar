<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Soal lengkap dengan kunci, hanya untuk guru pemilik mapel. */
class SoalGuruResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mapel_id' => $this->mapel_id,
            'topik_id' => $this->topik_id,
            'topik' => $this->whenLoaded('topik', fn () => $this->topik->nama),
            'teks' => $this->teks,
            'tingkat' => $this->tingkat,
            'rating_elo' => $this->rating_elo,
            'status' => $this->status,
            'pilihan' => $this->whenLoaded('pilihan', fn () => $this->pilihan->map(fn ($p) => [
                'id' => $p->id,
                'teks' => $p->teks,
                'benar' => (bool) $p->benar,
            ])->values()),
        ];
    }
}
