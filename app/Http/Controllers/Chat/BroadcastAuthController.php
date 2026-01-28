<?php

namespace App\Http\Controllers\Chat;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * WebSocket 廣播授權控制器
 */
class BroadcastAuthController extends Controller
{
    /**
     * 驗證使用者是否可訂閱私人頻道
     */
    public function auth(Request $request): Response
    {
        $socket_id = (string) $request->input('socket_id');
        $channel_name = (string) $request->input('channel_name'); // 例如 private-user.{uuid}

        // 你自己的授權規則：只允許訂閱自己的 private-user.<user_key>
        $session_user_key = (string) session('chat.user_key', '');

        // channel_name 會是 private-user.xxxx
        $expected = 'private-user.'.$session_user_key;

        if (! $session_user_key || $channel_name !== $expected) {
            abort(403);
        }

        // 用 Pusher 的簽名規則產 auth： key:signature
        $key = config('broadcasting.connections.pusher.key');
        $secret = config('broadcasting.connections.pusher.secret');

        $string_to_sign = $socket_id.':'.$channel_name;
        $signature = hash_hmac('sha256', $string_to_sign, $secret);

        return response()->json([
            'auth' => $key.':'.$signature,
        ]);
    }
}
