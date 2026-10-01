<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SongAudioController extends Controller
{
    /**
     * Upload or replace reference backing track audio for a song.
     */
    public function store(Request $request, Song $song): JsonResponse
    {
        $request->validate([
            'audio' => [
                'required',
                'file',
                'mimes:mp3,wav,ogg',
                'max:15360', // max 15MB
            ],
        ]);

        $disk = Storage::disk('public');

        // Delete old file if present
        if ($song->file_path && $disk->exists($song->file_path)) {
            $disk->delete($song->file_path);
        }

        $file = $request->file('audio');
        $storedPath = $file->store('song-audio', 'public');

        $song->update([
            'file_path' => $storedPath,
        ]);

        return response()->json([
            'message'             => 'Audio referensi berhasil diunggah.',
            'file_path'           => $song->file_path,
            'audio_url'           => $song->audio_url,
            'reference_audio_url' => $song->audio_url,
        ], 200);
    }

    /**
     * Delete reference backing track audio for a song.
     */
    public function destroy(Song $song): JsonResponse
    {
        $disk = Storage::disk('public');

        if ($song->file_path && $disk->exists($song->file_path)) {
            $disk->delete($song->file_path);
        }

        $song->update([
            'file_path' => null,
        ]);

        return response()->json([
            'message' => 'Audio referensi berhasil dihapus.',
        ], 200);
    }
}
