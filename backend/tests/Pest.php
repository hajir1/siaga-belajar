<?php

use App\Models\Mapel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/** Masuk sebagai akun demo dengan email tertentu dan kembalikan header Authorization. */
function masuk(string $email): array
{
    $token = test()->postJson('/api/login', ['email' => $email, 'password' => 'siaga123'])->json('token');

    // Lupakan user yang tersimpan di guard agar request berikutnya benar-benar memakai token ini.
    app('auth')->forgetGuards();

    return ['Authorization' => "Bearer {$token}"];
}

/** ID mapel demo berdasarkan nama (tidak bergantung auto-increment). */
function mapelId(string $nama = 'Matematika'): int
{
    return (int) Mapel::query()->where('nama', $nama)->value('id');
}
