<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * 產生並確保每個聊天使用者都有唯一的識別碼。
 */
class EnsureChatIdentity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('chat.user_key')) {
            $request->session()->put('chat.user_key', (string) Str::uuid());
        }

        return $next($request);
    }
}
