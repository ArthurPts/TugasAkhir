<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChordPlacement;
use App\Models\Song;
use App\Models\SongSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SongSectionController extends Controller
{
    /**
     * Display a listing of sections for a song.
     */
    public function index(Song $song): JsonResponse
    {
        $sections = $song->sections()->with(['lyricLines.chordPlacements.chord'])->orderBy('sequence')->get();
        return response()->json($sections);
    }

    /**
     * Store a newly created section under a song.
     */
    public function store(Request $request, Song $song): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:45'],
            'sequence' => ['nullable', 'integer'],
        ]);

        $sequence = $validated['sequence'] ?? (($song->sections()->max('sequence') ?? 0) + 1);

        $section = $song->sections()->create([
            'name'     => $validated['name'],
            'sequence' => $sequence,
        ]);

        return response()->json($section, 201);
    }

    /**
     * Update the specified section.
     */
    public function update(Request $request, SongSection $section): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['sometimes', 'string', 'max:45'],
            'sequence' => ['sometimes', 'integer'],
        ]);

        $section->update($validated);

        return response()->json($section);
    }

    /**
     * Remove the specified section (cascade to lines and placements).
     */
    public function destroy(SongSection $section): JsonResponse
    {
        DB::transaction(function () use ($section) {
            $lineIds = $section->lyricLines()->pluck('id');
            ChordPlacement::whereIn('lyric_line_id', $lineIds)->delete();
            $section->lyricLines()->delete();
            $section->delete();
        });

        return response()->json(null, 204);
    }
}
