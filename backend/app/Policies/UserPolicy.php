<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Laporan siswa: hanya orang tuanya sendiri atau guru yang mengajarnya. */
    public function lihatLaporan(User $user, User $siswa): bool
    {
        if (! $siswa->isSiswa()) {
            return false;
        }

        return match ($user->role) {
            User::ORTU => $user->anak()->where('users.id', $siswa->id)->exists(),
            User::GURU => $user->mengajar($siswa),
            default => false,
        };
    }
}
