<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nama
 * @property int $pembuat_id
 * @property bool $mode_ketat
 * @property string $mode_pengerjaan
 * @property bool $proteksi_seleksi_teks
 * @property int|null $batas_waktu_per_soal
 */
#[Fillable(['nama', 'pembuat_id', 'mode_ketat', 'mode_pengerjaan', 'proteksi_seleksi_teks', 'batas_waktu_per_soal'])]
class Mapel extends Model
{
    protected $table = 'mapel';

    protected function casts(): array
    {
        return [
            'mode_ketat' => 'boolean',
            'proteksi_seleksi_teks' => 'boolean',
            'batas_waktu_per_soal' => 'integer',
        ];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembuat_id');
    }

    public function topik(): HasMany
    {
        return $this->hasMany(Topik::class);
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class);
    }
}
