<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareBladeGlobals
{
    public function handle(Request $request, Closure $next): Response
    {
        $streamKey = config('stream.api_key');
        $secret = config('stream.api_secret');

        View::share('stream', [
            'apiKey' => is_string($streamKey) ? $streamKey : null,
            'configured' => is_string($streamKey) && $streamKey !== '' && is_string($secret) && $secret !== '',
        ]);

        return $next($request);
    }
}
