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
            ],
            'i18n' => [
                'dashboard_video_help' => __('dashboard.video_id_help'),
                'dashboard_phone_label' => __('dashboard.phone_label'),
                'incoming_call' => __('dashboard.incoming_call'),
                'answer' => __('dashboard.answer'),
                'dismiss' => __('dashboard.dismiss'),
                'join_call' => __('dashboard.join_call'),
                'join_waiting' => __('dashboard.join_waiting'),
                'join_ready' => __('dashboard.join_ready'),
            ],
        ];
    }
}
