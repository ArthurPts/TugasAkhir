<?php

namespace App\Http\Controllers;

use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SongPlayerController extends Controller
{
    /**
     * Halaman player Blade. Guest boleh akses lagu public; lagu private hanya owner & admin.
     */
    public function show(Request $request, Song $song): View
    {
        $user = $request->user();

        if ($song->visibility === 'private') {
            if (! $user || ($user->role !== 'admin' && $user->id !== $song->user_id)) {
                abort(403, 'Anda tidak memiliki akses ke lagu privat ini.');
            }
        }

        $canEdit = $user && ($user->role === 'admin' || $user->id === $song->user_id);

        return view('songs.player', [
            'songId'  => $song->id,
            'song'    => $song,
            'canEdit' => $canEdit,
        ]);
    }
}