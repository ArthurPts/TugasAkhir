<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Jobs\GenerateChordAudioJob;
use App\Http\Controllers\ChordAudioController;
use App\Http\Controllers\SongPlayerController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/api/chord-audio/batch', [ChordAudioController::class, 'batch']);
Route::get('/song-test', fn() => view('song-test'));
Route::get('/songs/{song}/player', [SongPlayerController::class, 'show'])->name('songs.player');


Route::get('/audio-test', fn() => view('audio-test'));

Route::get('/test-dispatch/{text}', function (string $text) {
    GenerateChordAudioJob::dispatch($text, 'en-US-AriaNeural');
    return "Dispatched job for: {$text}. Cek queue:work di terminal.";
});

