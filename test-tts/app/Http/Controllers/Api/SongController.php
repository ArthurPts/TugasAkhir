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
     * Display a listing of the resource (M1 Catalog + Search + Visibility).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Song::with(['artist', 'categories'])->latest();

        // 1. Aturan Visibility:
        // Guest: hanya lagu public.
        // User login: public + lagu private miliknya sendiri.
        // Admin: semua lagu sesuai filter jika diminta.
        if (! $user) {
            $query->where('visibility', 'public');
        } elseif ($user->role === 'admin') {
            if ($visibility = $request->query('visibility')) {
                $query->where('visibility', $visibility);
            }
        } else {
            $targetVisibility = $request->query('visibility');
            if ($targetVisibility === 'private') {
                $query->where('user_id', $user->id)->where('visibility', 'private');
            } elseif ($targetVisibility === 'public') {
                $query->where('visibility', 'public');
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('visibility', 'public')
                      ->orWhere(function ($sub) use ($user) {
                          $sub->where('user_id', $user->id)
                              ->where('visibility', 'private');
                      });
                });
            }
        }

        // Filter Lagu Milik User Sendiri (Lagu Saya)
        if ($request->boolean('mine') && $user) {
            $query->where('user_id', $user->id);
        }

        // 2. Filter Pencarian (Judul lagu atau Nama Artis)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('artist', function ($aq) use ($search) {
                      $aq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // 3. Filter Artist
        if ($artistId = $request->query('artist_id')) {
            $query->where('artist_id', $artistId);
        }

        // 4. Filter Kategori
        if ($category = $request->query('category')) {
            $query->whereHas('categories', function ($cq) use ($category) {
                if (is_numeric($category)) {
                    $cq->where('categories.id', $category);
                } else {
                    $cq->where('categories.name', $category);
                }
            });
        }

        $perPage = (int) $request->query('per_page', 20);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage (with Quota enforcement).
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

        $user = $request->user() ?? \App\Models\User::first();

        // Quota check untuk role user (maksimal 10 lagu aktif)
        if ($user && $user->role !== 'admin' && $user->songs()->count() >= 10) {
            return response()->json([
                'message' => 'Batas kuota upload lagu tercapai (maksimal 10 lagu aktif). Hapus lagu lama untuk menambah slot.',
            ], 403);
        }

        $validated['user_id'] = $user?->id ?? 1;

        $song = Song::create($validated);

        return response()->json($song, 201);
    }

    /**
     * Display the specified resource (with visibility check).
     */
    public function show(Request $request, Song $song): JsonResponse
    {
        $this->authorizeViewSong($request, $song);

        return response()->json(
            $song->load('artist', 'categories', 'sections.lyricLines.chordPlacements.chord')
        );
    }

    /**
     * Transpose seluruh lagu tanpa mengubah data chord placement di database.
     */
    public function transpose(Request $request, Song $song): JsonResponse
    {
        $this->authorizeViewSong($request, $song);

        $validated = $request->validate([
            'steps'    => ['required', 'integer'],
            'dispatch' => ['nullable', 'boolean'],
        ]);

        $dispatchMissingAudio = $request->boolean('dispatch', true);

        return response()->json(
            $this->buildTimelineResponse($song, (int) $validated['steps'], $dispatchMissingAudio)
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Song $song): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->role !== 'admin' && $user->id !== $song->user_id) {
            return response()->json(['message' => 'Tidak memiliki izin untuk mengubah lagu ini.'], 403);
        }

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
    public function destroy(Request $request, Song $song): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->role !== 'admin' && $user->id !== $song->user_id) {
            return response()->json(['message' => 'Tidak memiliki izin untuk menghapus lagu ini.'], 403);
        }

        $song->delete();

        return response()->json(null, 204);
    }

    /**
     * Endpoint utama timeline lagu.
     */
    public function timeline(Request $request, Song $song): JsonResponse
    {
        $this->authorizeViewSong($request, $song);

        return response()->json(
            $this->buildTimelineResponse($song, 0, false)
        );
    }

    private function authorizeViewSong(Request $request, Song $song): void
    {
        if ($song->visibility === 'private') {
            $user = $request->user();
            if (! $user || ($user->role !== 'admin' && $user->id !== $song->user_id)) {
                abort(404, 'Lagu tidak ditemukan.');
            }
        }
    }

    private function buildTimelineResponse(Song $song, int $steps = 0, bool $dispatchMissingAudio = false): array
    {
        $song->load([
            'sections' => fn ($query) => $query->orderBy('sequence'),
            'sections.lyricLines' => fn ($query) => $query->orderBy('line_number'),
            'sections.lyricLines.chordPlacements' => fn ($query) => $query->orderBy('position'),
            'sections.lyricLines.chordPlacements.chord',
        ]);

        $markers = [];
        $sectionsData = [];
        $dispatchedChordIds = [];

        foreach ($song->sections as $section) {
            $linesData = [];

            foreach ($section->lyricLines as $line) {
                $placementsData = [];

                foreach ($line->chordPlacements as $placement) {
                    if ($placement->start_beat === null) {
                        continue;
                    }

                    $originalChord = $placement->chord;
                    $chord = $originalChord;

                    if ($steps !== 0) {
                        $transposedName = ChordTransposer::transpose($originalChord->name, $steps);
                        $chord = Chord::firstOrCreate(
                            ['name' => $transposedName],
                            ['pronunciation' => $transposedName],
                        );
                    }

                    $audio = $this->resolveAudio($chord, $dispatchMissingAudio, $dispatchedChordIds);

                    $placementItem = [
                        'chord_placement_id' => $placement->id,
                        'position'           => $placement->position,
                        'beat'               => $placement->start_beat,
                        'chord_name'         => $chord->name,
                        'chord_text'         => $chord->pronunciation,
                        'audio_url'          => $audio['audio_url'],
                        'audio_ready'        => $audio['audio_ready'],
                    ];

                    $placementsData[] = $placementItem;
                    
                    $markers[] = array_merge([
                        'section'            => $section->name,
                        'section_sequence'   => $section->sequence,
                        'line_id'            => $line->id,
                        'line_number'        => $line->line_number,
                        'line_content'       => $line->content,
                    ], $placementItem);
                }
                
                $linesData[] = [
                    'line_id'     => $line->id,
                    'line_number' => $line->line_number,
                    'content'     => $line->content,
                    'placements'  => $placementsData,
                ];
            }

            $sectionsData[] = [
                'id'       => $section->id,
                'name'     => $section->name,
                'sequence' => $section->sequence,
                'lines'    => $linesData,
            ];

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
                'time_signature_numerator'   => $song->time_signature_numerator ?? 4,
                'time_signature_denominator' => $song->time_signature_denominator ?? 4,
                'file_path'                  => $song->file_path,
                'audio_url'                  => $song->audio_url,
                'reference_audio_url'        => $song->audio_url,
            ],
            'sections'        => $sectionsData,
            'markers'         => $markers,
            'all_audio_ready' => collect($markers)->every(fn($m) => $m['audio_ready']),
        ];
    }

    /**
     * Periksa ketersediaan file audio chord.
     * Jika belum ada file audionya dan dispatch diaktifkan, dispatch job TTS.
     */
    private function resolveAudio(Chord $chord, bool $dispatchMissingAudio, array &$dispatchedChordIds): array
    {
        $isReady = !empty($chord->file_path) && Storage::disk('public')->exists($chord->file_path);

        if (! $isReady && $dispatchMissingAudio && ! isset($dispatchedChordIds[$chord->id])) {
            $dispatchedChordIds[$chord->id] = true;
            GenerateChordAudioJob::dispatch($chord);
        }

        return [
            'audio_url'   => $chord->audio_url,
            'audio_ready' => $isReady,
        ];
    }
}
