<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Category;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SongWebController extends Controller
{
    /**
     * GET /songs - Katalog Lagu Publik (Phase D)
     */
    public function index(): View
    {
        $artists = Artist::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('songs.index', compact('artists', 'categories'));
    }

    /**
     * GET /me/songs - Lagu Saya (Phase D)
     */
    public function mySongs(): View
    {
        return view('songs.my-songs');
    }

    /**
     * GET /songs/create - Form Metadata Lagu Baru (Phase E)
     */
    public function create(Request $request): View
    {
        $user = $request->user();
        if ($user && $user->role !== 'admin' && $user->songs()->count() >= 10) {
            abort(403, 'Batas kuota upload lagu tercapai (maksimal 10 lagu aktif). Hapus lagu lama untuk menambah slot.');
        }

        $artists = Artist::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('songs.create', compact('artists', 'categories'));
    }

    /**
     * GET /songs/{song}/edit - Editor Lagu & Backing Track (Phase E)
     */
    public function edit(Request $request, Song $song): View
    {
        $song->load(['artist', 'categories', 'sections.lyricLines.chordPlacements.chord']);
        $maxAudioSizeMb = 15;
        $allowedAudioFormats = 'MP3, WAV, OGG';

        return view('songs.edit', compact('song', 'maxAudioSizeMb', 'allowedAudioFormats'));
    }

    /**
     * GET /songs/{song}/editor/simple - Editor Chord Mode Sederhana (Phase F)
     */
    public function simpleEditor(Request $request, Song $song): View
    {
        $song->load(['artist', 'sections.lyricLines.chordPlacements.chord']);
        return view('songs.editor-simple', compact('song'));
    }

    /**
     * GET /songs/{song}/editor/beat - Editor Chord Mode Beat (Phase G)
     */
    public function beatEditor(Request $request, Song $song): View
    {
        $song->load(['artist', 'sections.lyricLines.chordPlacements.chord']);
        return view('songs.editor-beat', compact('song'));
    }
}
