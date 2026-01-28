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
use App\Services\Chat\ChatRoomService;
use App\Services\Chat\MatchService;
use Illuminate\Http\JsonResponse;

class MatchController extends Controller
{
    public function __construct(
        private readonly MatchService $match_service,
        private readonly ChatRoomService $chat_room_service,
    ) {}

    /**
     * 加入配對佇列
     */
    public function start(MatchRequest $request): JsonResponse
    {
        $user_key = $request->validated('user_key');

        $this->match_service->start($user_key);
        $this->chat_room_service->setUserState($user_key, ChatMatchState::Queue);
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
        $this->chat_room_service->setUserState($user_key, ChatMatchState::Idle);
        event(new MatchQueue($user_key, ChatMatchState::Idle));

        return response()->json([
            'state' => ChatMatchState::Idle->value,
        ]);
    }

    public function leave(string $room_key, MatchRequest $request): JsonResponse
    {
        $user_key = $request->validated('user_key');

        $members = $this->chat_room_service->getRoomMembers($room_key);

        if (! is_array($members) || ! in_array($user_key, $members, true)) {
            $this->chat_room_service->clearUserRoom($user_key);
            $this->chat_room_service->setUserState($user_key, ChatMatchState::Idle);

            return response()->json([
                'state' => ChatMatchState::Idle->value,
            ]);
        }

        foreach ($members as $member) {
            $this->chat_room_service->clearUserRoom($member);
            $this->chat_room_service->setUserState($member, ChatMatchState::Idle);
            event(new MatchQueue($member, ChatMatchState::Idle));
        }

        $this->chat_room_service->clearRoom($room_key);
        event(new ChatPartnerLeft($room_key, $user_key));

        return response()->json([
            'state' => ChatMatchState::Idle->value,
        ]);
    }

    public function sendMessage(string $room_key, SendMessageRequest $request): JsonResponse
    {
        $user_key = $request->validated('user_key');
        $message = $request->validated('message');
        $members = $this->chat_room_service->getRoomMembers($room_key);

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
