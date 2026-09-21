<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChordAudioCache;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SongController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Song::with('artist')->latest()->paginate(20)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'                       => ['required', 'string', 'max:75'],
            'artist_id'                   => ['required', 'exists:artists,id'],
            'default_key'                 => ['nullable', 'string', 'max:45'],
            'bpm'                         => ['required', 'integer', 'min:20', 'max:300'],
            'time_signature_numerator'    => ['nullable', 'integer'],
            'time_signature_denominator'  => ['nullable', 'integer'],
            'visibility'                  => ['required', 'in:public,private'],
        ]);

        // Sementara belum ada auth (itu Tahap 5) — fallback ke user pertama.
        $validated['user_id'] = $request->user()?->id ?? \App\Models\User::first()->id;

        $song = Song::create($validated);

        return response()->json($song, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Song $song): JsonResponse
    {
        return response()->json(
            $song->load('artist', 'sections.lyricLines.chordPlacements.chord')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Song $song): JsonResponse
    {
        $validated = $request->validate([
            'title'                       => ['sometimes', 'string', 'max:75'],
            'artist_id'                   => ['sometimes', 'exists:artists,id'],
            'default_key'                 => ['nullable', 'string', 'max:45'],
            'bpm'                         => ['sometimes', 'integer', 'min:20', 'max:300'],
            'time_signature_numerator'    => ['nullable', 'integer'],
            'time_signature_denominator'  => ['nullable', 'integer'],
            'visibility'                  => ['sometimes', 'in:public,private'],
        ]);

        $song->update($validated);

        return response()->json($song);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Song $song): JsonResponse
    {
        $song->delete();

        return response()->json(null, 204);
    }


    /**
     * Endpoint utama Tahap 3->4: gabungkan seluruh timeline lagu
     * (section -> line -> chord placement) dengan URL audio yang
     * sudah ter-generate, siap langsung dikonsumsi AudioEngine.
     */
    public function timeline(Song $song, Request $request): JsonResponse
    {
        $voice = $request->query('voice', 'en-US-AriaNeural');

        $song->load('sections.lyricLines.chordPlacements.chord');

        $markers = [];
        foreach ($song->sections as $section) {
            foreach ($section->lyricLines as $line) {
                foreach ($line->chordPlacements as $placement) {
                    $chord = $placement->chord;
                    $hash = ChordAudioCache::hashFor($chord->pronunciation, $voice);
                    $cache = ChordAudioCache::where('text_hash', $hash)->first();

                    $markers[] = [
                        'chord_placement_id' => $placement->id,
                        'section' => $section->name,
                        'line_number' => $line->line_number,
                        'beat' => $placement->start_beat,
                        'chord_name' => $chord->name,
                        'chord_text' => $chord->pronunciation,
                        'audio_url' => $cache?->url(),
                        'audio_ready' => $cache !== null,
                    ];
                }
            }
        }

        return response()->json([
            'song' => [
                'id' => $song->id,
                'title' => $song->title,
                'bpm' => $song->bpm,
                'time_signature_numerator' => $song->time_signature_numerator,
                'time_signature_denominator' => $song->time_signature_denominator,
            ],
            'markers' => $markers,
            'all_audio_ready' => collect($markers)->every(fn($m) => $m['audio_ready']),
        ]);
    }
}
