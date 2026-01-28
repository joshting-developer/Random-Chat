<?php

namespace Tests\Unit\Chat;

use App\Events\Chat\MatchFound;
use App\Jobs\Chat\ProcessMatchJob;
use App\Services\Chat\ChatRoomService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcessMatchJobTest extends TestCase
{
    public function test_it_creates_room_and_removes_queue_members(): void
    {
        Cache::flush();
        Event::fake([MatchFound::class]);

        $user_key = (string) Str::uuid();
        $partner_key = (string) Str::uuid();

        Redis::shouldReceive('lrange')
            ->once()
            ->with('chat:match:queue', 0, -1)
            ->andReturn([$user_key, $partner_key]);

        Redis::shouldReceive('lrem')
            ->once()
            ->with('chat:match:queue', 0, $user_key)
            ->andReturn(1);

        Redis::shouldReceive('lrem')
            ->once()
            ->with('chat:match:queue', 0, $partner_key)
            ->andReturn(1);

        $job = new ProcessMatchJob($user_key);
        $job->handle(app(ChatRoomService::class));

        $room_key = Cache::get('chat:user-room:'.$user_key);

        $this->assertNotNull($room_key);
        $this->assertSame($room_key, Cache::get('chat:user-room:'.$partner_key));
        $this->assertSame(
            [$user_key, $partner_key],
            Cache::get('chat:room:'.$room_key)['members'] ?? [],
        );

        Event::assertDispatched(
            MatchFound::class,
            fn (MatchFound $event) => $event->user_key === $user_key
                && $event->room_key === $room_key,
        );

        Event::assertDispatched(
            MatchFound::class,
            fn (MatchFound $event) => $event->user_key === $partner_key
                && $event->room_key === $room_key,
        );
    }

    public function test_it_skips_matching_when_user_is_not_in_queue(): void
    {
        Cache::flush();
        Event::fake([MatchFound::class]);

        $user_key = (string) Str::uuid();
        $partner_key = (string) Str::uuid();

        Redis::shouldReceive('lrange')
            ->once()
            ->with('chat:match:queue', 0, -1)
            ->andReturn([$partner_key]);

        Redis::shouldReceive('lrem')->never();

        $job = new ProcessMatchJob($user_key);
        $job->handle(app(ChatRoomService::class));

        $this->assertNull(Cache::get('chat:user-room:'.$user_key));
        $this->assertNull(Cache::get('chat:user-room:'.$partner_key));
        Event::assertNotDispatched(MatchFound::class);
    }
}
