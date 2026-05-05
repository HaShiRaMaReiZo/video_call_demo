<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use StreamIo\Client;
use StreamIo\DTO\CallRequest;
use StreamIo\DTO\GetOrCreateCallRequest;
use StreamIo\DTO\MemberRequest;
use StreamIo\DTO\TokenParams;
use StreamIo\DTO\UserRequest;

class StreamVideoService
{
    private ?Client $client = null;

    public function __construct()
    {
        $key = config('stream.api_key');
        $secret = config('stream.api_secret');
        if (is_string($key) && $key !== '' && is_string($secret) && $secret !== '') {
            $this->client = new Client($key, $secret);
        }
    }

    public function isConfigured(): bool
    {
        return $this->client !== null;
    }

    public function createUserToken(string $streamUserId): string
    {
        if (! $this->client) {
            throw new \RuntimeException('Stream Video is not configured (missing STREAM_API_KEY / STREAM_API_SECRET).');
        }

        return $this->client->createToken(new TokenParams($streamUserId, 3600));
    }

    public function upsertStreamUser(User $user): void
    {
        if (! $this->client || ! $user->stream_user_id) {
            return;
        }

        $this->client->upsertUsers([
            new UserRequest(
                id: $user->stream_user_id,
                name: $user->name,
            ),
        ]);
    }

    /**
     * @return array{callId: string, callType: string}
     */
    public function ringCallToClient(User $officer, string $targetClientStreamUserId): array
    {
        if (! $this->client || ! $officer->stream_user_id) {
            throw new \RuntimeException('Stream Video is not configured or officer has no stream_user_id.');
        }

        $this->client->upsertUsers([
            new UserRequest(id: $officer->stream_user_id, name: $officer->name),
            new UserRequest(id: $targetClientStreamUserId, name: 'Client'),
        ]);

        $callType = config('stream.default_call_type', 'default');
        $callId = (string) Str::uuid();

        $call = $this->client->call($callType, $callId);
        $call->getOrCreateCall(new GetOrCreateCallRequest(
            ring: true,
            video: true,
            data: new CallRequest(
                createdById: $officer->stream_user_id,
                members: [
                    new MemberRequest($officer->stream_user_id),
                    new MemberRequest($targetClientStreamUserId),
                ],
            ),
        ));

        return ['callId' => $callId, 'callType' => $callType];
    }
}
