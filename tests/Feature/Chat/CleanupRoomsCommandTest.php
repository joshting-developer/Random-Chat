<?php

namespace Tests\Feature\Chat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

class CleanupRoomsCommandTest extends TestCase
{
    public function test_it_removes_rooms_without_presence(): void
    {
        Cache::flush();
        config(['cache.prefix' => 'test-cache-']);

        $room_key = (string) Str::uuid();
        $user_key = (string) Str::uuid();
        $partner_key = (string) Str::uuid();

        Cache::forever('chat:room:'.$room_key, [
            'room_key' => $room_key,
            'members' => [$user_key, $partner_key],
        ]);

        Cache::forever('chat:user-room:'.$user_key, $room_key);
        Cache::forever('chat:user-room:'.$partner_key, $room_key);
        Cache::forever('chat:state:'.$user_key, 'room');
        Cache::forever('chat:state:'.$partner_key, 'room');

        Redis::shouldReceive('keys')
            ->once()
            ->with('test-cache-chat:room:*')
            ->andReturn(['test-cache-chat:room:'.$room_key]);

        $this->artisan('chat:rooms:cleanup')
            ->assertExitCode(0);

        $this->assertNull(Cache::get('chat:room:'.$room_key));
        $this->assertNull(Cache::get('chat:user-room:'.$user_key));
        $this->assertNull(Cache::get('chat:user-room:'.$partner_key));
        $this->assertNull(Cache::get('chat:state:'.$user_key));
        $this->assertNull(Cache::get('chat:state:'.$partner_key));
    }

    public function test_it_keeps_rooms_with_presence(): void
    {
        Cache::flush();
        config(['cache.prefix' => 'test-cache-']);

        $room_key = (string) Str::uuid();
        $user_key = (string) Str::uuid();
        $partner_key = (string) Str::uuid();

        Cache::forever('chat:room:'.$room_key, [
            'room_key' => $room_key,
            'members' => [$user_key, $partner_key],
        ]);

        Cache::forever('chat:user-room:'.$user_key, $room_key);
        Cache::forever('chat:user-room:'.$partner_key, $room_key);
        Cache::put('chat:presence:'.$user_key, $room_key, now()->addSeconds(25));

        Redis::shouldReceive('keys')
            ->once()
            ->with('test-cache-chat:room:*')
            ->andReturn(['test-cache-chat:room:'.$room_key]);

        $this->artisan('chat:rooms:cleanup')
            ->assertExitCode(0);

        $this->assertNotNull(Cache::get('chat:room:'.$room_key));
        $this->assertSame($room_key, Cache::get('chat:user-room:'.$user_key));
        $this->assertSame($room_key, Cache::get('chat:user-room:'.$partner_key));
    }
}
