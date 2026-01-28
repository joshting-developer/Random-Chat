<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\ChatMessageSent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class SendMessageTest extends TestCase
{
    public function test_it_broadcasts_message_for_room_members(): void
    {
        Cache::flush();
        Event::fake([ChatMessageSent::class]);

        $roomKey = (string) Str::uuid();
        $userKey = (string) Str::uuid();

        Cache::forever('chat:room:'.$roomKey, [
            'roomKey' => $roomKey,
            'members' => [$userKey],
        ]);

        $response = $this->postJson(route('chat.rooms.messages', ['roomKey' => $roomKey]), [
            'user_key' => $userKey,
            'message' => 'Hello there!',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'sent',
            ]);

        Event::assertDispatched(
            ChatMessageSent::class,
            fn (ChatMessageSent $event) => $event->room_key === $roomKey
                && $event->user_key === $userKey
                && $event->message === 'Hello there!'
                && $event->sent_at !== '',
        );
    }

    public function test_it_rejects_message_from_non_members(): void
    {
        Cache::flush();
        Event::fake([ChatMessageSent::class]);

        $roomKey = (string) Str::uuid();
        $userKey = (string) Str::uuid();

        Cache::forever('chat:room:'.$roomKey, [
            'roomKey' => $roomKey,
            'members' => ['someone-else'],
        ]);

        $response = $this->postJson(route('chat.rooms.messages', ['roomKey' => $roomKey]), [
            'user_key' => $userKey,
            'message' => 'Hello there!',
        ]);

        $response->assertNotFound();
        Event::assertNotDispatched(ChatMessageSent::class);
    }
}
