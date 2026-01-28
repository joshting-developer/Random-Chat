<?php

use App\Http\Controllers\Chat\BroadcastAuthController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Broadcast::routes(['middleware' => ['web']]);

Route::get('/', function () {
    return Inertia::render('Chat/Lobby');
})->name('home');

Route::get('/rooms/{roomKey}', function (string $roomKey) {
    $session_user_key = (string) session('chat.user_key', '');
    $room = Cache::get('chat:room:'.$roomKey);
    $members = is_array($room) ? ($room['members'] ?? null) : null;

    if (! $session_user_key || ! is_array($members)) {
        return Inertia::render('Chat/RoomUnavailable', [
            'reason' => 'missing',
            'roomKey' => $roomKey,
        ]);
    }

    if (! in_array($session_user_key, $members, true)) {
        return Inertia::render('Chat/RoomUnavailable', [
            'reason' => 'forbidden',
            'roomKey' => $roomKey,
        ]);
    }

    return Inertia::render('Chat/Chat', [
        'roomKey' => $roomKey,
    ]);
})->whereUuid('roomKey')->name('chat.room');

Route::post('/chat/broadcasting/auth', [BroadcastAuthController::class, 'auth']);

require __DIR__.'/settings.php';
require __DIR__.'/channels.php';
