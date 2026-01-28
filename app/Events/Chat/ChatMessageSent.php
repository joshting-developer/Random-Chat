<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 建立聊天訊息事件
     */
    public function __construct(
        public string $room_key,
        public string $user_key,
        public string $message,
        public string $sent_at,
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
        return 'chat.message';
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
            'user_key' => $this->user_key,
            'message' => $this->message,
            'sent_at' => $this->sent_at,
        ];
    }
}
