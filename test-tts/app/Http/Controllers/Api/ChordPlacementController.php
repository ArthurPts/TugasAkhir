<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PlacementCollisionException;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateChordAudioJob;
use App\Models\Chord;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Services\ChordPlacementCoordinateService;
use App\Support\ChordTransposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChordPlacementController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, ChordPlacementCoordinateService $coordinateService): JsonResponse
    {
        $validated = $request->validate([
            'lyric_line_id' => ['required', 'exists:lyric_lines,id'],
            'chord_id'      => ['required', 'exists:chords,id'],
            'position'      => ['nullable', 'integer', 'min:0'],
            'start_beat'    => ['nullable', 'integer', 'min:0'],
            'force'         => ['nullable', 'boolean'],
        ]);

        if (! isset($validated['position']) && ! isset($validated['start_beat'])) {
            return response()->json([
                'message' => 'Either position or start_beat must be provided.',
                'errors' => [
                    'position' => ['Either position or start_beat must be provided.'],
                ],
            ], 422);
        }

        $line = LyricLine::with('section.song')->findOrFail($validated['lyric_line_id']);
        $force = (bool) ($validated['force'] ?? false);

        try {
            $placement = $coordinateService->savePlacement(
                $line,
                (int) $validated['chord_id'],
                isset($validated['position']) ? (int) $validated['position'] : null,
                isset($validated['start_beat']) ? (int) $validated['start_beat'] : null,
                $force
            );
        } catch (PlacementCollisionException $e) {
            return response()->json([
                'message' => 'Chord placement collision detected' . ($e->targetBeat !== null ? " at beat {$e->targetBeat}" : '') . '.',
                'collision' => true,
                'colliding_placement' => [
                    'id'            => $e->collidingPlacement->id,
                    'start_beat'    => $e->collidingPlacement->start_beat,
                    'chord_id'      => $e->collidingPlacement->chord_id,
                    'chord_name'    => $e->collidingPlacement->chord?->name,
                    'lyric_line_id' => $e->collidingPlacement->lyric_line_id,
                ],
            ], 409);
        }

        // Dispatch TTS generation if audio not ready
        $chord = $placement->chord;
        if ($chord) {
            $audioReady = !empty($chord->file_path) && Storage::disk('public')->exists($chord->file_path);
            if (! $audioReady) {
                GenerateChordAudioJob::dispatch($chord);
            }
        }

        return response()->json($placement, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChordPlacement $chordPlacement, ChordPlacementCoordinateService $coordinateService): JsonResponse
    {
        $validated = $request->validate([
            'chord_id'   => ['sometimes', 'exists:chords,id'],
            'position'   => ['nullable', 'integer', 'min:0'],
            'start_beat' => ['nullable', 'integer', 'min:0'],
            'force'      => ['nullable', 'boolean'],
        ]);

        $line = $chordPlacement->lyricLine()->with('section.song')->firstOrFail();
        $chordId = (int) ($validated['chord_id'] ?? $chordPlacement->chord_id);

        if (array_key_exists('start_beat', $validated) && ! array_key_exists('position', $validated)) {
            $position = null;
            $startBeat = $validated['start_beat'] !== null ? (int) $validated['start_beat'] : null;
        } elseif (array_key_exists('position', $validated) && ! array_key_exists('start_beat', $validated)) {
            $position = $validated['position'] !== null ? (int) $validated['position'] : null;
            $startBeat = null;
        } else {
            $position = array_key_exists('position', $validated)
                ? ($validated['position'] !== null ? (int) $validated['position'] : null)
                : $chordPlacement->position;
            $startBeat = array_key_exists('start_beat', $validated)
                ? ($validated['start_beat'] !== null ? (int) $validated['start_beat'] : null)
                : $chordPlacement->start_beat;
        }

        $force = (bool) ($validated['force'] ?? false);

        try {
            $placement = $coordinateService->savePlacement(
                $line,
                $chordId,
                $position,
                $startBeat,
                $force,
                $chordPlacement
            );
        } catch (PlacementCollisionException $e) {
            return response()->json([
                'message' => 'Chord placement collision detected' . ($e->targetBeat !== null ? " at beat {$e->targetBeat}" : '') . '.',
                'collision' => true,
                'colliding_placement' => [
                    'id'            => $e->collidingPlacement->id,
                    'start_beat'    => $e->collidingPlacement->start_beat,
                    'chord_id'      => $e->collidingPlacement->chord_id,
                    'chord_name'    => $e->collidingPlacement->chord?->name,
                    'lyric_line_id' => $e->collidingPlacement->lyric_line_id,
                ],
            ], 409);
        }

        return response()->json($placement);
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
     *
     * Voice TTS dikontrol server via config, tidak dari client.
     */
    public function transpose(Request $request, ChordPlacement $chordPlacement): JsonResponse
    {
        $validated = $request->validate([
            'steps' => ['required', 'integer'],
        ]);

        $currentChord = $chordPlacement->chord;

        $newName = ChordTransposer::transpose($currentChord->name, $validated['steps']);

        $newChord = Chord::firstOrCreate(
            ['name' => $newName],
            ['pronunciation' => $newName],
        );

        $audioReady = !empty($newChord->file_path) && Storage::disk('public')->exists($newChord->file_path);

        if (! $audioReady) {
            GenerateChordAudioJob::dispatch($newChord);
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
