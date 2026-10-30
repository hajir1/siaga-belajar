<?php

use App\Models\Soal;
use App\Models\Topik;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->guru = masuk('guru@siaga.test');
});

it('memberi peta kelemahan kelas dan kalimat ringkas', function () {
    $d = $this->getJson('/api/guru/mapel/'.mapelId().'/dashboard', $this->guru)->assertOk()->json('data');

    $aljabar = collect($d['ringkasan_kelas'])->firstWhere('topik', 'Aljabar');
    expect($aljabar['jumlah_siswa'])->toBe(5)
        ->and($aljabar['jumlah_lemah'])->toBeGreaterThanOrEqual(1)
        ->and(implode(' ', $d['kalimat_kelas']))->toContain('kelas lemah di');
});

it('menampilkan kejadian pindah tab demo sebagai data perlu dicek', function () {
    $d = $this->getJson('/api/guru/mapel/'.mapelId('IPA').'/dashboard', $this->guru)->json('data');
    $dimas = collect($d['siswa'])->firstWhere('nama', 'Dimas Saputra');

    expect($dimas['kejadian_pindah_tab']['jumlah'])->toBe(3);
});

it('merekomendasikan remedial untuk topik yang kena peringatan', function () {
    $h = masuk('andi@siaga.test');
    $rek = collect($this->getJson('/api/kuis/rekomendasi', $h)->assertOk()->json('data'));

    expect($rek->pluck('topik'))->toContain('Pecahan');

    $pecahan = $rek->firstWhere('topik', 'Pecahan');
    $sesi = $this->postJson('/api/kuis/sesi', ['mapel_id' => $pecahan['mapel_id'], 'topik_id' => $pecahan['topik_id']], $h)
        ->assertCreated()->assertJsonPath('data.topik_fokus.nama', 'Pecahan')->json('data.id');

    foreach (range(1, 5) as $_) {
        $soal = $this->getJson("/api/kuis/sesi/{$sesi}/soal-berikutnya", $h)->json('data');
        expect($soal['topik'])->toBe('Pecahan');
        $this->postJson("/api/kuis/sesi/{$sesi}/jawaban", ['soal_id' => $soal['id'], 'pilihan_id' => $soal['pilihan'][0]['id'], 'id_unik_klien' => (string) Str::uuid()], $h);
    }
});

it('menolak sesi remedial dengan topik dari mapel lain', function () {
    $topikIpa = Topik::query()->where('mapel_id', mapelId('IPA'))->value('id');
    $this->postJson('/api/kuis/sesi', ['mapel_id' => mapelId(), 'topik_id' => $topikIpa], masuk('andi@siaga.test'))
        ->assertStatus(422)->assertJsonValidationErrors('topik_id');
});

it('mengekspor nilai ke CSV', function () {
    $res = $this->get('/api/guru/mapel/'.mapelId().'/ekspor/nilai', $this->guru)->assertOk();

    expect($res->headers->get('Content-Type'))->toContain('text/csv')
        ->and($res->getContent())->toContain('Andi Pratama')
        ->and($res->getContent())->toContain('Pecahan');
});

it('mengekspor lalu mengimpor ulang bank soal dengan isi yang sama', function () {
    $csv = $this->get('/api/guru/mapel/'.mapelId().'/ekspor/soal', $this->guru)->assertOk()->getContent();
    expect($csv)->toContain('topik;teks;tingkat;status;benar');

    $mapelBaru = $this->postJson('/api/guru/mapel', ['nama' => 'Matematika Salinan'], $this->guru)->json('data.id');
    $this->post("/api/guru/mapel/{$mapelBaru}/impor/soal", ['berkas' => UploadedFile::fake()->createWithContent('soal.csv', $csv)], $this->guru + ['Accept' => 'application/json'])
        ->assertCreated()->assertJsonPath('data.jumlah', 51 - 1);

    $asli = Soal::query()->where('mapel_id', mapelId())->where('teks', 'like', 'Hasil dari -8 + 15%')->with('pilihan')->first();
    $salinan = Soal::query()->where('mapel_id', $mapelBaru)->where('teks', $asli->teks)->with('pilihan')->first();
    expect($salinan->pilihan->firstWhere('benar', true)->teks)->toBe('7')
        ->and(Topik::query()->where('mapel_id', $mapelBaru)->count())->toBe(5);
});

it('menolak seluruh impor jika ada baris yang salah', function () {
    $csv = "topik;teks;tingkat;status;benar;salah1\nPecahan;Soal yang benar formatnya;mudah;terbit;1;2\nPecahan;;mudah;terbit;1;\n";
    $sebelum = Soal::count();

    $res = $this->post('/api/guru/mapel/'.mapelId().'/impor/soal', ['berkas' => UploadedFile::fake()->createWithContent('soal.csv', $csv)], $this->guru + ['Accept' => 'application/json'])
        ->assertStatus(422);

    expect(implode(' ', $res->json('errors.berkas')))->toContain('Baris 3')
        ->and(Soal::count())->toBe($sebelum);
});

it('mengamankan sel CSV dari formula injection', function () {
    $topik = Topik::query()->where('mapel_id', mapelId())->value('id');
    $this->postJson('/api/guru/soal', [
        'mapel_id' => mapelId(), 'topik_id' => $topik, 'teks' => '=HYPERLINK("http://contoh")', 'tingkat' => 'mudah', 'status' => 'draf',
        'pilihan' => [['teks' => '-3', 'benar' => true], ['teks' => '+1', 'benar' => false]],
    ], $this->guru)->assertCreated();

    $csv = $this->get('/api/guru/mapel/'.mapelId().'/ekspor/soal', $this->guru)->getContent();
    expect($csv)->toContain("'=HYPERLINK")->toContain(';-3;')->toContain("'+1");
});

it('memberi analisis kualitas soal untuk guru saja', function () {
    $data = collect($this->getJson('/api/guru/mapel/'.mapelId().'/kualitas-soal', $this->guru)->assertOk()->json('data'));

    expect($data)->toHaveCount(50)
        ->and($data->sum('jumlah_jawaban'))->toBe(600)
        ->and($data->first())->toHaveKeys(['persen_benar', 'rating_awal', 'rating_sekarang', 'pengecoh', 'catatan']);

    $this->getJson('/api/guru/mapel/'.mapelId().'/kualitas-soal', masuk('andi@siaga.test'))->assertForbidden();
});
