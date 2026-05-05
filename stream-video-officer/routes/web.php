<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StreamRingController;
use App\Http\Controllers\StreamTokenController;
use App\Http\Controllers\VideoCallController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/stream/token', [StreamTokenController::class, 'store'])->name('stream.token');
    Route::post('/calls/ring', [StreamRingController::class, 'store'])->name('calls.ring');
    Route::get('/call/{callType}/{callId}', [VideoCallController::class, 'show'])
        ->where(['callType' => '[A-Za-z0-9_\-]+', 'callId' => '[A-Za-z0-9\-]+'])
        ->name('video.call');
});

require __DIR__.'/auth.php';
