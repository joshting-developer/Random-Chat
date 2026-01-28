<?php

use App\Http\Controllers\Chat\BroadcastAuthController;
use App\Services\Chat\ChatRoomService;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Broadcast::routes(['middleware' => ['web']]);

Route::get('/', function () {
    return Inertia::render('Chat/Lobby');
})->name('home');

Route::get('/rooms/{roomKey}', function (string $roomKey, ChatRoomService $chat_room_service) {
    $session_user_key = (string) session('chat.user_key', '');
    $members = $chat_room_service->getRoomMembers($roomKey);

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
