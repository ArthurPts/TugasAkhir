<?php

namespace Database\Seeders;

use App\Models\Chord;
use Illuminate\Database\Seeder;

class ChordSeeder extends Seeder
{
    /**
     * Real chord names paired with a human-readable pronunciation.
     * This is fixed, real-world lookup data (not random test data),
     * which is why it lives in the seeder rather than the factory.
     *
     * @var array<string, string>
     */
    protected array $chords = [
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
     * Run the database seeder.
     */
    public function run(): void
    {
        foreach ($this->chords as $name => $pronunciation) {
            Chord::firstOrCreate(
                ['name' => $name],
                ['pronunciation' => $pronunciation],
            );
        }
    }
}
