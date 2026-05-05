<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VideoCallController extends Controller
{
    public function show(Request $request, string $callType, string $callId): Response
    {
        return Inertia::render('Video/ActiveCall', [
            'callType' => $callType,
            'callId' => $callId,
        ]);
    }
}
