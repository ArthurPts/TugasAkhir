<?php

use App\Http\Controllers\Api\ChordPlacementController;
use App\Http\Controllers\Api\SongController;
use Illuminate\Support\Facades\Route;

Route::apiResource('songs', SongController::class);
Route::get('/songs/{song}/timeline', [SongController::class, 'timeline']);

Route::post('/chord-placements', [ChordPlacementController::class, 'store']);
Route::patch('/chord-placements/{chordPlacement}', [ChordPlacementController::class, 'update']);
Route::delete('/chord-placements/{chordPlacement}', [ChordPlacementController::class, 'destroy']);
Route::post('/chord-placements/{chordPlacement}/transpose', [ChordPlacementController::class, 'transpose']);