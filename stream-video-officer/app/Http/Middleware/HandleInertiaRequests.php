<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $streamKey = config('stream.api_key');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'stream' => [
                'apiKey' => is_string($streamKey) ? $streamKey : null,
                'configured' => is_string($streamKey) && $streamKey !== '' && is_string(config('stream.api_secret')) && config('stream.api_secret') !== '',
                'client_lookup_configured' => is_string(config('stream.client_app_url')) && config('stream.client_app_url') !== ''
                    && is_string(config('stream.officer_lookup_token')) && config('stream.officer_lookup_token') !== '',
            ],
            'i18n' => [
                'officer_ring_title' => __('dashboard.officer_ring_title'),
                'officer_target_label' => __('dashboard.officer_target_label'),
                'officer_start_call' => __('dashboard.officer_start_call'),
                'officer_ring_help' => __('dashboard.officer_ring_help'),
            ],
        ];
    }
}
