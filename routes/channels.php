<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;

Broadcast::private('user.{user_key}', function ($user, string $user_key) {
    return session('chat.user_key') === $user_key;
});

Broadcast::private('chat-{room_key}', function ($user, string $room_key) {
    $session_user_key = (string) session('chat.user_key', '');

    if (! $session_user_key) {
        return false;
    }

    $room = Cache::get('chat:room:'.$room_key);
    $members = is_array($room) ? ($room['members'] ?? null) : null;

    if (! is_array($members)) {
        return false;
    }

    return in_array($session_user_key, $members, true);
});
