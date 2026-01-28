<?php

namespace App\Console\Commands\Chat;

use App\Services\Chat\ChatRoomService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CleanupRoomsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:rooms:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup chat rooms without active heartbeat';

    /**
     * Execute the console command.
     */
    public function handle(ChatRoomService $chat_room_service): int
    {
        $prefix = (string) config('cache.prefix', '');
        $room_keys = Redis::keys($prefix.'chat:room:*');

        foreach ($room_keys as $cache_key) {
            $room_key = str_replace($prefix.'chat:room:', '', (string) $cache_key);
            $room = $chat_room_service->getRoom($room_key);

            if (! is_array($room)) {
                $chat_room_service->clearRoom($room_key);

                continue;
            }

            $members = $room['members'] ?? null;

            if (! is_array($members) || $members === []) {
                $chat_room_service->clearRoom($room_key);

                continue;
            }

            $has_active_presence = false;

            foreach ($members as $member) {
                $presence = Cache::get("chat:presence:{$member}");

                if ($presence === $room_key) {
                    $has_active_presence = true;
                    break;
                }
            }

            if ($has_active_presence) {
                continue;
            }

            foreach ($members as $member) {
                $chat_room_service->clearUserRoom($member);
                $chat_room_service->clearUserState($member);
            }

            $chat_room_service->clearRoom($room_key);
        }

        return self::SUCCESS;
    }
}
