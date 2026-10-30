<?php

namespace App\Policies;

use App\Models\SesiKuis;
use App\Models\User;

class SesiKuisPolicy
{
    public function kerjakan(User $user, SesiKuis $sesi): bool
    {
        return $user->isSiswa() && $sesi->siswa_id === $user->id;
    }
}
