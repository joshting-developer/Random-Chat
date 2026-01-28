<?php

use App\Http\Controllers\Chat\MatchController;
use Illuminate\Support\Facades\Route;

Route::prefix('chat')->group(function (): void {
    Route::post('match/start', [MatchController::class, 'start'])->name('chat.match.start');
    Route::post('match/cancel', [MatchController::class, 'cancel'])->name('chat.match.cancel');
    Route::post('rooms/{room_key}/leave', [MatchController::class, 'leave'])->name('chat.rooms.leave');
    Route::post('rooms/{room_key}/messages', [MatchController::class, 'sendMessage'])->name('chat.rooms.messages');
});
