<?php

namespace App\Http\Controllers\Chat;

use App\Services\Chat\ChatRoomService;
use Inertia\Inertia;

/**
 * 聊天頁面控制器
 */
class PageController
{
    public function __construct(private readonly ChatRoomService $chat_room_service) {}

    /**
     * 聊天室大廳
     * @return \Inertia\Response
     */
    public function lobby()
    {
        return Inertia::render('Chat/Lobby');
    }

    /**
     * 聊天室頁面
     * @return \Inertia\Response
     */
    public function room(string $room_key)
    {
        $session_user_key = (string) session('chat.user_key', '');
        $members = $this->chat_room_service->getRoomMembers($room_key);

        // 聊天室或使用者資料遺失
        if (! $session_user_key || ! is_array($members)) {
            return Inertia::render('Chat/RoomUnavailable', [
                'reason' => 'missing',
                'room_key' => $room_key,
            ]);
        }

        // 使用者不在聊天室成員中
        if (! in_array($session_user_key, $members, true)) {
            return Inertia::render('Chat/RoomUnavailable', [
                'reason' => 'forbidden',
                'room_key' => $room_key,
            ]);
        }

        return Inertia::render('Chat/Chat', [
            'room_key' => $room_key,
        ]);
    }
}
