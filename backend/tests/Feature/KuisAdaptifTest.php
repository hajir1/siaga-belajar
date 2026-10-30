<?php

use App\Models\PilihanSoal;
use App\Models\RatingSiswa;
use App\Models\RiwayatJawaban;
use App\Models\Soal;
use App\Models\User;
use App\Services\PemilihanSoalService;
use Database\Seeders\AkunDemoSeeder;
use Database\Seeders\BankSoalSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed([AkunDemoSeeder::class, BankSoalSeeder::class]);
    $this->h = masuk('andi@siaga.test');
    $this->sesi = $this->postJson('/api/kuis/sesi', ['mapel_id' => mapelId()], $this->h)->assertCreated()->json('data.id');
});

function jawab($test, int $soalId, bool $benar, ?string $uuid = null)
{
    $pilihan = PilihanSoal::query()->where('soal_id', $soalId)->where('benar', $benar)->first();

    return $test->postJson("/api/kuis/sesi/{$test->sesi}/jawaban", [
        'soal_id' => $soalId,
        'pilihan_id' => $pilihan->id,
        'id_unik_klien' => $uuid ?? (string) Str::uuid(),
    ], $test->h);
}

it('tidak pernah mengirim kolom benar di soal berikutnya', function () {
    $res = $this->getJson("/api/kuis/sesi/{$this->sesi}/soal-berikutnya", $this->h)->assertOk();

    expect($res->getContent())->not->toContain('"benar"')
        ->and($res->getContent())->not->toContain('rating_elo');
    expect($res->json('data.pilihan'))->toHaveCount(4);
});

it('menilai jawaban di server dan memperbarui rating', function () {
    $soalId = $this->getJson("/api/kuis/sesi/{$this->sesi}/soal-berikutnya", $this->h)->json('data.id');

    $res = jawab($this, $soalId, true)->assertCreated();

    expect($res->json('data.benar'))->toBeTrue()
        ->and($res->json('data.rating_baru'))->toBeGreaterThan(1000);
    expect(RatingSiswa::query()->where('mapel_id', mapelId())->value('rating'))->toBe($res->json('data.rating_baru'));
});

it('tidak mencatat jawaban ganda dengan id_unik_klien yang sama', function () {
    $soalId = Soal::query()->where('mapel_id', mapelId())->value('id');
    $uuid = (string) Str::uuid();

    jawab($this, $soalId, false, $uuid)->assertCreated();
    jawab($this, $soalId, false, $uuid)->assertOk()->assertJsonPath('data.duplikat', true);

    expect(RiwayatJawaban::count())->toBe(1);
});

it('menolak pilihan yang bukan milik soal', function () {
    $soalId = Soal::query()->where('mapel_id', mapelId())->value('id');
    $pilihanLain = PilihanSoal::query()->where('soal_id', '!=', $soalId)->value('id');

    $this->postJson("/api/kuis/sesi/{$this->sesi}/jawaban", [
        'soal_id' => $soalId, 'pilihan_id' => $pilihanLain, 'id_unik_klien' => (string) Str::uuid(),
    ], $this->h)->assertStatus(422);
});

it('menolak siswa lain mengakses sesi orang lain', function () {
    $this->getJson("/api/kuis/sesi/{$this->sesi}/soal-berikutnya", masuk('bela@siaga.test'))->assertForbidden();
});

it('tidak memberi soal yang sama berturut-turut', function () {
    $sebelumnya = null;
    foreach (range(1, 15) as $_) {
        $id = $this->getJson("/api/kuis/sesi/{$this->sesi}/soal-berikutnya", $this->h)->json('data.id');
        expect($id)->not->toBe($sebelumnya);
        jawab($this, $id, (bool) random_int(0, 1));
        $sebelumnya = $id;
    }
});

it('mengulang soal yang salah setelah beberapa soal', function () {
    $salah = $this->getJson("/api/kuis/sesi/{$this->sesi}/soal-berikutnya", $this->h)->json('data.id');
    jawab($this, $salah, false);

    $muncul = [];
    foreach (range(1, PemilihanSoalService::JEDA_ULANG + 1) as $_) {
        $id = $this->getJson("/api/kuis/sesi/{$this->sesi}/soal-berikutnya", $this->h)->json('data.id');
        $muncul[] = $id;
        jawab($this, $id, true);
    }

    // Tidak muncul sebelum jeda, lalu muncul tepat setelah jeda terpenuhi.
    expect(array_slice($muncul, 0, PemilihanSoalService::JEDA_ULANG))->not->toContain($salah)
        ->and(end($muncul))->toBe($salah);
});

it('memilih soal dengan rating dekat rating siswa', function () {
    $andi = User::query()->where('email', 'andi@siaga.test')->value('id');
    RatingSiswa::query()->updateOrInsert(['siswa_id' => $andi, 'mapel_id' => mapelId()], ['rating' => 1200]);
    $id = $this->getJson("/api/kuis/sesi/{$this->sesi}/soal-berikutnya", $this->h)->json('data.id');

    expect(Soal::find($id)->tingkat)->toBe('sulit');
});
