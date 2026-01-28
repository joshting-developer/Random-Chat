<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatPartnerLeft implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 建立離開聊天室事件
     */
    public function __construct(
        public string $room_key,
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
            new PrivateChannel('chat-'.$this->room_key),
        ];
    }

    /**
     * 設定廣播事件名稱
     */
    public function broadcastAs(): string
    {
        return 'chat.partner.left';
    }

    /**
     * 取得廣播資料
     *
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'roomKey' => $this->room_key,
            'userKey' => $this->user_key,
        ];
    }
}
