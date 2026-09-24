<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateChordAudioJob;
use App\Models\Chord;
use App\Models\ChordPlacement;
use App\Support\ChordTransposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChordPlacementController extends Controller
{
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
            ['pronunciation' => $newName],
        );

        $audioReady = !empty($newChord->file_path) && Storage::disk('public')->exists($newChord->file_path);

        if (! $audioReady) {
            GenerateChordAudioJob::dispatch($newChord, $voice);
        }

        return response()->json([
            'chord_placement_id' => $chordPlacement->id,
            'old_chord'          => $currentChord->name,
            'chord_name'         => $newChord->name,
            'chord_text'         => $newChord->pronunciation,
            'audio_url'          => $newChord->audio_url,
            'audio_ready'        => $audioReady,
        ]);
    }
}
