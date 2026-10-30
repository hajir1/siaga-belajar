<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sesi_id', 'id_unik_klien', 'jenis', 'durasi_detik', 'waktu'])]
class KejadianKuis extends Model
{
    protected $table = 'kejadian_kuis';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['waktu' => 'datetime', 'durasi_detik' => 'integer'];
    }

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiKuis::class, 'sesi_id');
    }
}
