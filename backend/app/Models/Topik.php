<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['mapel_id', 'nama'])]
class Topik extends Model
{
    protected $table = 'topik';

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class);
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class);
    }
}
