<?php

namespace App\Repositories\Chat;

use App\Models\ChatHistory;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class ChatRepository
{
    /**
     * 取得房間聊天紀錄
     *
     * @return Collection<int, ChatHistory>
     */
    public function getRoomHistory(string $room_key): Collection
    {
        return ChatHistory::query()
            ->where('room_key', $room_key)
            ->orderBy('sent_at')
            ->get();
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
        return ChatHistory::query()->create([
            'room_key' => $room_key,
            'user_key' => $user_key,
            'message' => $message,
            'sent_at' => $this->normalizeSentAt($sent_at),
        ]);
    }

    /**
     * 取得指定聊天紀錄
     */
    public function findHistory(int $id): ?ChatHistory
    {
        return ChatHistory::query()->find($id);
    }

    /**
     * 更新聊天紀錄
     *
     * @param  array{room_key?: string, user_key?: string, message?: string, sent_at?: CarbonInterface|string|null}  $attributes
     */
    public function updateHistory(ChatHistory $history, array $attributes): ChatHistory
    {
        $history->fill($attributes);
        $history->save();

        return $history;
    }

    /**
     * 刪除聊天紀錄
     */
    public function deleteHistory(ChatHistory $history): void
    {
        $history->delete();
    }

    private function normalizeSentAt(CarbonInterface|string|null $sent_at): CarbonInterface
    {
        if ($sent_at instanceof CarbonInterface) {
            return $sent_at;
        }

        if (is_string($sent_at)) {
            return Carbon::parse($sent_at);
        }

        return now();
    }
}
