<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi rute ke peran tertentu. Contoh: ->middleware('peran:guru').
 */
class PastikanPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $peran, true)) {
            abort(403, 'Anda tidak punya akses ke fitur ini.');
        }

        return $next($request);
    }
}
