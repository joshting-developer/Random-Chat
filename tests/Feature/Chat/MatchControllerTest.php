<?php

namespace Tests\Feature\Chat;

use App\Models\ChatHistory;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

class MatchControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, string>
     */
    private array $redis_queue = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->redis_queue = [];

        Redis::shouldReceive('rpush')
            ->andReturnUsing(function (string $key, string $value): int {
                $this->redis_queue[] = $value;

                return count($this->redis_queue);
            });

        Redis::shouldReceive('lrem')
            ->andReturnUsing(function (string $key, int $count, string $value): int {
                $before = count($this->redis_queue);
                $this->redis_queue = array_values(
                    array_filter(
                        $this->redis_queue,
                        static fn (string $member): bool => $member !== $value,
                    ),
                );

                return $before - count($this->redis_queue);
            });

        Redis::shouldReceive('lrange')
            ->andReturnUsing(function (): array {
                return $this->redis_queue;
            });
    }

    public function test_start_match_enqueues_user(): void
    {
        $userKey = (string) Str::uuid();

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userKey])
            ->postJson(route('chat.match.start'), [
                'user_key' => $userKey,
            ]);

        $response->assertOk();
        $response->assertJson([
            'state' => 'queue',
        ]);

        $this->assertSame('queue', Cache::get('chat:state:'.$userKey));
    }

    public function test_start_match_pairs_two_users(): void
    {
        $userAKey = (string) Str::uuid();
        $userBKey = (string) Str::uuid();

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userAKey])
            ->postJson(route('chat.match.start'), [
                'user_key' => $userAKey,
            ])
            ->assertOk();

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userBKey])
            ->postJson(route('chat.match.start'), [
                'user_key' => $userBKey,
            ]);

        $response->assertOk();
        $roomKey = Cache::get('chat:user-room:'.$userAKey);

        $this->assertNotEmpty($roomKey);
        $this->assertSame('room', Cache::get('chat:state:'.$userAKey));
        $this->assertSame('room', Cache::get('chat:state:'.$userBKey));
        $this->assertSame($roomKey, Cache::get('chat:user-room:'.$userAKey));
        $this->assertSame($roomKey, Cache::get('chat:user-room:'.$userBKey));
    }

    public function test_cancel_match_removes_user_from_queue(): void
    {
        $userKey = (string) Str::uuid();

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userKey])
            ->postJson(route('chat.match.start'), [
                'user_key' => $userKey,
            ])
            ->assertOk();

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userKey])
            ->postJson(route('chat.match.cancel'), [
                'user_key' => $userKey,
            ]);

        $response->assertOk();
        $response->assertJson([
            'state' => 'idle',
        ]);

        $this->assertSame('idle', Cache::get('chat:state:'.$userKey));
        $this->assertNotContains($userKey, $this->redis_queue);
    }

    public function test_join_room_requires_membership(): void
    {
        $userAKey = (string) Str::uuid();
        $userBKey = (string) Str::uuid();

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userAKey])
            ->postJson(route('chat.match.start'), [
                'user_key' => $userAKey,
            ])
            ->assertOk();

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userBKey])
            ->postJson(route('chat.match.start'), [
                'user_key' => $userBKey,
            ]);

        $roomKey = Cache::get('chat:user-room:'.$userAKey);
        $this->assertNotEmpty($roomKey);

        $notMemberKey = (string) Str::uuid();

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $notMemberKey])
            ->postJson(route('chat.rooms.join', ['room_key' => (string) $roomKey]), [
                'user_key' => $notMemberKey,
            ])
            ->assertNotFound();

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userAKey])
            ->postJson(route('chat.rooms.join', ['room_key' => (string) $roomKey]), [
                'user_key' => $userAKey,
            ])
            ->assertOk()
            ->assertJson([
                'state' => 'room',
                'room_key' => (string) $roomKey,
                'history' => [],
            ]);
    }

    public function test_leave_room_clears_room_state(): void
    {
        $userAKey = (string) Str::uuid();
        $userBKey = (string) Str::uuid();

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userAKey])
            ->postJson(route('chat.match.start'), [
                'user_key' => $userAKey,
            ])
            ->assertOk();

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userBKey])
            ->postJson(route('chat.match.start'), [
                'user_key' => $userBKey,
            ]);

        $roomKey = Cache::get('chat:user-room:'.$userAKey);
        $this->assertNotEmpty($roomKey);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userAKey])
            ->postJson(route('chat.rooms.leave', ['room_key' => (string) $roomKey]), [
                'user_key' => $userAKey,
            ])
            ->assertOk()
            ->assertJson([
                'state' => 'idle',
            ]);

        $this->assertSame('idle', Cache::get('chat:state:'.$userAKey));
        $this->assertSame('idle', Cache::get('chat:state:'.$userBKey));
        $this->assertNull(Cache::get('chat:user-room:'.$userAKey));
        $this->assertNull(Cache::get('chat:user-room:'.$userBKey));
    }

    public function test_join_room_returns_history(): void
    {
        $roomKey = (string) Str::uuid();
        $userKey = (string) Str::uuid();

        Cache::forever('chat:room:'.$roomKey, [
            'room_key' => $roomKey,
            'members' => [$userKey],
        ]);

        $first = ChatHistory::factory()->create([
            'room_key' => $roomKey,
            'user_key' => $userKey,
            'message' => 'First message',
            'sent_at' => now()->subMinute(),
        ]);

        $second = ChatHistory::factory()->create([
            'room_key' => $roomKey,
            'user_key' => $userKey,
            'message' => 'Second message',
            'sent_at' => now(),
        ]);

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userKey])
            ->postJson(route('chat.rooms.join', ['room_key' => $roomKey]), [
                'user_key' => $userKey,
            ]);

        $response->assertOk();
        $history = $response->json('history');

        $this->assertIsArray($history);
        $this->assertCount(2, $history);
        $this->assertSame($first->id, $history[0]['id']);
        $this->assertSame($second->id, $history[1]['id']);
    }
}
