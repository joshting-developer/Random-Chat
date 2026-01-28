<?php

use App\Http\Controllers\Chat\BroadcastAuthController;
use App\Http\Controllers\Chat\PageController;
use App\Services\Chat\ChatRoomService;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Broadcast::routes(['middleware' => ['web']]);

Route::get('/', [PageController::class, 'lobby'])->name('home');

Route::get('/rooms/{room_key}', [PageController::class, 'room'])->whereUuid('room_key')->name('chat.room');

Route::post('/chat/broadcasting/auth', [BroadcastAuthController::class, 'auth']);

require __DIR__ . '/settings.php';
require __DIR__ . '/channels.php';
