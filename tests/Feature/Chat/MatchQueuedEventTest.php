<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\MatchQueued;
use App\Services\Chat\MatchService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class MatchQueuedEventTest extends TestCase
{
    public function test_match_queued_event_is_dispatched(): void
    {
        Event::fake([MatchQueued::class]);

        $this->app->instance(MatchService::class, new class extends MatchService
        {
            public function start(string $userKey): void {}
        });

        $userKey = (string) Str::uuid();

        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->postJson(route('chat.match.start'), [
                'user_key' => $userKey,
            ]);

        $response->assertOk();

        Event::assertDispatched(MatchQueued::class, function (MatchQueued $event) use ($userKey): bool {
            return $event->user_key === $userKey;
        });
    }
}
