<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class MatchService
{
    // Redis keys
    private const QUEUE_KEY = 'chat:match:queue';               // list

    private const LOCK_KEY = 'chat:match:lock';

    /**
     * 開始配對：
     * - 若有人在 queue：配對並建立 room
     * - 否則：把自己放進 queue 等待
     *
     * 回傳：
     * - ['status' => 'queued'] 或 ['status' => 'matched', 'roomKey' => '...']
     */
    public function start(string $userKey): void
    {
        // 核心：對「取人 / 配對 / 入隊」這段加鎖，避免同時配到同一個人或覆蓋狀態
        $lock = Cache::lock(self::LOCK_KEY, 3); // 最多鎖 3 秒

        try {
            // block(秒)：最多等 2 秒取得鎖（避免尖峰時大量 timeout）
            $lock->block(2);

            Redis::rpush(self::QUEUE_KEY, $userKey);
        } finally {
            optional($lock)->release();
        }
    }
}
