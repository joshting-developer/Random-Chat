<?php

namespace Tests\Feature\Chat;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class MatchControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
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
        $roomKey = $response->json('roomKey');

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
            ->postJson(route('chat.match.cancel'));

        $response->assertOk();
        $response->assertJson([
            'state' => 'idle',
        ]);

        $this->assertSame('idle', Cache::get('chat:state:'.$userKey));
        $this->assertNotContains($userKey, Cache::get('chat:queue', []));
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

        $roomKey = (string) $response->json('roomKey');

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => 'not-member'])
            ->postJson(route('chat.rooms.join', ['roomKey' => $roomKey]))
            ->assertNotFound();

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userAKey])
            ->postJson(route('chat.rooms.join', ['roomKey' => $roomKey]))
            ->assertOk()
            ->assertJson([
                'state' => 'room',
                'roomKey' => $roomKey,
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

        $roomKey = (string) $response->json('roomKey');

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userAKey])
            ->postJson(route('chat.rooms.leave', ['roomKey' => $roomKey]))
            ->assertOk()
            ->assertJson([
                'state' => 'idle',
            ]);

        $this->assertSame('idle', Cache::get('chat:state:'.$userAKey));
        $this->assertSame('idle', Cache::get('chat:state:'.$userBKey));
        $this->assertNull(Cache::get('chat:user-room:'.$userAKey));
        $this->assertNull(Cache::get('chat:user-room:'.$userBKey));
    }
}
