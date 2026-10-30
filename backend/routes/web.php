<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Semua rute non-API dilayani oleh SPA React (hasil build ada di public/index.html).
// Di Apache, .htaccess sudah mengarahkan rute React langsung ke index.html; ini cadangan untuk server lain.
Route::fallback(function (Request $request) {
    $index = public_path('index.html');

    abort_if($request->is('api/*') || ! file_exists($index), 404);

    return response()->file($index, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-cache']);
});
