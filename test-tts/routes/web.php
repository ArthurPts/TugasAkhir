<?php

use App\Http\Controllers\ChordWebController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SongPlayerController;
use App\Http\Controllers\SongWebController;
use App\Models\Song;
use Illuminate\Support\Facades\Route;

// Landing / Redirect ke Katalog
Route::get('/', function () {
    return redirect('/songs');
});

// Katalog Publik (Guest boleh)
Route::get('/songs', [SongWebController::class, 'index'])->name('songs.catalog');

// Buat Lagu Baru (Auth, harus sebelum /songs/{song} agar tidak tertangkap binding)
Route::get('/songs/create', [SongWebController::class, 'create'])
    ->middleware('auth')
    ->name('songs.create');

// Lagu Saya (Auth)
Route::get('/me/songs', [SongWebController::class, 'mySongs'])
    ->middleware('auth')
    ->name('me.songs');

// Redirect /dashboard bawaan Breeze ke Lagu Saya
Route::get('/dashboard', function () {
    return redirect()->route('me.songs');
})->middleware('auth')->name('dashboard');

// Player Lagu (Guest boleh untuk public; private dicek di controller)
Route::get('/songs/{song}/player', [SongPlayerController::class, 'show'])->name('songs.player');

// Legacy /edit diarahkan ke editor sederhana agar hanya ada 2 halaman editor aktif
Route::get('/songs/{song}/edit', function (Song $song) {
    return redirect()->route('songs.editor.simple', $song);
})
    ->middleware(['auth', 'can:update,song'])
    ->name('songs.edit');

// Editor Chord Mode Sederhana (Auth + Owner/Admin)
Route::get('/songs/{song}/editor/simple', [SongWebController::class, 'simpleEditor'])
    ->middleware(['auth', 'can:update,song'])
    ->name('songs.editor.simple');

// Editor Chord Mode Beat (Auth + Owner/Admin)
Route::get('/songs/{song}/editor/beat', [SongWebController::class, 'beatEditor'])
    ->middleware(['auth', 'can:update,song'])
    ->name('songs.editor.beat');

// Legacy shortcut editor juga diarahkan ke editor sederhana
Route::get('/songs/{song}/editor', function (Song $song) {
    return redirect()->route('songs.editor.simple', $song);
})
    ->middleware(['auth', 'can:update,song'])
    ->name('songs.editor');

// Pustaka Chord (Publik lihat, aksi tambah via API dicek auth)
Route::get('/chords', [ChordWebController::class, 'index'])->name('chords.index');

// Profile Breeze
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

