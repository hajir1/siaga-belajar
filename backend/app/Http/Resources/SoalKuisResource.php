<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Soal untuk siswa. TIDAK memuat kolom "benar" maupun rating soal.
 */
class SoalKuisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teks' => $this->teks,
            'topik' => $this->topik?->nama,
            'pilihan' => $this->pilihan->map(fn ($p) => ['id' => $p->id, 'teks' => $p->teks])->values(),
        ];
    }
}
