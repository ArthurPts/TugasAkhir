<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Real category names grouped by type. Fixed lookup data
     * (not random test data), so it lives in the seeder rather
     * than the factory — same reasoning as ChordSeeder.
     *
     * @var array<string, list<string>>
     */
    protected array $categories = [
        'mood' => ['Happy', 'Sad', 'Energetic', 'Calm', 'Romantic', 'Melancholic'],
        'location' => ['Live', 'Studio', 'Acoustic', 'Concert'],
        'genre' => ['Pop', 'Rock', 'Jazz', 'Folk', 'Worship', 'Gospel', 'EDM', 'Hip Hop'],
    ];

    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        foreach ($this->categories as $type => $names) {
            foreach ($names as $name) {
                Category::firstOrCreate(
                    ['name' => $name],
                    ['type' => $type],
                );
            }
        }
    }
}
