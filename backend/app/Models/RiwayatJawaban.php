<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sesi_id', 'siswa_id', 'soal_id', 'pilihan_id', 'benar', 'waktu_habis', 'rating_sebelum', 'id_unik_klien', 'dijawab_pada', 'disinkron_pada'])]
class RiwayatJawaban extends Model
{
    protected $table = 'riwayat_jawaban';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'benar' => 'boolean',
            'waktu_habis' => 'boolean',
            'dijawab_pada' => 'datetime',
            'disinkron_pada' => 'datetime',
        ];
    }

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiKuis::class, 'sesi_id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
