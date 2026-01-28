<?php

use App\Http\Controllers\Chat\BroadcastAuthController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Broadcast::routes(['middleware' => ['web']]);

Route::get('/', function () {
    return Inertia::render('Chat/Lobby');
})->name('home');

Route::get('/chat', function () {
    return Inertia::render('Chat/Chat');
})->name('chat');

Route::post('/chat/broadcasting/auth', [BroadcastAuthController::class, 'auth']);

require __DIR__.'/settings.php';
require __DIR__.'/channels.php';
