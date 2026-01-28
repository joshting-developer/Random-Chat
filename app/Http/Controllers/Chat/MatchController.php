<?php

namespace App\Http\Controllers\Chat;

use App\Enums\ChatMatchState;
use App\Events\Chat\MatchQueue;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\MatchRequest;
use App\Jobs\Chat\ProcessMatchJob;
use App\Services\Chat\MatchService;
use Illuminate\Http\JsonResponse;

class MatchController extends Controller
{
    public function __construct(private readonly MatchService $match_service) {}

    /**
     * 加入配對佇列
     */
    public function start(MatchRequest $request): JsonResponse
    {
        $user_key = $request->validated('user_key');

        $this->match_service->start($user_key);
        event(new MatchQueue($user_key, ChatMatchState::Queue));
        ProcessMatchJob::dispatch($user_key);

        return response()->json([
            'state' => ChatMatchState::Queue->value,
        ]);
    }

    public function cancel(MatchRequest $request): JsonResponse
    {
        $user_key = $request->validated('user_key');

        $this->match_service->cancel($user_key);
        event(new MatchQueue($user_key, ChatMatchState::Idle));

        return response()->json([
            'state' => ChatMatchState::Idle->value,
        ]);

    }
}
