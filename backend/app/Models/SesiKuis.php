<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['siswa_id', 'mapel_id', 'id_unik_klien', 'topik_fokus_id', 'mulai', 'selesai'])]
class SesiKuis extends Model
{
    protected $table = 'sesi_kuis';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['mulai' => 'datetime', 'selesai' => 'datetime'];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class);
    }

    public function topikFokus(): BelongsTo
    {
        return $this->belongsTo(Topik::class, 'topik_fokus_id');
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(RiwayatJawaban::class, 'sesi_id');
    }

    public function kejadian(): HasMany
    {
        return $this->hasMany(KejadianKuis::class, 'sesi_id');
    }
}
