<?php

namespace App\Services;

use App\Exceptions\PlacementCollisionException;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ChordPlacementCoordinateService
{
    /**
     * Menghitung offset display start beat untuk setiap baris lirik di lagu
     * berdasarkan algoritma auto-continuation (§5.2).
     *
     * @return array<int, int> [line_id => displayStartBeat]
     */
    public function calculateLineDisplayStarts(Song $song): array
    {
        $beatsPerBar = $song->time_signature_numerator ?: 4;
        $cumulativeBeat = 0;
        $lineDisplayStarts = [];

        $song->loadMissing([
            'sections' => fn ($q) => $q->orderBy('sequence'),
            'sections.lyricLines' => fn ($q) => $q->orderBy('line_number'),
            'sections.lyricLines.chordPlacements',
        ]);

        foreach ($song->sections as $section) {
            foreach ($section->lyricLines as $line) {
                $lineDisplayStarts[$line->id] = $cumulativeBeat;

                $beats = $line->chordPlacements
                    ->pluck('start_beat')
                    ->filter(fn ($b) => $b !== null);

                if ($beats->isEmpty()) {
                    $lineMaxBeatUsed = $cumulativeBeat + $beatsPerBar - 1;
                } else {
                    $lineMaxBeatUsed = (int) $beats->max();
                }

                $cumulativeBeat = (int) (ceil(($lineMaxBeatUsed + 1) / $beatsPerBar) * $beatsPerBar);
            }
        }

        return $lineDisplayStarts;
    }

    /**
     * Konversi koordinat position <-> start_beat (§5.1).
     *
     * @return array{position: int, start_beat: int}
     */
    public function convertCoordinates(
        LyricLine $line,
        ?int $position,
        ?int $startBeat,
        ?int $excludePlacementId = null
    ): array {
        if ($position === null && $startBeat === null) {
            throw new InvalidArgumentException('At least position or start_beat must be provided.');
        }

        if ($position === null && $excludePlacementId !== null) {
            $existingPlacement = $line->chordPlacements()->where('id', $excludePlacementId)->first();
            if ($existingPlacement) {
                $position = $existingPlacement->position;
                if ($startBeat === null) {
                    $startBeat = $existingPlacement->start_beat;
                }
            }
        }

        if ($position === null && $startBeat !== null) {
            $position = 0;
        }

        if ($position === null) {
            $position = 0;
        }

        return [
            'position'   => $position !== null ? max(0, (int) $position) : null,
            'start_beat' => $startBeat !== null ? max(0, (int) $startBeat) : null,
        ];
    }

    /**
     * Simpan atau update placement dengan deteksi collision dan transaksi atomik.
     *
     * @throws PlacementCollisionException
     */
    public function savePlacement(
        LyricLine $line,
        int $chordId,
        ?int $position,
        ?int $startBeat,
        bool $force = false,
        ?ChordPlacement $placement = null
    ): ChordPlacement {
        return DB::transaction(function () use ($line, $chordId, $position, $startBeat, $force, $placement) {
            $coords = $this->convertCoordinates($line, $position, $startBeat, $placement?->id);
            $finalPosition = $coords['position'];
            $finalBeat = $coords['start_beat'];

            $song = $line->section?->song;
            $songId = $song?->id;

            if ($songId && $finalBeat !== null) {
                $collidingQuery = ChordPlacement::whereHas('lyricLine.section', function ($q) use ($songId) {
                    $q->where('song_id', $songId);
                })
                ->where('start_beat', $finalBeat);

                if ($placement) {
                    $collidingQuery->where('id', '!=', $placement->id);
                }

                $colliding = $collidingQuery->lockForUpdate()->with('chord')->first();

                if ($colliding) {
                    if (! $force) {
                        throw new PlacementCollisionException($colliding, $finalBeat);
                    }

                    $collidingQuery->delete();
                }
            }

            if ($placement) {
                $placement->update([
                    'chord_id'   => $chordId,
                    'position'   => $finalPosition,
                    'start_beat' => $finalBeat,
                ]);
                return $placement->fresh(['chord']);
            }

            return $line->chordPlacements()->create([
                'chord_id'   => $chordId,
                'position'   => $finalPosition,
                'start_beat' => $finalBeat,
            ])->load('chord');
        });
    }
}
