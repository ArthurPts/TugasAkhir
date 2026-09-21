<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateChordAudioJob;
use App\Models\Chord;
use App\Models\ChordAudioCache;
use App\Models\Song;
use App\Support\ChordTransposer;
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
     * Transpose seluruh lagu tanpa mengubah data chord placement di database.
     */
    public function transpose(Song $song, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'steps' => ['required', 'integer'],
            'voice' => ['nullable', 'string'],
            'dispatch' => ['nullable', 'boolean'],
        ]);

        $voice = $validated['voice'] ?? 'en-US-AriaNeural';
        $dispatchMissingAudio = $request->boolean('dispatch', true);

        return response()->json(
            $this->buildTimelineResponse($song, $voice, (int) $validated['steps'], $dispatchMissingAudio)
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

        return response()->json(
            $this->buildTimelineResponse($song, $voice, 0, false)
        );
    }

    private function buildTimelineResponse(Song $song, string $voice, int $steps = 0, bool $dispatchMissingAudio = false): array
    {
        $song->load([
            'sections' => fn ($query) => $query->orderBy('sequence'),
            'sections.lyricLines' => fn ($query) => $query->orderBy('line_number'),
            'sections.lyricLines.chordPlacements' => fn ($query) => $query->orderBy('position'),
            'sections.lyricLines.chordPlacements.chord',
        ]);

        $markers = [];
        $dispatchedHashes = [];

        foreach ($song->sections as $section) {
            foreach ($section->lyricLines as $line) {
                foreach ($line->chordPlacements as $placement) {
                    $originalChord = $placement->chord;
                    $chord = $originalChord;

                    if ($steps !== 0) {
                        $transposedName = ChordTransposer::transpose($originalChord->name, $steps);
                        $chord = Chord::firstOrCreate(
                            ['name' => $transposedName],
                            ['pronunciation' => $transposedName],
                        );
                    }

                    $audio = $this->resolveAudio($chord, $voice, $dispatchMissingAudio, $dispatchedHashes);

                    $markers[] = [
                        'chord_placement_id' => $placement->id,
                        'section' => $section->name,
                        'section_sequence' => $section->sequence,
                        'line_id' => $line->id,
                        'line_number' => $line->line_number,
                        'line_content' => $line->content,
                        'position' => $placement->position,
                        'beat' => $placement->start_beat,
                        'chord_name' => $chord->name,
                        'chord_text' => $chord->pronunciation,
                        'audio_url' => $audio['audio_url'],
                        'audio_ready' => $audio['audio_ready'],
                    ];
                }
            }
        }

        usort($markers, static function (array $left, array $right): int {
            return [$left['section_sequence'], $left['line_number'], $left['position']]
                <=> [$right['section_sequence'], $right['line_number'], $right['position']];
        });

        return [
            'song' => [
                'id' => $song->id,
                'title' => $song->title,
                'bpm' => $song->bpm,
                'time_signature_numerator' => $song->time_signature_numerator,
                'time_signature_denominator' => $song->time_signature_denominator,
            ],
            'markers' => $markers,
            'all_audio_ready' => collect($markers)->every(fn($m) => $m['audio_ready']),
        ];
    }

    /**
     * Resolve cache/audio URL untuk chord yang akan diputar.
     */
    private function resolveAudio(Chord $chord, string $voice, bool $dispatchMissingAudio, array &$dispatchedHashes): array
    {
        $hash = ChordAudioCache::hashFor($chord->pronunciation, $voice);
        $cache = ChordAudioCache::where('text_hash', $hash)->first();

        if (! $cache && $dispatchMissingAudio && ! isset($dispatchedHashes[$hash])) {
            $dispatchedHashes[$hash] = true;
            GenerateChordAudioJob::dispatch($chord->pronunciation, $voice);
        }

        return [
            'audio_url' => $cache?->url(),
            'audio_ready' => $cache !== null,
        ];
    }
}
