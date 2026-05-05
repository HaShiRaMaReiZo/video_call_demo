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
            'client_lookup_configured' => is_string(config('stream.client_app_url')) && config('stream.client_app_url') !== ''
                && is_string(config('stream.officer_lookup_token')) && config('stream.officer_lookup_token') !== '',
        ]);

        return $next($request);
    }
}
