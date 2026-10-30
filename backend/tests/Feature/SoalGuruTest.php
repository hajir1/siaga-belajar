<?php

use App\Models\Soal;
use App\Models\Topik;
use Database\Seeders\AkunDemoSeeder;
use Database\Seeders\BankSoalSeeder;

beforeEach(function () {
    $this->seed([AkunDemoSeeder::class, BankSoalSeeder::class]);
    $this->h = masuk('guru@siaga.test');
});

function soalValid(array $ubah = []): array
{
    return array_merge([
        'mapel_id' => mapelId(),
        'topik_id' => Topik::query()->where('mapel_id', mapelId())->value('id'),
        'teks' => 'Hasil dari 9 - 12 adalah ...',
        'tingkat' => 'mudah',
        'status' => 'terbit',
        'pilihan' => [
            ['teks' => '-3', 'benar' => true],
            ['teks' => '3', 'benar' => false],
            ['teks' => '21', 'benar' => false],
        ],
    ], $ubah);
}

it('menyimpan soal valid dengan rating awal sesuai tingkat', function () {
    $res = $this->postJson('/api/guru/soal', soalValid(['tingkat' => 'sulit']), $this->h)->assertCreated();

    expect($res->json('data.rating_elo'))->toBe(1200)
        ->and($res->json('data.pilihan'))->toHaveCount(3);
});

it('menolak form soal kosong', function () {
    $this->postJson('/api/guru/soal', [], $this->h)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['mapel_id', 'topik_id', 'teks', 'tingkat', 'status', 'pilihan']);
});

it('menolak soal dengan kurang dari 2 pilihan', function () {
    $this->postJson('/api/guru/soal', soalValid(['pilihan' => [['teks' => 'A', 'benar' => true]]]), $this->h)
        ->assertStatus(422)->assertJsonValidationErrors('pilihan');
});

it('menolak soal tanpa jawaban benar atau dengan dua jawaban benar', function () {
    $nolBenar = [['teks' => 'A', 'benar' => false], ['teks' => 'B', 'benar' => false]];
    $duaBenar = [['teks' => 'A', 'benar' => true], ['teks' => 'B', 'benar' => true]];

    $this->postJson('/api/guru/soal', soalValid(['pilihan' => $nolBenar]), $this->h)->assertJsonValidationErrors('pilihan');
    $this->postJson('/api/guru/soal', soalValid(['pilihan' => $duaBenar]), $this->h)->assertJsonValidationErrors('pilihan');
});

it('menolak teks pilihan kosong atau terlalu panjang', function () {
    $this->postJson('/api/guru/soal', soalValid(['pilihan' => [['teks' => '   ', 'benar' => true], ['teks' => 'B', 'benar' => false]]]), $this->h)
        ->assertJsonValidationErrors('pilihan.0.teks');
    $this->postJson('/api/guru/soal', soalValid(['teks' => str_repeat('a', 1001)]), $this->h)
        ->assertJsonValidationErrors('teks');
});

it('menolak topik dari mapel lain', function () {
    $topikIpa = Topik::query()->where('mapel_id', mapelId('IPA'))->value('id');
    $this->postJson('/api/guru/soal', soalValid(['topik_id' => $topikIpa]), $this->h)->assertJsonValidationErrors('topik_id');
});

it('soal draf tidak muncul di kuis siswa', function () {
    Soal::query()->update(['status' => 'draf']);
    $h = masuk('andi@siaga.test');
    $sesi = $this->postJson('/api/kuis/sesi', ['mapel_id' => mapelId()], $h)->json('data.id');

    $this->getJson("/api/kuis/sesi/{$sesi}/soal-berikutnya", $h)->assertOk()->assertJsonPath('data', null);
});

it('mengubah pengaturan mapel', function () {
    $this->patchJson('/api/guru/mapel/'.mapelId(), ['mode_ketat' => true, 'mode_pengerjaan' => 'paket'], $this->h)
        ->assertOk()
        ->assertJsonPath('data.mode_ketat', true)
        ->assertJsonPath('data.mode_pengerjaan', 'paket');

    $this->patchJson('/api/guru/mapel/'.mapelId(), ['mode_pengerjaan' => 'lainnya'], $this->h)->assertStatus(422);
});

it('menambah topik baru secara dinamis', function () {
    $this->postJson('/api/guru/mapel/'.mapelId().'/topik', ['nama' => 'Statistika'], $this->h)->assertCreated();
    $this->postJson('/api/guru/mapel/'.mapelId().'/topik', ['nama' => 'Statistika'], $this->h)->assertStatus(422);

    expect(collect($this->getJson('/api/mapel/'.mapelId(), $this->h)->json('data.topik'))->pluck('nama'))->toContain('Statistika');
});
