<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $mapel_id
 * @property int $topik_id
 * @property string $teks
 * @property string $tingkat
 * @property int $rating_elo
 * @property string $status
 */
#[Fillable(['mapel_id', 'topik_id', 'teks', 'tingkat', 'rating_elo', 'status', 'pembuat_id'])]
class Soal extends Model
{
    protected $table = 'soal';

    /** Pemetaan tingkat awal ke rating Elo. */
    public const RATING_AWAL = ['mudah' => 900, 'sedang' => 1050, 'sulit' => 1200];

    protected function casts(): array
    {
        return ['rating_elo' => 'integer'];
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class);
    }

    public function topik(): BelongsTo
    {
        return $this->belongsTo(Topik::class);
    }

    public function pilihan(): HasMany
    {
        return $this->hasMany(PilihanSoal::class);
    }
}
