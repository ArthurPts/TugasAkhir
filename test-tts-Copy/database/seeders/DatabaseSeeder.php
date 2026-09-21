<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Category;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * How many of each model to generate. Tune these as needed.
     */
    protected const USER_COUNT = 10;

    protected const ARTIST_COUNT = 5;

    protected const SONG_COUNT = 20;

    /**
     * Max number of categories randomly attached to each song.
     */
    protected const MAX_CATEGORIES_PER_SONG = 3;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lookup tables first — chords and categories are fixed,
        // real-world data, and Song's afterCreating hook depends
        // on chords already existing.
        $this->call([
            ChordSeeder::class,
            CategorySeeder::class,
        ]);

        // A known account for logging in during development.
        User::factory()->admin()->create([
            'username' => 'admin',
            'email' => 'admin@chordcue.test',
        ]);

        User::factory(self::USER_COUNT)->create();
        Artist::factory(self::ARTIST_COUNT)->create();

        // Each Song::factory()->create() call also builds its own
        // section -> lyric line -> chord placement via the
        // afterCreating hook on SongFactory.
        Song::factory(self::SONG_COUNT)
            ->create()
            ->each(function (Song $song) {
                $song->categories()->attach(
                    Category::inRandomOrder()
                        ->take(random_int(1, self::MAX_CATEGORIES_PER_SONG))
                        ->pluck('id'),
                );
            });
    }
}
