<?php

use App\Models\User;
use App\Services\PeringatanDiniService;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('menampilkan peringatan dini dari riwayat demo', function () {
    $data = $this->getJson('/api/guru/mapel/'.mapelId().'/dashboard', masuk('guru@siaga.test'))->assertOk()->json('data');

    $andi = collect($data['siswa'])->firstWhere('nama', 'Andi Pratama');
    $jenis = collect($andi['peringatan'])->pluck('jenis');

    expect($jenis)->toContain('turun_beruntun')->toContain('di_bawah_batas');
});

it('aturan turun beruntun butuh 3 penurunan berturut-turut', function () {
    $p = new PeringatanDiniService;

    expect($p->turunBeruntun([90, 80, 70, 60]))->toBeTrue()
        ->and($p->turunBeruntun([90, 80, 80, 60]))->toBeFalse()
        ->and($p->turunBeruntun([80, 70, 60]))->toBeFalse();
});

it('orang tua hanya melihat laporan anaknya sendiri', function () {
    $h = masuk('ortu@siaga.test');
    $anak = $this->getJson('/api/ortu/anak', $h)->assertOk()->json('data');

    expect($anak)->toHaveCount(1);

    $this->getJson("/api/laporan/siswa/{$anak[0]['id']}", $h)
        ->assertOk()
        ->assertJsonStructure(['data' => ['anak', 'mapel' => [['ringkasan', 'topik' => [['topik', 'nilai', 'saran']]]]]]);

    $bukanAnak = User::query()->where('email', 'bela@siaga.test')->value('id');
    $this->getJson("/api/laporan/siswa/{$bukanAnak}", $h)->assertForbidden();
});
