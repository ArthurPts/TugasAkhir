<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * How many of each model to generate. Tune these as needed.
     */
    protected const USER_COUNT = 10;

    protected const ARTIST_COUNT = 5;

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

        // Seed 3 lagu Indonesia asli beserta lirik & chord placements.
        // Kategori & chord sudah tersedia dari seeder di atas.
        $this->call(SongSeeder::class);
    }
}
