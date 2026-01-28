<?php

namespace Tests\Feature;

use App\Events\Chat\ChatPartnerLeft;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class LeaveRoomTest extends TestCase
{
    public function test_it_allows_leaving_a_room(): void
    {
        Cache::flush();
        Event::fake([ChatPartnerLeft::class]);

        $room_key = (string) Str::uuid();
        $user_key = (string) Str::uuid();
        $partner_key = (string) Str::uuid();

        Cache::forever('chat:room:'.$room_key, [
            'room_key' => $room_key,
            'members' => [$user_key, $partner_key],
        ]);
        Cache::forever('chat:user-room:'.$user_key, $room_key);
        Cache::forever('chat:user-room:'.$partner_key, $room_key);

        $response = $this->postJson("/api/chat/rooms/{$room_key}/leave", [
            'user_key' => $user_key,
        ]);

        $response->assertOk()
            ->assertJson([
                'state' => 'idle',
            ]);

        $this->assertNull(Cache::get('chat:user-room:'.$user_key));
        $this->assertNull(Cache::get('chat:user-room:'.$partner_key));

        Event::assertDispatched(
            ChatPartnerLeft::class,
            fn (ChatPartnerLeft $event) => $event->room_key === $room_key
                && $event->user_key === $user_key,
        );
    }
}
