<?php

use App\Http\Controllers\ChordAudioController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChordController;
use App\Http\Controllers\Api\ChordPlacementController;
use App\Http\Controllers\Api\LyricLineController;
use App\Http\Controllers\Api\SongAudioController;
use App\Http\Controllers\Api\SongController;
use App\Http\Controllers\Api\SongSectionController;
use Illuminate\Support\Facades\Route;

// Auth Routes (Sanctum)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/me/quota', [AuthController::class, 'quota']);
});

// Chord Library & Generation
Route::get('/chords', [ChordController::class, 'index']);
Route::post('/chords', [ChordController::class, 'store']);
Route::post('/chord-audio/batch', [ChordAudioController::class, 'batch']);

// Songs & Audio Reference
Route::apiResource('songs', SongController::class);
Route::get('/songs/{song}/timeline', [SongController::class, 'timeline']);
Route::post('/songs/{song}/transpose', [SongController::class, 'transpose']);
Route::post('/songs/{song}/audio', [SongAudioController::class, 'store']);
Route::delete('/songs/{song}/audio', [SongAudioController::class, 'destroy']);

// Song Sections & Lyric Lines
Route::get('/songs/{song}/sections', [SongSectionController::class, 'index']);
Route::post('/songs/{song}/sections', [SongSectionController::class, 'store']);
Route::patch('/sections/{section}', [SongSectionController::class, 'update']);
Route::delete('/sections/{section}', [SongSectionController::class, 'destroy']);

Route::post('/sections/{section}/lines', [LyricLineController::class, 'store']);
Route::patch('/lines/{line}', [LyricLineController::class, 'update']);
Route::delete('/lines/{line}', [LyricLineController::class, 'destroy']);

// Chord Placements & Transpose
Route::post('/chord-placements', [ChordPlacementController::class, 'store']);
Route::patch('/chord-placements/{chordPlacement}', [ChordPlacementController::class, 'update']);
Route::delete('/chord-placements/{chordPlacement}', [ChordPlacementController::class, 'destroy']);
Route::post('/chord-placements/{chordPlacement}/transpose', [ChordPlacementController::class, 'transpose']);