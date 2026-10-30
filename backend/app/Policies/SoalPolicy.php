<?php

namespace App\Policies;

use App\Models\Soal;
use App\Models\User;

class SoalPolicy
{
    /** Hanya guru pemilik mapel yang boleh melihat kunci, mengubah, atau menghapus soal. */
    public function kelola(User $user, Soal $soal): bool
    {
        return $user->isGuru() && $soal->mapel()->value('pembuat_id') === $user->id;
    }
}
