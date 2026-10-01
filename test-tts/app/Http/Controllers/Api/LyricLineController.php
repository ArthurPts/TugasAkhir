<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LyricLine;
use App\Models\SongSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LyricLineController extends Controller
{
    /**
     * Store a newly created lyric line under a section.
     */
    public function store(Request $request, SongSection $section): JsonResponse
    {
        $validated = $request->validate([
            'content'     => ['required', 'string'],
            'line_number' => ['nullable', 'integer'],
        ]);

        $lineNumber = $validated['line_number'] ?? (($section->lyricLines()->max('line_number') ?? 0) + 1);

        $line = $section->lyricLines()->create([
            'content'     => $validated['content'],
            'line_number' => $lineNumber,
        ]);

        return response()->json($line, 201);
    }

    /**
     * Update the specified lyric line.
     */
    public function update(Request $request, LyricLine $line): JsonResponse
    {
        $validated = $request->validate([
            'content'     => ['sometimes', 'string'],
            'line_number' => ['sometimes', 'integer'],
        ]);

        $line->update($validated);

        return response()->json($line);
    }

    /**
     * Remove the specified lyric line (cascade to placements).
     */
    public function destroy(LyricLine $line): JsonResponse
    {
        DB::transaction(function () use ($line) {
            $line->chordPlacements()->delete();
            $line->delete();
        });

        return response()->json(null, 204);
    }
}
