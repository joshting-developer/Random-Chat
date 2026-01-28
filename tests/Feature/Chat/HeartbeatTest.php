<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\ChatPartnerLeft;
use App\Events\Chat\MatchQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class HeartbeatTest extends TestCase
{
    public function test_it_returns_partner_online_status(): void
    {
        Cache::flush();

        $roomKey = (string) Str::uuid();
        $userKey = (string) Str::uuid();
        $partnerKey = (string) Str::uuid();

        Cache::forever('chat:room:'.$roomKey, [
            'room_key' => $roomKey,
            'members' => [$userKey, $partnerKey],
        ]);

        Cache::put("chat:presence:{$partnerKey}", $roomKey, now()->addSeconds(25));

        $response = $this->postJson(route('chat.heartbeat'), [
            'room_key' => $roomKey,
            'user_key' => $userKey,
        ]);

        $response->assertOk()
            ->assertJson([
                'room_key' => $roomKey,
                'partner_online' => true,
                'partner_user_key' => $partnerKey,
            ]);

        $this->assertSame($roomKey, Cache::get("chat:presence:{$userKey}"));
    }

    public function test_it_closes_room_when_partner_offline(): void
    {
        Cache::flush();
        Event::fake([ChatPartnerLeft::class, MatchQueue::class]);

        $roomKey = (string) Str::uuid();
        $userKey = (string) Str::uuid();
        $partnerKey = (string) Str::uuid();

        Cache::forever('chat:room:'.$roomKey, [
            'room_key' => $roomKey,
            'members' => [$userKey, $partnerKey],
        ]);

        Cache::forever('chat:user-room:'.$userKey, $roomKey);
        Cache::forever('chat:user-room:'.$partnerKey, $roomKey);

        $response = $this->postJson(route('chat.heartbeat'), [
            'room_key' => $roomKey,
            'user_key' => $userKey,
        ]);

        $response->assertOk()
            ->assertJson([
                'room_key' => $roomKey,
                'partner_online' => false,
                'partner_user_key' => $partnerKey,
            ]);

        $this->assertNull(Cache::get('chat:room:'.$roomKey));
        $this->assertNull(Cache::get('chat:user-room:'.$userKey));
        $this->assertNull(Cache::get('chat:user-room:'.$partnerKey));

        Event::assertDispatched(
            ChatPartnerLeft::class,
            fn (ChatPartnerLeft $event) => $event->room_key === $roomKey
                && $event->user_key === $partnerKey,
        );

        Event::assertDispatched(
            MatchQueue::class,
            fn (MatchQueue $event) => $event->user_key === $userKey
                && $event->state->value === 'idle',
        );
    }
}
