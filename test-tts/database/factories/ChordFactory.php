<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chord>
 */
class ChordFactory extends Factory
{
    /**
     * Common chords paired with a human-readable pronunciation.
     * Kept here so the factory can hand out real chord names instead
     * of random words (chords are a finite, real-world vocabulary).
     *
     * @var array<string, string>
     */
    protected static array $chords = [
        'C' => 'C Major',
        'Cm' => 'C Minor',
        'C#' => 'C Sharp Major',
        'D' => 'D Major',
        'Dm' => 'D Minor',
        'D#' => 'D Sharp Major',
        'E' => 'E Major',
        'Em' => 'E Minor',
        'F' => 'F Major',
        'Fm' => 'F Minor',
        'F#' => 'F Sharp Major',
        'G' => 'G Major',
        'Gm' => 'G Minor',
        'G#' => 'G Sharp Major',
        'A' => 'A Major',
        'Am' => 'A Minor',
        'A#' => 'A Sharp Major',
        'B' => 'B Major',
        'Bm' => 'B Minor',
        'Cmaj7' => 'C Major Seventh',
        'Dm7' => 'D Minor Seventh',
        'G7' => 'G Dominant Seventh',
        'Asus4' => 'A Suspended Fourth',
        'Esus2' => 'E Suspended Second',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $keys = null;
        static $cursor = 0;
        if ($keys === null) {
            $keys = array_keys(static::$chords);
            shuffle($keys);
        }

        $name = $keys[$cursor % count($keys)];
        $cursor++;

        return [
            'name' => $name,
            'pronunciation' => static::$chords[$name],
        ];
    }
}
