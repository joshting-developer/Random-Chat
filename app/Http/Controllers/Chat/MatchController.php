<?php

namespace App\Http\Controllers\Chat;

use App\Enums\ChatMatchState;
use App\Events\Chat\ChatMessageSent;
use App\Events\Chat\ChatPartnerLeft;
use App\Events\Chat\MatchQueue;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\MatchRequest;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Jobs\Chat\ProcessMatchJob;
use App\Services\Chat\MatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

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

    public function leave(string $room_key, MatchRequest $request): JsonResponse
    {
        $user_key = $request->validated('user_key');

        $room = Cache::get('chat:room:'.$room_key);
        $members = is_array($room) ? ($room['members'] ?? null) : null;

        if (! is_array($members) || ! in_array($user_key, $members, true)) {
            Cache::forget('chat:user-room:'.$user_key);
            Cache::forever('chat:state:'.$user_key, ChatMatchState::Idle->value);

            return response()->json([
                'state' => ChatMatchState::Idle->value,
            ]);
        }

        foreach ($members as $member) {
            Cache::forget('chat:user-room:'.$member);
            Cache::forever('chat:state:'.$member, ChatMatchState::Idle->value);
            event(new MatchQueue($member, ChatMatchState::Idle));
        }

        Cache::forget('chat:room:'.$room_key);
        event(new ChatPartnerLeft($room_key, $user_key));

        return response()->json([
            'state' => ChatMatchState::Idle->value,
        ]);
    }

    public function sendMessage(string $room_key, SendMessageRequest $request): JsonResponse
    {
        $user_key = $request->validated('user_key');
        $message = $request->validated('message');
        $room = Cache::get('chat:room:'.$room_key);
        $members = is_array($room) ? ($room['members'] ?? null) : null;

        if (! is_array($members) || ! in_array($user_key, $members, true)) {
            return response()->json([
                'message' => 'Room not found.',
            ], 404);
        }

        $sent_at = now()->toIso8601String();

        event(new ChatMessageSent($room_key, $user_key, $message, $sent_at));

        return response()->json([
            'status' => 'sent',
            'sentAt' => $sent_at,
        ]);
    }
}
