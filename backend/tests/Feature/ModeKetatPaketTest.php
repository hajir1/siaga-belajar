<?php

use App\Models\KejadianKuis;
use App\Models\Mapel;
use App\Models\PilihanSoal;
use App\Models\RiwayatJawaban;
use App\Models\SesiKuis;
use App\Models\Soal;
use App\Models\User;
use Database\Seeders\AkunDemoSeeder;
use Database\Seeders\BankSoalSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed([AkunDemoSeeder::class, BankSoalSeeder::class]);
    $this->h = masuk('andi@siaga.test');
});

function mulaiSesi($test, ?int $mapel = null): int
{
    return $test->postJson('/api/kuis/sesi', ['mapel_id' => $mapel ?? mapelId()], $test->h)->assertCreated()->json('data.id');
}

it('mencatat pindah tab hanya jika Mode Ketat aktif', function () {
    $sesi = mulaiSesi($this);
    $this->postJson("/api/kuis/sesi/{$sesi}/kejadian", ['jenis' => 'pindah_tab', 'durasi_detik' => 30], $this->h)
        ->assertOk()->assertJsonPath('data.dicatat', false);

    Mapel::query()->whereKey(mapelId())->update(['mode_ketat' => true]);
    $this->postJson("/api/kuis/sesi/{$sesi}/kejadian", ['jenis' => 'pindah_tab', 'durasi_detik' => 30], $this->h)->assertCreated();
    $this->postJson("/api/kuis/sesi/{$sesi}/kejadian", ['jenis' => 'lainnya', 'durasi_detik' => -1], $this->h)->assertStatus(422);

    $andi = collect($this->getJson('/api/guru/mapel/'.mapelId().'/dashboard', masuk('guru@siaga.test'))->json('data.siswa'))->firstWhere('nama', 'Andi Pratama');
    expect($andi['kejadian_pindah_tab'])->toBe(['jumlah' => 1, 'durasi_detik' => 30]);
});

it('menerima jawaban waktu habis hanya jika mapel memakai batas waktu', function () {
    $sesi = mulaiSesi($this);
    $soal = Soal::query()->where('mapel_id', mapelId())->value('id');
    $kirim = fn () => $this->postJson("/api/kuis/sesi/{$sesi}/jawaban", ['soal_id' => $soal, 'waktu_habis' => true, 'id_unik_klien' => (string) Str::uuid()], $this->h);

    $kirim()->assertStatus(422);

    Mapel::query()->whereKey(mapelId())->update(['batas_waktu_per_soal' => 30]);
    $kirim()->assertCreated()->assertJsonPath('data.benar', false);
    expect(RiwayatJawaban::first()->waktu_habis)->toBeTrue();
});

it('memberi info ukuran dan paket tanpa kunci jawaban', function () {
    $this->getJson('/api/kuis/mapel/'.mapelId().'/paket/info', $this->h)
        ->assertOk()->assertJsonPath('data.jumlah_soal', 50);

    $res = $this->getJson('/api/kuis/mapel/'.mapelId().'/paket', $this->h)->assertOk();
    expect($res->json('data.soal'))->toHaveCount(50)
        ->and($res->getContent())->not->toContain('"benar"')
        ->and($res->getContent())->not->toContain('rating_elo');
});

it('menolak paket mapel dari guru lain', function () {
    $guruLain = User::create(['name' => 'Guru Lain', 'email' => 'lain@siaga.test', 'password' => 'x', 'role' => 'guru']);
    $mapel = Mapel::create(['nama' => 'Rahasia', 'pembuat_id' => $guruLain->id]);

    $this->getJson("/api/kuis/mapel/{$mapel->id}/paket", $this->h)->assertForbidden();
});

it('menyinkron jawaban paket sekali saja walau dikirim ulang', function () {
    Mapel::query()->whereKey(mapelId())->update(['mode_ketat' => true]);
    $soal = Soal::query()->where('mapel_id', mapelId())->take(3)->pluck('id');
    $body = [
        'mapel_id' => mapelId(),
        'id_sesi_klien' => (string) Str::uuid(),
        'mulai' => now()->subMinutes(10)->toIso8601String(),
        'selesai' => now()->subMinute()->toIso8601String(),
        'jawaban' => $soal->map(fn ($id, $i) => [
            'soal_id' => $id,
            'pilihan_id' => PilihanSoal::query()->where('soal_id', $id)->where('benar', $i === 0)->value('id'),
            'id_unik_klien' => (string) Str::uuid(),
            'dijawab_pada' => now()->subMinutes(9 - $i)->toIso8601String(),
        ])->all(),
        'kejadian' => [['id_unik_klien' => (string) Str::uuid(), 'jenis' => 'pindah_tab', 'durasi_detik' => 20, 'waktu' => now()->subMinutes(5)->toIso8601String()]],
    ];

    $r1 = $this->postJson('/api/kuis/sinkron', $body, $this->h)->assertOk();
    expect(collect($r1->json('data.hasil'))->pluck('status')->unique()->all())->toBe(['tersimpan'])
        ->and($r1->json('data.hasil.0.benar'))->toBeTrue()
        ->and($r1->json('data.hasil.1.benar'))->toBeFalse();

    $r2 = $this->postJson('/api/kuis/sinkron', $body, $this->h)->assertOk();
    expect(collect($r2->json('data.hasil'))->pluck('status')->unique()->all())->toBe(['duplikat']);

    expect(SesiKuis::count())->toBe(1)
        ->and(RiwayatJawaban::count())->toBe(3)
        ->and(KejadianKuis::count())->toBe(1)
        ->and(SesiKuis::first()->selesai)->not->toBeNull();
});

it('menandai jawaban paket untuk soal yang sudah ditarik sebagai ditolak', function () {
    $soal = Soal::query()->where('mapel_id', mapelId())->first();
    $pilihan = PilihanSoal::query()->where('soal_id', $soal->id)->value('id');
    $soal->update(['status' => 'draf']);

    $this->postJson('/api/kuis/sinkron', [
        'mapel_id' => mapelId(), 'id_sesi_klien' => (string) Str::uuid(), 'mulai' => now()->toIso8601String(),
        'jawaban' => [['soal_id' => $soal->id, 'pilihan_id' => $pilihan, 'id_unik_klien' => (string) Str::uuid(), 'dijawab_pada' => now()->toIso8601String()]],
    ], $this->h)->assertOk()->assertJsonPath('data.hasil.0.status', 'ditolak');
});

it('mengacak urutan pilihan di Mode Ketat tanpa membocorkan kunci', function () {
    Mapel::query()->whereKey(mapelId())->update(['mode_ketat' => true]);
    $res = $this->getJson('/api/kuis/sesi/'.mulaiSesi($this).'/soal-berikutnya', $this->h)->assertOk();

    expect($res->json('data.pilihan'))->toHaveCount(4)
        ->and($res->getContent())->not->toContain('"benar"');
});
