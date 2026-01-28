<?php

namespace Tests\Feature\Chat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChatRoomPageTest extends TestCase
{
    public function test_it_renders_chat_room_with_room_key(): void
    {
        $roomKey = (string) Str::uuid();
        $userKey = (string) Str::uuid();

        Cache::forever('chat:room:'.$roomKey, [
            'room_key' => $roomKey,
            'members' => [$userKey],
        ]);

        $response = $this->withSession(['chat.user_key' => $userKey])
            ->get("/rooms/{$roomKey}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Chat/Chat')
            ->where('room_key', $roomKey)
        );
    }

    public function test_it_rejects_non_members_from_chat_room(): void
    {
        $roomKey = (string) Str::uuid();
        $userKey = (string) Str::uuid();

        Cache::forever('chat:room:'.$roomKey, [
            'room_key' => $roomKey,
            'members' => ['someone-else'],
        ]);

        $response = $this->withSession(['chat.user_key' => $userKey])
            ->get("/rooms/{$roomKey}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Chat/RoomUnavailable')
            ->where('reason', 'forbidden')
            ->where('room_key', $roomKey)
        );
    }

    public function test_it_shows_missing_room_page(): void
    {
        $roomKey = (string) Str::uuid();
        $userKey = (string) Str::uuid();

        $response = $this->withSession(['chat.user_key' => $userKey])
            ->get("/rooms/{$roomKey}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Chat/RoomUnavailable')
            ->where('reason', 'missing')
            ->where('room_key', $roomKey)
        );
    }
}
