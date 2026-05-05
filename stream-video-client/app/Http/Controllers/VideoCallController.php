<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoCallController extends Controller
{
    public function show(Request $request, string $callType, string $callId): View
    {
        return view('video.active-call', [
            'callType' => $callType,
            'callId' => $callId,
        ]);
    }
}
