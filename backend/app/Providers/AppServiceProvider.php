<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 5 percobaan login per menit per email+IP.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip())
                ->response(fn () => response()->json(['message' => 'Terlalu banyak percobaan login. Coba lagi sebentar lagi.'], 429));
        });

        // Jawaban: cukup longgar untuk sinkron antrean, tapi menahan spam.
        RateLimiter::for('jawaban', function (Request $request) {
            return Limit::perMinute(60)->by((string) ($request->user()?->id ?? $request->ip()))
                ->response(fn () => response()->json(['message' => 'Terlalu banyak jawaban dalam waktu singkat.'], 429));
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by((string) ($request->user()?->id ?? $request->ip()));
        });
    }
}
