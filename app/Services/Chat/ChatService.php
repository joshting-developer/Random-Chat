<?php

namespace App\Services\Chat;

use App\Models\ChatHistory;
use App\Repositories\Chat\ChatRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class ChatService
{
    public function __construct(private readonly ChatRepository $chat_repository) {}

    /**
     * 取得房間聊天紀錄
     *
     * @return Collection<int, ChatHistory>
     */
    public function getRoomHistory(string $room_key): Collection
    {
        return $this->chat_repository->getRoomHistory($room_key);
    }

    /**
     * 建立聊天紀錄
     */
    public function createHistory(
        string $room_key,
        string $user_key,
        string $message,
        CarbonInterface|string|null $sent_at = null,
    ): ChatHistory {
        return $this->chat_repository->createHistory(
            $room_key,
            $user_key,
            $message,
            $sent_at,
        );
    }

    /**
     * 取得指定聊天紀錄
     */
    public function findHistory(int $id): ?ChatHistory
    {
        return $this->chat_repository->findHistory($id);
    }

    /**
     * 更新聊天紀錄
     *
     * @param  array{room_key?: string, user_key?: string, message?: string, sent_at?: CarbonInterface|string|null}  $attributes
     */
    public function updateHistory(ChatHistory $history, array $attributes): ChatHistory
    {
        return $this->chat_repository->updateHistory($history, $attributes);
    }

    /**
     * 刪除聊天紀錄
     */
    public function deleteHistory(ChatHistory $history): void
    {
        $this->chat_repository->deleteHistory($history);
    }
}
