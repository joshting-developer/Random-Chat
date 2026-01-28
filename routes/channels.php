<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::private('user.{user_key}', function ($user, string $user_key) {
    return session('chat.user_key') === $user_key;
});
