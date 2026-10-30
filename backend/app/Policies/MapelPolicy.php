<?php

namespace App\Policies;

use App\Models\Mapel;
use App\Models\User;

class MapelPolicy
{
    /** Guru pembuat, atau siswa yang diajar oleh guru pembuat. */
    public function view(User $user, Mapel $mapel): bool
    {
        return match ($user->role) {
            User::GURU => $mapel->pembuat_id === $user->id,
            User::SISWA => in_array($mapel->pembuat_id, $user->idGuruPengajar(), true),
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->isGuru();
    }

    public function update(User $user, Mapel $mapel): bool
    {
        return $user->isGuru() && $mapel->pembuat_id === $user->id;
    }
}
