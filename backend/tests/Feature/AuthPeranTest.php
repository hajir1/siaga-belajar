<?php

use Database\Seeders\AkunDemoSeeder;
use Database\Seeders\BankSoalSeeder;

beforeEach(function () {
    $this->seed([AkunDemoSeeder::class, BankSoalSeeder::class]);
});

it('menolak login dengan sandi salah', function () {
    $this->postJson('/api/login', ['email' => 'guru@siaga.test', 'password' => 'salah'])
        ->assertStatus(422)
        ->assertJsonPath('errors.email.0', 'Email atau kata sandi salah.');
});

it('membatasi percobaan login', function () {
    foreach (range(1, 5) as $_) {
        $this->postJson('/api/login', ['email' => 'guru@siaga.test', 'password' => 'salah']);
    }
    $this->postJson('/api/login', ['email' => 'guru@siaga.test', 'password' => 'salah'])->assertStatus(429);
});

it('mengembalikan token dan peran saat login berhasil', function () {
    $this->postJson('/api/login', ['email' => 'siswa@siaga.test', 'password' => 'siaga123'])->assertStatus(422);
    $this->postJson('/api/login', ['email' => 'andi@siaga.test', 'password' => 'siaga123'])
        ->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'nama', 'peran']])
        ->assertJsonPath('user.peran', 'siswa');
});

it('menolak akses tanpa token', function () {
    $this->getJson('/api/me')->assertStatus(401);
    $this->getJson('/api/mapel')->assertStatus(401);
});

it('menolak siswa memanggil endpoint guru', function () {
    $h = masuk('andi@siaga.test');

    $this->getJson('/api/guru/mapel/'.mapelId().'/dashboard', $h)->assertForbidden();
    $this->getJson('/api/guru/mapel/'.mapelId().'/soal', $h)->assertForbidden();
    $this->postJson('/api/guru/soal', [], $h)->assertForbidden();
    $this->patchJson('/api/guru/mapel/'.mapelId(), ['mode_ketat' => true], $h)->assertForbidden();
});

it('menolak orang tua memanggil endpoint guru dan kuis', function () {
    $h = masuk('ortu@siaga.test');

    $this->getJson('/api/guru/mapel/'.mapelId().'/dashboard', $h)->assertForbidden();
    $this->postJson('/api/kuis/sesi', ['mapel_id' => mapelId()], $h)->assertForbidden();
});

it('menolak guru mengerjakan kuis', function () {
    $this->postJson('/api/kuis/sesi', ['mapel_id' => mapelId()], masuk('guru@siaga.test'))->assertForbidden();
});

it('mencabut token saat logout', function () {
    $h = masuk('guru@siaga.test');
    $this->postJson('/api/logout', [], $h)->assertOk();
    app('auth')->forgetGuards();
    $this->getJson('/api/me', $h)->assertStatus(401);
});

it('mengembalikan JSON 404 untuk endpoint API yang tidak ada', function () {
    $this->getJson('/api/tidak-ada')->assertNotFound()->assertJsonPath('message', 'Data tidak ditemukan.');
});
