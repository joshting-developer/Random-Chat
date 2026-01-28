<?php

namespace Tests\Feature\Chat;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Str;
use Tests\TestCase;

class BroadcastChannelTest extends TestCase
{
    public function test_user_channel_allows_matching_session_user_key(): void
    {
        $userKey = (string) Str::uuid();

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['chat.user_key' => $userKey])
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-user.'.$userKey,
                'socket_id' => '123.456',
            ]);

        $response->assertOk();
    }
}
