<?php

namespace App\Http\Controllers;

use App\Models\Chord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChordAudioController extends Controller
{
    /**
     * Ambil URL audio untuk sekumpulan teks/nama chord sekaligus.
     * Hanya mengembalikan yang file audionya sudah siap.
     */
    public function batch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'texts'   => ['required', 'array'],
            'texts.*' => ['string'],
        ]);

        $texts = $validated['texts'];

        $chords = Chord::whereIn('pronunciation', $texts)
            ->orWhereIn('name', $texts)
            ->get();

        $result = $chords->filter(function ($chord) {
            return !empty($chord->file_path) && Storage::disk('public')->exists($chord->file_path);
        })->map(function ($chord) {
            return [
                'text' => $chord->pronunciation,
                'url'  => $chord->audio_url,
            ];
        });

        return response()->json($result->values());
    }
}
