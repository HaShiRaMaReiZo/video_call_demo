<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClientStreamLookupService
{
    public function isConfigured(): bool
    {
        $url = (string) config('stream.client_app_url', '');
        $token = (string) config('stream.officer_lookup_token', '');

        return $url !== '' && $token !== '';
    }

    /**
     * Resolve client Stream user id from phone via the client Laravel app (server-to-server).
     *
     * @return string|null stream_user_id or null if not found / error
     */
    public function resolveStreamUserIdByPhone(string $phoneInput): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phoneInput) ?? '';
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        $base = rtrim((string) config('stream.client_app_url'), '/');
        $url = $base.'/internal/stream-user-id-by-phone';
        $token = (string) config('stream.officer_lookup_token');

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-Officer-Lookup-Token' => $token])
                ->acceptJson()
                ->post($url, ['phone' => $digits]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $id = $response->json('stream_user_id');

        return is_string($id) && $id !== '' && strlen($id) <= 64 ? $id : null;
    }
}
