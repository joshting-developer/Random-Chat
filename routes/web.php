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

Route::get('/rooms/{room_key}', function (string $room_key, ChatRoomService $chat_room_service) {
    $session_user_key = (string) session('chat.user_key', '');
    $members = $chat_room_service->getRoomMembers($room_key);

    if (! $session_user_key || ! is_array($members)) {
        return Inertia::render('Chat/RoomUnavailable', [
            'reason' => 'missing',
            'room_key' => $room_key,
        ]);
    }

    if (! in_array($session_user_key, $members, true)) {
        return Inertia::render('Chat/RoomUnavailable', [
            'reason' => 'forbidden',
            'room_key' => $room_key,
        ]);
    }

    return Inertia::render('Chat/Chat', [
        'room_key' => $room_key,
    ]);
})->whereUuid('room_key')->name('chat.room');

Route::post('/chat/broadcasting/auth', [BroadcastAuthController::class, 'auth']);

require __DIR__.'/settings.php';
require __DIR__.'/channels.php';
