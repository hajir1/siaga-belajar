<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role guru|siswa|ortu
 */
#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const GURU = 'guru';

    public const SISWA = 'siswa';

    public const ORTU = 'ortu';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isGuru(): bool
    {
        return $this->role === self::GURU;
    }

    public function isSiswa(): bool
    {
        return $this->role === self::SISWA;
    }

    public function isOrtu(): bool
    {
        return $this->role === self::ORTU;
    }

    /** Kelas yang diampu (untuk guru). */
    public function kelasDiampu(): HasMany
    {
        return $this->hasMany(Kelas::class, 'guru_id');
    }

    /** Kelas tempat siswa terdaftar. */
    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kelas_siswa', 'siswa_id', 'kelas_id');
    }

    /** Anak dari orang tua. */
    public function anak(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ortu_siswa', 'ortu_id', 'siswa_id');
    }

    /** Orang tua dari siswa. */
    public function ortu(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ortu_siswa', 'siswa_id', 'ortu_id');
    }

    /**
     * ID guru yang mengajar siswa ini (lewat kelas).
     *
     * @return array<int>
     */
    public function idGuruPengajar(): array
    {
        return $this->kelas()->pluck('guru_id')->unique()->values()->all();
    }

    /** Apakah guru ini mengajar siswa tersebut di salah satu kelasnya. */
    public function mengajar(User $siswa): bool
    {
        return $this->kelasDiampu()
            ->whereHas('siswa', fn ($q) => $q->where('users.id', $siswa->id))
            ->exists();
    }
}
