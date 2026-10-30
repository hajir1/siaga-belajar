<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MapelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'mode_ketat' => $this->mode_ketat,
            'mode_pengerjaan' => $this->mode_pengerjaan,
            'proteksi_seleksi_teks' => $this->proteksi_seleksi_teks,
            'batas_waktu_per_soal' => $this->batas_waktu_per_soal,
            'jumlah_soal_terbit' => $this->whenCounted('soal'),
            'topik' => TopikResource::collection($this->whenLoaded('topik')),
        ];
    }
}
