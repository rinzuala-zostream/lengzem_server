<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowCrossOriginResource
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        return $response
            ->header('Cross-Origin-Resource-Policy', 'cross-origin')
            ->header('Cross-Origin-Opener-Policy', 'same-origin-allow-popups')
            ->header('Cross-Origin-Embedder-Policy', 'unsafe-none');
    }
}
