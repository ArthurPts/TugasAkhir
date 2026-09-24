<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\Chord;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Song>
 */
class SongFactory extends Factory
{
    /**
     * Musical keys used for the optional default_key field.
     *
     * @var list<string>
     */
    protected static array $keys = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fn () => User::inRandomOrder()->value('id') ?? User::factory(),
            'title' => fake()->sentence(3),
            'artist_id' => fn () => Artist::inRandomOrder()->value('id') ?? Artist::factory(),
            'default_key' => fake()->optional(0.8)->randomElement(static::$keys),
            'bpm' => fake()->numberBetween(60, 180),
            'time_signature_numerator' => fake()->optional(0.9)->randomElement([2, 3, 4, 6]),
            'time_signature_denominator' => fake()->optional(0.9)->randomElement([4, 8]),
            'visibility' => fake()->randomElement(['public', 'private']),
        ];
    }

    /**
     * Indicate that the song is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
        ]);
    }

    /**
     * Indicate that the song is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
        ]);
    }

    /**
     * Build the full song hierarchy: 1 section, containing 1 lyric line,
     * containing 1 chord placement. Runs after every Song::factory()
     * create() call, since sections/lines/placements never get seeded
     * on their own — they only ever exist underneath a song.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Song $song) {
            $section = SongSection::create([
                'song_id' => $song->id,
                'name' => 'Verse 1',
                'sequence' => 1,
            ]);

            $line = LyricLine::create([
                'section_id' => $section->id,
                'line_number' => 1,
                'content' => fake()->sentence(6),
            ]);

            // Reuse an existing chord if one's already been seeded,
            // otherwise mint one — chords is a small, finite lookup
            // table, so we never want a factory spawning new rows freely.
            $chordId = Chord::inRandomOrder()->value('id')
                ?? Chord::factory()->create()->id;

            $line->chordPlacements()->create([
                'chord_id' => $chordId,
                'position' => 0,
                'start_time' => fake()->randomFloat(3, 0, 4),
                'start_beat' => 0,
            ]);
        });
    }
}
