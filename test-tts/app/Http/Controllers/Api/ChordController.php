<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateChordAudioJob;
use App\Models\Chord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChordController extends Controller
{
    /**
     * Display a listing of chords.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Chord::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('pronunciation', 'like', "%{$search}%");
            });
        }

        $chords = $query->orderBy('name')->get()->map(function (Chord $chord) {
            $audioReady = !empty($chord->file_path) && Storage::disk('public')->exists($chord->file_path);
            return [
                'id'            => $chord->id,
                'name'          => $chord->name,
                'pronunciation' => $chord->pronunciation,
                'file_path'     => $chord->file_path,
                'audio_url'     => $chord->audio_url,
                'audio_ready'   => $audioReady,
            ];
        });

        return response()->json($chords);
    }

    /**
     * Store a newly created custom chord.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:45', 'unique:chords,name'],
            'pronunciation' => ['required', 'string', 'max:75'],
        ]);

        $chord = Chord::create([
            'name'          => $validated['name'],
            'pronunciation' => $validated['pronunciation'],
        ]);

        // Dispatch TTS generation using server configured voice
        GenerateChordAudioJob::dispatch($chord);

        return response()->json([
            'id'            => $chord->id,
            'name'          => $chord->name,
            'pronunciation' => $chord->pronunciation,
            'file_path'     => $chord->file_path,
            'audio_url'     => $chord->audio_url,
            'audio_ready'   => false,
        ], 201);
    }
}
