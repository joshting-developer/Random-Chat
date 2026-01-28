<?php

namespace App\Events\Chat;

use App\Enums\ChatMatchState;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MatchQueued implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 建立已加入配對事件
     */
    public function __construct(
        public string $user_key,
    ) {}

    /**
     * 取得廣播頻道
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->user_key),
        ];
    }

    /**
     * 設定廣播事件名稱
     */
    public function broadcastAs(): string
    {
        return 'chat.match.queued';
    }

    /**
     * 取得廣播資料
     *
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        Log::channel('single')->info('MatchQueued broadcastWith', ['user_key' => $this->user_key]);

        return [
            'state' => ChatMatchState::Queue->value,
        ];
    }
}
