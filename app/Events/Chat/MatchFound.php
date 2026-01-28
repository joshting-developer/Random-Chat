<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchFound implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 建立配對完成事件
     */
    public function __construct(
        public string $user_key,
        public string $room_key,
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
        return 'chat.match.found';
    }

    /**
     * 取得廣播資料
     *
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'room_key' => $this->room_key,
        ];
    }
}
