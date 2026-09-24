<?php

namespace App\Http\Controllers;

use App\Models\Song;
use Illuminate\View\View;

class SongPlayerController extends Controller
{
    /**
     * Halaman ini murni shell Blade; data lagu dimuat lewat endpoint timeline API.
     */
    public function show(Song $song): View
    {
        return view('songs.player', ['songId' => $song->id]);
    }
}