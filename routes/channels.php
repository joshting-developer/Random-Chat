<?php

use App\Services\Chat\ChatRoomService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::private('user.{user_key}', function ($user, string $user_key) {
    return session('chat.user_key') === $user_key;
});

Broadcast::private('chat-{room_key}', function ($user, string $room_key) {
    $session_user_key = (string) session('chat.user_key', '');

    if (! $session_user_key) {
        return false;
    }

    $members = app(ChatRoomService::class)->getRoomMembers($room_key);

    return is_array($members) && in_array($session_user_key, $members, true);
});
