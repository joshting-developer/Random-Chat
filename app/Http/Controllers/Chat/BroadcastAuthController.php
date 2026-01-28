<?php

namespace App\Http\Controllers\Chat;

use App\Services\Chat\ChatRoomService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * WebSocket 廣播授權控制器
 */
class BroadcastAuthController extends Controller
{
    public function __construct(private readonly ChatRoomService $chat_room_service) {}

    /**
     * 驗證使用者是否可訂閱私人頻道
     */
    public function auth(Request $request): Response
    {
        $socket_id = (string) $request->input('socket_id');
        $channel_name = (string) $request->input('channel_name'); // 例如 private-user.{uuid}

        $session_user_key = (string) session('chat.user_key', '');
        $private_user_channel = 'private-user.'.$session_user_key;
        $is_user_channel = $channel_name === $private_user_channel;
        $is_room_channel = $this->isAuthorizedRoomChannel($channel_name, $session_user_key);

        if (! $session_user_key || (! $is_user_channel && ! $is_room_channel)) {
            abort(403);
        }

        // 用 Pusher 的簽名規則產 auth： key:signature
        $key = config('broadcasting.connections.pusher.key');
        $secret = config('broadcasting.connections.pusher.secret');

        $string_to_sign = $socket_id.':'.$channel_name;
        $signature = hash_hmac('sha256', $string_to_sign, $secret);

        return response()->json([
            'auth' => $key.':'.$signature,
        ]);
    }

    private function isAuthorizedRoomChannel(string $channel_name, string $session_user_key): bool
    {
        if (! str_starts_with($channel_name, 'private-chat-')) {
            return false;
        }

        $room_key = substr($channel_name, strlen('private-chat-'));

        if ($room_key === '') {
            return false;
        }

        $members = $this->chat_room_service->getRoomMembers($room_key);

        return is_array($members) && in_array($session_user_key, $members, true);
    }
}
