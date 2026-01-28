<?php

namespace App\Services\Chat;

use App\Enums\ChatMatchState;
use Illuminate\Support\Facades\Cache;

class ChatRoomService
{
    private const ROOM_PREFIX = 'chat:room:';

    private const ROOM_USER_PREFIX = 'chat:user-room:';

    private const STATE_PREFIX = 'chat:state:';

    /**
     * 取得房間資訊
     *
     * @return array<string, mixed>|null
     */
    public function getRoom(string $room_key): ?array
    {
        $room = Cache::get(self::ROOM_PREFIX.$room_key);

        return is_array($room) ? $room : null;
    }

    /**
     * 取得房間成員
     *
     * @return array<int, string>|null
     */
    public function getRoomMembers(string $room_key): ?array
    {
        $room = $this->getRoom($room_key);

        if (! is_array($room)) {
            return null;
        }

        $members = $room['members'] ?? null;

        return is_array($members) ? $members : null;
    }

    /**
     * 建立房間資訊
     *
     * @param  array<int, string>  $members
     */
    public function storeRoom(string $room_key, array $members): void
    {
        Cache::forever(self::ROOM_PREFIX.$room_key, [
            'room_key' => $room_key,
            'members' => $members,
        ]);
    }

    /**
     * 設定使用者房間
     */
    public function setUserRoom(string $user_key, string $room_key): void
    {
        Cache::forever(self::ROOM_USER_PREFIX.$user_key, $room_key);
    }

    /**
     * 清除使用者房間
     */
    public function clearUserRoom(string $user_key): void
    {
        Cache::forget(self::ROOM_USER_PREFIX.$user_key);
    }

    /**
     * 清除房間資訊
     */
    public function clearRoom(string $room_key): void
    {
        Cache::forget(self::ROOM_PREFIX.$room_key);
    }

    /**
     * 設定使用者狀態
     */
    public function setUserState(string $user_key, ChatMatchState $state): void
    {
        Cache::forever(self::STATE_PREFIX.$user_key, $state->value);
    }
}
