<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SongPlayerController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/songs/{song}/player', [SongPlayerController::class, 'show'])->name('songs.player');

