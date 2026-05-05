<?php

namespace App\Http\Controllers;

use App\Services\StreamVideoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreamTokenController extends Controller
{
    public function store(Request $request, StreamVideoService $stream): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->stream_user_id, 403);

        if (! $stream->isConfigured()) {
            return response()->json(['message' => 'Stream is not configured on the server.'], 503);
        }

        $stream->upsertStreamUser($user);
        $token = $stream->createUserToken($user->stream_user_id);

        return response()->json([
            'apiKey' => config('stream.api_key'),
            'token' => $token,
            'user' => [
                'id' => $user->stream_user_id,
                'name' => $user->name,
            ],
        ]);
    }
}
