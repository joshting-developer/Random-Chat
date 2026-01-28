<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Chat/Lobby');
})->name('home');

Route::get('/chat', function () {
    return Inertia::render('Chat/Chat');
})->name('home');

require __DIR__ . '/settings.php';
