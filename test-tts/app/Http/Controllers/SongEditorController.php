<?php

namespace App\Http\Controllers;

use App\Models\Song;
use Illuminate\Contracts\View\View;

class SongEditorController extends Controller
{
    /**
     * Show the chord editor page (Simple & Beat Editor).
     */
    public function show(Song $song): View
    {
        return view('songs.editor', [
            'songId' => $song->id,
            'song'   => $song->load('artist'),
        ]);
    }
}
