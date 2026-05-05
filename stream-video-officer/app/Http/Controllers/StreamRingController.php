<?php

namespace App\Http\Controllers;

use App\Services\ClientStreamLookupService;
use App\Services\StreamVideoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StreamRingController extends Controller
{
    public function store(
        Request $request,
        StreamVideoService $stream,
        ClientStreamLookupService $clientLookup,
    ): RedirectResponse {
        $data = $request->validate([
            'client_phone' => 'required|string|max:32',
        ]);

        if (! $stream->isConfigured()) {
            return back()->withErrors(['client_phone' => 'Stream is not configured on the server.']);
        }

        if (! $clientLookup->isConfigured()) {
            return back()->withErrors([
                'client_phone' => 'Client lookup is not configured. Set CLIENT_APP_URL and OFFICER_LOOKUP_TOKEN on the officer app (same token as on the client app).',
            ]);
        }

        $officer = $request->user();
        if (! $officer?->stream_user_id) {
            return back()->withErrors(['client_phone' => 'Your account is missing a Stream user id.']);
        }

        $targetStreamUserId = $clientLookup->resolveStreamUserIdByPhone($data['client_phone']);
        if ($targetStreamUserId === null) {
            return back()->withErrors([
                'client_phone' => 'No client found for this phone number, or the client app could not be reached.',
            ]);
        }

        if ($officer->stream_user_id === $targetStreamUserId) {
            return back()->withErrors(['client_phone' => 'Invalid client selection.']);
        }

        try {
            $info = $stream->ringCallToClient($officer, $targetStreamUserId);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['client_phone' => 'Could not start the call: '.$e->getMessage()]);
        }

        return redirect()->route('video.call', [
            'callType' => $info['callType'],
            'callId' => $info['callId'],
        ]);
    }
}
