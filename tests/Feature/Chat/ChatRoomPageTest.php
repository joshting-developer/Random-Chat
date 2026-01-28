<?php

namespace Tests\Feature\Chat;

use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChatRoomPageTest extends TestCase
{
    public function test_it_renders_chat_room_with_room_key(): void
    {
        $roomKey = (string) Str::uuid();

        $response = $this->get("/chat/rooms/{$roomKey}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Chat/Chat')
            ->where('roomKey', $roomKey)
        );
    }
}
