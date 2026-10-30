<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['siswa_id', 'mapel_id', 'rating'])]
class RatingSiswa extends Model
{
    protected $table = 'rating_siswa';

    protected $primaryKey = null;

    public $incrementing = false;

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }
}
