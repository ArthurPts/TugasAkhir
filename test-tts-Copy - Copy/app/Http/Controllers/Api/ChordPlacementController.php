<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateChordAudioJob;
use App\Models\Chord;
use App\Models\ChordAudioCache;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Support\ChordTransposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChordPlacementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lyric_line_id' => ['required', 'exists:lyric_lines,id'],
            'chord_id'      => ['required', 'exists:chords,id'],
            'position'      => ['required', 'integer'],
            'start_time'    => ['nullable', 'numeric'],
            'start_beat'    => ['nullable', 'integer'],
        ]);

        $placement = ChordPlacement::create($validated);

        return response()->json($placement->load('chord'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChordPlacement $chordPlacement): JsonResponse
    {
        $validated = $request->validate([
            'chord_id'   => ['sometimes', 'exists:chords,id'],
            'position'   => ['sometimes', 'integer'],
            'start_time' => ['nullable', 'numeric'],
            'start_beat' => ['nullable', 'integer'],
        ]);

        $chordPlacement->update($validated);

        return response()->json($chordPlacement->load('chord'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChordPlacement $chordPlacement): JsonResponse
    {
        $chordPlacement->delete();

        return response()->json(null, 204);
    }

    /**
     * Transpose chord pada satu marker sebanyak N semitone.
     * Kalau chord hasil transpose belum pernah ada di tabel `chords`,
     * dibuatkan barunya. Kalau audio-nya belum ter-cache, langsung
     * dispatch job generate — jadi frontend cukup poll `audio_ready`.
     */
    public function transpose(Request $request, ChordPlacement $chordPlacement): JsonResponse
    {
        $validated = $request->validate([
            'steps' => ['required', 'integer'],
            'voice' => ['nullable', 'string'],
        ]);

        $voice = $validated['voice'] ?? 'en-US-AriaNeural';
        $currentChord = $chordPlacement->chord;

        $newName = ChordTransposer::transpose($currentChord->name, $validated['steps']);

        $newChord = Chord::firstOrCreate(
            ['name' => $newName],
            ['pronunciation' => $newName], // fallback kasar, bisa dirapikan manual di tabel nanti
        );

        $chordPlacement->update(['chord_id' => $newChord->id]);

        $hash = ChordAudioCache::hashFor($newChord->pronunciation, $voice);
        $audioReady = ChordAudioCache::where('text_hash', $hash)->exists();

        if (! $audioReady) {
            GenerateChordAudioJob::dispatch($newChord->pronunciation, $voice);
        }

        return response()->json([
            'chord_placement_id' => $chordPlacement->id,
            'old_chord' => $currentChord->name,
            'new_chord' => $newChord->name,
            'audio_ready' => $audioReady,
        ]);
    }
}
