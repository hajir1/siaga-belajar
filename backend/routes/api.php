<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BerkasController;
use App\Http\Controllers\Api\DashboardGuruController;
use App\Http\Controllers\Api\KuisController;
use App\Http\Controllers\Api\LaporanOrtuController;
use App\Http\Controllers\Api\MapelController;
use App\Http\Controllers\Api\PaketController;
use App\Http\Controllers\Api\SoalController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Mapel (dibaca guru dan siswa)
    Route::middleware('peran:guru,siswa')->group(function () {
        Route::get('/mapel', [MapelController::class, 'index']);
        Route::get('/mapel/{mapel}', [MapelController::class, 'show']);
    });

    // Guru
    Route::middleware('peran:guru')->prefix('guru')->group(function () {
        Route::post('/mapel', [MapelController::class, 'store']);
        Route::patch('/mapel/{mapel}', [MapelController::class, 'update']);
        Route::post('/mapel/{mapel}/topik', [MapelController::class, 'storeTopik']);
        Route::get('/mapel/{mapel}/soal', [SoalController::class, 'index']);
        Route::get('/mapel/{mapel}/dashboard', DashboardGuruController::class);

        // Berkas dan kualitas soal
        Route::get('/mapel/{mapel}/ekspor/nilai', [BerkasController::class, 'eksporNilai']);
        Route::get('/mapel/{mapel}/ekspor/soal', [BerkasController::class, 'eksporSoal']);
        Route::post('/mapel/{mapel}/impor/soal', [BerkasController::class, 'imporSoal']);
        Route::get('/mapel/{mapel}/kualitas-soal', [BerkasController::class, 'kualitasSoal']);
        Route::get('/contoh-format-soal', [BerkasController::class, 'contohSoal']);

        Route::post('/soal', [SoalController::class, 'store']);
        Route::get('/soal/{soal}', [SoalController::class, 'show']);
        Route::put('/soal/{soal}', [SoalController::class, 'update']);
        Route::delete('/soal/{soal}', [SoalController::class, 'destroy']);
    });

    // Siswa
    Route::middleware('peran:siswa')->prefix('kuis')->group(function () {
        Route::post('/sesi', [KuisController::class, 'mulai']);
        Route::get('/sesi/{sesi}/soal-berikutnya', [KuisController::class, 'soalBerikutnya']);
        Route::post('/sesi/{sesi}/jawaban', [KuisController::class, 'jawab'])->middleware('throttle:jawaban');
        Route::post('/sesi/{sesi}/selesai', [KuisController::class, 'selesai']);
        Route::post('/sesi/{sesi}/kejadian', [KuisController::class, 'kejadian'])->middleware('throttle:jawaban');
        Route::get('/rekomendasi', [KuisController::class, 'rekomendasi']);

        // Mode Paket (koneksi tidak stabil)
        Route::get('/mapel/{mapel}/paket/info', [PaketController::class, 'info']);
        Route::get('/mapel/{mapel}/paket', [PaketController::class, 'unduh']);
        Route::post('/sinkron', [PaketController::class, 'sinkron'])->middleware('throttle:jawaban');
    });

    // Orang tua (laporan juga bisa dibuka guru yang mengajar siswa tersebut)
    Route::get('/ortu/anak', [LaporanOrtuController::class, 'anak'])->middleware('peran:ortu');
    Route::get('/laporan/siswa/{anak}', [LaporanOrtuController::class, 'laporan'])->middleware('peran:ortu,guru');
});
