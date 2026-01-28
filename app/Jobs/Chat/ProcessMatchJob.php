<?php

namespace App\Jobs\Chat;

use App\Enums\ChatMatchState;
use App\Events\Chat\MatchQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class ProcessMatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const QUEUE_KEY = 'chat:match:queue';

    private const LOCK_KEY = 'chat:match:lock';

    private const ROOM_PREFIX = 'chat:room:';

    private const ROOM_USER_PREFIX = 'chat:user-room:';

    /**
     * 建立配對處理工作
     */
    public function __construct(
        public string $user_key,
    ) {}

    /**
     * 執行配對流程
     */
    public function handle(): void
    {
        $lock = Cache::lock(self::LOCK_KEY, 3);

        try {
            $lock->block(2);

            $queue = Redis::lrange(self::QUEUE_KEY, 0, -1);
            $partner_key = $this->findPartner($queue, $this->user_key);

            if (! $partner_key) {
                Log::info("No partner found for user {$this->user_key}");
                return;
            }

            $room_key = (string) Str::uuid();

            Cache::forever(self::ROOM_PREFIX . $room_key, [
                'roomKey' => $room_key,
                'members' => [$this->user_key, $partner_key],
            ]);

            Cache::forever(self::ROOM_USER_PREFIX . $this->user_key, $room_key);
            Cache::forever(self::ROOM_USER_PREFIX . $partner_key, $room_key);

            Redis::lrem(self::QUEUE_KEY, 0, $this->user_key);
            Redis::lrem(self::QUEUE_KEY, 0, $partner_key);

            Log::info("Matched users {$this->user_key} and {$partner_key} into room {$room_key}");
            event(new MatchQueue($this->user_key, ChatMatchState::Room));
            event(new MatchQueue($partner_key, ChatMatchState::Room));
        } catch (LockTimeoutException) {
            // 保持安靜，等待下一次 job 重試
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * 取得可配對的使用者
     *
     * @param  array<int, string>  $queue
     */
    private function findPartner(array $queue, string $user_key): ?string
    {
        foreach ($queue as $candidate_key) {
            if ($candidate_key === $user_key) {
                continue;
            }

            return $candidate_key;
        }

        return null;
    }
}
