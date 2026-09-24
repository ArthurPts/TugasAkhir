<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateChordAudioJob;
use App\Models\Chord;
use App\Models\Song;
use App\Support\ChordTransposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'file_path'                   => ['nullable', 'string', 'max:255'],
            'visibility'                  => ['required', 'in:public,private'],
        ]);

        // Sementara fallback ke user pertama jika belum ada authentication
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
            'steps'    => ['required', 'integer'],
            'voice'    => ['nullable', 'string'],
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
            'file_path'                   => ['nullable', 'string', 'max:255'],
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
     * Endpoint utama timeline lagu: gabungkan seluruh struktur lagu
     * (section -> line -> chord placement) beserta URL audio chord dan audio lagu.
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
        $dispatchedChordIds = [];

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

                    $audio = $this->resolveAudio($chord, $voice, $dispatchMissingAudio, $dispatchedChordIds);

                    $markers[] = [
                        'chord_placement_id' => $placement->id,
                        'section'            => $section->name,
                        'section_sequence'   => $section->sequence,
                        'line_id'            => $line->id,
                        'line_number'        => $line->line_number,
                        'line_content'       => $line->content,
                        'position'           => $placement->position,
                        'beat'               => $placement->start_beat,
                        'chord_name'         => $chord->name,
                        'chord_text'         => $chord->pronunciation,
                        'audio_url'          => $audio['audio_url'],
                        'audio_ready'        => $audio['audio_ready'],
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
                'id'                         => $song->id,
                'title'                      => $song->title,
                'bpm'                        => $song->bpm,
                'time_signature_numerator'   => $song->time_signature_numerator,
                'time_signature_denominator' => $song->time_signature_denominator,
                'file_path'                  => $song->file_path,
                'audio_url'                  => $song->audio_url,
            ],
            'markers'         => $markers,
            'all_audio_ready' => collect($markers)->every(fn($m) => $m['audio_ready']),
        ];
    }

    /**
     * Periksa ketersediaan file audio chord.
     * Jika belum ada file audionya dan dispatch diaktifkan, dispatch job TTS.
     */
    private function resolveAudio(Chord $chord, string $voice, bool $dispatchMissingAudio, array &$dispatchedChordIds): array
    {
        $isReady = !empty($chord->file_path) && Storage::disk('public')->exists($chord->file_path);

        if (! $isReady && $dispatchMissingAudio && ! isset($dispatchedChordIds[$chord->id])) {
            $dispatchedChordIds[$chord->id] = true;
            GenerateChordAudioJob::dispatch($chord, $voice);
        }

        return [
            'audio_url'   => $chord->audio_url,
            'audio_ready' => $isReady,
        ];
    }
}
