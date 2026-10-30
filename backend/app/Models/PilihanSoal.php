<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kolom "benar" disembunyikan dari serialisasi bawaan agar tidak pernah
 * bocor ke klien secara tidak sengaja. Resource guru menampilkannya secara eksplisit.
 */
#[Fillable(['soal_id', 'teks', 'benar'])]
#[Hidden(['benar'])]
class PilihanSoal extends Model
{
    protected $table = 'pilihan_soal';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['benar' => 'boolean'];
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
