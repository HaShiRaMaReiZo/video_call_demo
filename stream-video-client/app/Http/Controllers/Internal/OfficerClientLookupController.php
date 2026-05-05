<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneDigits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficerClientLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expected = (string) config('stream.officer_lookup_token', '');
        if ($expected === '' || ! hash_equals($expected, (string) $request->header('X-Officer-Lookup-Token', ''))) {
            abort(403, 'Invalid lookup token.');
        }

        $validated = $request->validate([
            'phone' => 'required|string|max:32',
        ]);

        $digits = PhoneDigits::normalize($validated['phone']);
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return response()->json(['message' => 'Invalid phone number.'], 422);
        }

        $user = User::query()->where('phone', $digits)->first();

        if (! $user?->stream_user_id) {
            return response()->json(['message' => 'No client found for this phone number.'], 404);
        }

        return response()->json(['stream_user_id' => $user->stream_user_id]);
    }
}
