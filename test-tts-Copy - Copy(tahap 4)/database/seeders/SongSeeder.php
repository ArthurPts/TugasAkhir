<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Category;
use App\Models\Chord;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use Illuminate\Database\Seeder;

class SongSeeder extends Seeder
{
    /**
     * Data 3 lagu Indonesia asli beserta lirik dan chord.
     *
     * Struktur setiap lagu:
     *   title       – judul lagu
     *   artist      – nama artis/band
     *   default_key – nada dasar
     *   bpm         – beats per minute
     *   time_sig    – [numerator, denominator]
     *   categories  – nama-nama kategori (sudah ada via CategorySeeder)
     *   sections    – array section (verse/chorus/dll)
     *     name      – nama section
     *     lines     – array baris lirik
     *       content – teks lirik
     *       chords  – chord placements: [['chord' => 'C', 'position' => 0, 'beat' => 1], ...]
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $songs = [
        // ─────────────────────────────────────────────────────
        // 1. Pelangi di Matamu – Jamrud (1999)
        //    Key: G  |  BPM: 90  |  4/4
        // ─────────────────────────────────────────────────────
        [
            'title'      => 'Pelangi di Matamu',
            'artist'     => 'Jamrud',
            'default_key'=> 'G',
            'bpm'        => 90,
            'time_sig'   => [4, 4],
            'categories' => ['Pop', 'Rock', 'Happy'],
            'sections'   => [
                [
                    'name'  => 'Verse 1',
                    'lines' => [
                        [
                            'content' => 'Sudah ku tahu semua tentangmu',
                            'chords'  => [
                                ['chord' => 'G',  'position' => 0,  'beat' => 1],
                                ['chord' => 'D',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Kau memang cantik dan aku tergila',
                            'chords'  => [
                                ['chord' => 'Em', 'position' => 0,  'beat' => 1],
                                ['chord' => 'C',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Senyummu sungguh membuatku mabuk',
                            'chords'  => [
                                ['chord' => 'G',  'position' => 0,  'beat' => 1],
                                ['chord' => 'D',  'position' => 8,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Jantungku berdegup tak menentu',
                            'chords'  => [
                                ['chord' => 'C',  'position' => 0,  'beat' => 1],
                                ['chord' => 'D',  'position' => 8,  'beat' => 3],
                            ],
                        ],
                    ],
                ],
                [
                    'name'  => 'Chorus',
                    'lines' => [
                        [
                            'content' => 'Pelangi pelangi di matamu',
                            'chords'  => [
                                ['chord' => 'G',  'position' => 0,  'beat' => 1],
                                ['chord' => 'Em', 'position' => 8,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Sungguh indah warnanya',
                            'chords'  => [
                                ['chord' => 'C',  'position' => 0,  'beat' => 1],
                                ['chord' => 'D',  'position' => 7,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Pelangi pelangi di matamu',
                            'chords'  => [
                                ['chord' => 'G',  'position' => 0,  'beat' => 1],
                                ['chord' => 'Em', 'position' => 8,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Membuatku terpesona',
                            'chords'  => [
                                ['chord' => 'C',  'position' => 0,  'beat' => 1],
                                ['chord' => 'G',  'position' => 7,  'beat' => 3],
                            ],
                        ],
                    ],
                ],
                [
                    'name'  => 'Verse 2',
                    'lines' => [
                        [
                            'content' => 'Ku ingin selalu bersamamu',
                            'chords'  => [
                                ['chord' => 'G',  'position' => 0,  'beat' => 1],
                                ['chord' => 'D',  'position' => 8,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Menemani hari harimu',
                            'chords'  => [
                                ['chord' => 'Em', 'position' => 0,  'beat' => 1],
                                ['chord' => 'C',  'position' => 7,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Karena kau begitu sempurna',
                            'chords'  => [
                                ['chord' => 'G',  'position' => 0,  'beat' => 1],
                                ['chord' => 'D',  'position' => 8,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Di hatiku hanya ada dirimu',
                            'chords'  => [
                                ['chord' => 'C',  'position' => 0,  'beat' => 1],
                                ['chord' => 'D',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                    ],
                ],
                [
                    'name'  => 'Bridge',
                    'lines' => [
                        [
                            'content' => 'Dan ku tak bisa jauh darimu',
                            'chords'  => [
                                ['chord' => 'Em', 'position' => 0,  'beat' => 1],
                                ['chord' => 'Bm', 'position' => 8,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Karena cintaku milikmu',
                            'chords'  => [
                                ['chord' => 'C',  'position' => 0,  'beat' => 1],
                                ['chord' => 'D',  'position' => 7,  'beat' => 3],
                            ],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────
        // 2. Aku Milikmu – Dewa 19 (1992)
        //    Key: D  |  BPM: 75  |  4/4
        // ─────────────────────────────────────────────────────
        [
            'title'      => 'Aku Milikmu',
            'artist'     => 'Dewa 19',
            'default_key'=> 'D',
            'bpm'        => 75,
            'time_sig'   => [4, 4],
            'categories' => ['Pop', 'Romantic', 'Melancholic'],
            'sections'   => [
                [
                    'name'  => 'Verse 1',
                    'lines' => [
                        [
                            'content' => 'Bila ku ingat saat bersamamu',
                            'chords'  => [
                                ['chord' => 'D',  'position' => 0,  'beat' => 1],
                                ['chord' => 'A',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Hati ini terasa damai',
                            'chords'  => [
                                ['chord' => 'Bm', 'position' => 0,  'beat' => 1],
                                ['chord' => 'G',  'position' => 8,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Ku tak percaya engkau telah pergi',
                            'chords'  => [
                                ['chord' => 'D',  'position' => 0,  'beat' => 1],
                                ['chord' => 'A',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Meninggalkan kenangan indah',
                            'chords'  => [
                                ['chord' => 'G',  'position' => 0,  'beat' => 1],
                                ['chord' => 'A',  'position' => 7,  'beat' => 3],
                            ],
                        ],
                    ],
                ],
                [
                    'name'  => 'Pre-Chorus',
                    'lines' => [
                        [
                            'content' => 'Mungkin memang sudah takdir kita',
                            'chords'  => [
                                ['chord' => 'G',  'position' => 0,  'beat' => 1],
                                ['chord' => 'D',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Harus berpisah selamanya',
                            'chords'  => [
                                ['chord' => 'Em', 'position' => 0,  'beat' => 1],
                                ['chord' => 'A',  'position' => 8,  'beat' => 3],
                            ],
                        ],
                    ],
                ],
                [
                    'name'  => 'Chorus',
                    'lines' => [
                        [
                            'content' => 'Ku tetap milikmu selamanya',
                            'chords'  => [
                                ['chord' => 'D',  'position' => 0,  'beat' => 1],
                                ['chord' => 'A',  'position' => 8,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Walau kini tak lagi bersama',
                            'chords'  => [
                                ['chord' => 'Bm', 'position' => 0,  'beat' => 1],
                                ['chord' => 'G',  'position' => 8,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Cintaku hanya untukmu',
                            'chords'  => [
                                ['chord' => 'G',  'position' => 0,  'beat' => 1],
                                ['chord' => 'D',  'position' => 7,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Aku milikmu untuk selamanya',
                            'chords'  => [
                                ['chord' => 'Em', 'position' => 0,  'beat' => 1],
                                ['chord' => 'A',  'position' => 9,  'beat' => 3],
                                ['chord' => 'D',  'position' => 18, 'beat' => 1],
                            ],
                        ],
                    ],
                ],
                [
                    'name'  => 'Verse 2',
                    'lines' => [
                        [
                            'content' => 'Setiap malam ku selalu bermimpi',
                            'chords'  => [
                                ['chord' => 'D',  'position' => 0,  'beat' => 1],
                                ['chord' => 'A',  'position' => 10, 'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Tentang wajahmu yang cantik',
                            'chords'  => [
                                ['chord' => 'Bm', 'position' => 0,  'beat' => 1],
                                ['chord' => 'G',  'position' => 7,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Ku rindukan sentuhan tanganmu',
                            'chords'  => [
                                ['chord' => 'D',  'position' => 0,  'beat' => 1],
                                ['chord' => 'A',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Yang hangat menyentuh hatiku',
                            'chords'  => [
                                ['chord' => 'G',  'position' => 0,  'beat' => 1],
                                ['chord' => 'A',  'position' => 8,  'beat' => 3],
                            ],
                        ],
                    ],
                ],
                [
                    'name'  => 'Outro',
                    'lines' => [
                        [
                            'content' => 'Karena aku milikmu',
                            'chords'  => [
                                ['chord' => 'D',  'position' => 0,  'beat' => 1],
                                ['chord' => 'A',  'position' => 7,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Selamanya milikmu',
                            'chords'  => [
                                ['chord' => 'Bm', 'position' => 0,  'beat' => 1],
                                ['chord' => 'G',  'position' => 7,  'beat' => 3],
                                ['chord' => 'D',  'position' => 14, 'beat' => 1],
                            ],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────
        // 3. Tentang Kita – Peterpan (2004, album Bintang di Surga)
        //    Key: Am  |  BPM: 80  |  4/4
        // ─────────────────────────────────────────────────────
        [
            'title'      => 'Tentang Kita',
            'artist'     => 'Peterpan',
            'default_key'=> 'Am',
            'bpm'        => 80,
            'time_sig'   => [4, 4],
            'categories' => ['Pop', 'Melancholic', 'Sad'],
            'sections'   => [
                [
                    'name'  => 'Verse 1',
                    'lines' => [
                        [
                            'content' => 'Ku menunggu kau disini',
                            'chords'  => [
                                ['chord' => 'Am', 'position' => 0,  'beat' => 1],
                                ['chord' => 'F',  'position' => 7,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Seperti biasanya kita dulu',
                            'chords'  => [
                                ['chord' => 'C',  'position' => 0,  'beat' => 1],
                                ['chord' => 'G',  'position' => 8,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Duduk berdua menikmati senja',
                            'chords'  => [
                                ['chord' => 'Am', 'position' => 0,  'beat' => 1],
                                ['chord' => 'F',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Di tepi pantai ini',
                            'chords'  => [
                                ['chord' => 'C',  'position' => 0,  'beat' => 1],
                                ['chord' => 'G',  'position' => 6,  'beat' => 3],
                            ],
                        ],
                    ],
                ],
                [
                    'name'  => 'Verse 2',
                    'lines' => [
                        [
                            'content' => 'Ku ingat cerita kita waktu itu',
                            'chords'  => [
                                ['chord' => 'Am', 'position' => 0,  'beat' => 1],
                                ['chord' => 'F',  'position' => 10, 'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Kau berjanji takkan pergi',
                            'chords'  => [
                                ['chord' => 'C',  'position' => 0,  'beat' => 1],
                                ['chord' => 'G',  'position' => 7,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Namun semua kini telah berlalu',
                            'chords'  => [
                                ['chord' => 'Am', 'position' => 0,  'beat' => 1],
                                ['chord' => 'F',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Kau sudah tak di sini lagi',
                            'chords'  => [
                                ['chord' => 'G',  'position' => 0,  'beat' => 1],
                                ['chord' => 'Em', 'position' => 7,  'beat' => 3],
                            ],
                        ],
                    ],
                ],
                [
                    'name'  => 'Chorus',
                    'lines' => [
                        [
                            'content' => 'Tentang kita yang tak mungkin bersatu',
                            'chords'  => [
                                ['chord' => 'F',  'position' => 0,  'beat' => 1],
                                ['chord' => 'C',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Tentang rasa yang tak pernah padam',
                            'chords'  => [
                                ['chord' => 'Am', 'position' => 0,  'beat' => 1],
                                ['chord' => 'G',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Tentang cinta yang terlanjur dalam',
                            'chords'  => [
                                ['chord' => 'F',  'position' => 0,  'beat' => 1],
                                ['chord' => 'C',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Di hatiku hanya ada tentang kita',
                            'chords'  => [
                                ['chord' => 'G',  'position' => 0,  'beat' => 1],
                                ['chord' => 'Am', 'position' => 9,  'beat' => 3],
                            ],
                        ],
                    ],
                ],
                [
                    'name'  => 'Bridge',
                    'lines' => [
                        [
                            'content' => 'Mungkin ini semua memang takdirku',
                            'chords'  => [
                                ['chord' => 'Dm', 'position' => 0,  'beat' => 1],
                                ['chord' => 'Am', 'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Untuk mencintaimu dari kejauhan',
                            'chords'  => [
                                ['chord' => 'F',  'position' => 0,  'beat' => 1],
                                ['chord' => 'G',  'position' => 9,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Ku kan terus menunggu',
                            'chords'  => [
                                ['chord' => 'C',  'position' => 0,  'beat' => 1],
                                ['chord' => 'G',  'position' => 7,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Meski kau tak pernah kembali',
                            'chords'  => [
                                ['chord' => 'Am', 'position' => 0,  'beat' => 1],
                                ['chord' => 'F',  'position' => 8,  'beat' => 3],
                                ['chord' => 'G',  'position' => 14, 'beat' => 1],
                            ],
                        ],
                    ],
                ],
                [
                    'name'  => 'Outro',
                    'lines' => [
                        [
                            'content' => 'Tentang kita',
                            'chords'  => [
                                ['chord' => 'Am', 'position' => 0,  'beat' => 1],
                                ['chord' => 'G',  'position' => 6,  'beat' => 3],
                            ],
                        ],
                        [
                            'content' => 'Selamanya tentang kita',
                            'chords'  => [
                                ['chord' => 'F',  'position' => 0,  'beat' => 1],
                                ['chord' => 'C',  'position' => 7,  'beat' => 3],
                                ['chord' => 'Am', 'position' => 13, 'beat' => 1],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil user admin sebagai pemilik lagu dummy (sudah dibuat di DatabaseSeeder)
        $owner = User::where('username', 'admin')->first()
            ?? User::first();

        if (! $owner) {
            $this->command?->error('Tidak ada user di DB. Jalankan DatabaseSeeder terlebih dahulu.');
            return;
        }

        // Preload semua chord sekali agar tidak query per baris
        $chordMap = Chord::all()->keyBy('name');

        foreach ($this->songs as $songData) {
            // ── 1. Artist (firstOrCreate agar idempotent) ──────────────
            $artist = Artist::firstOrCreate(['name' => $songData['artist']]);

            // ── 2. Song ────────────────────────────────────────────────
            $song = Song::create([
                'user_id'                    => $owner->id,
                'title'                      => $songData['title'],
                'artist_id'                  => $artist->id,
                'default_key'                => $songData['default_key'],
                'bpm'                        => $songData['bpm'],
                'time_signature_numerator'   => $songData['time_sig'][0],
                'time_signature_denominator' => $songData['time_sig'][1],
                'visibility'                 => 'public',
            ]);

            // ── 3. Categories ──────────────────────────────────────────
            $categoryIds = Category::whereIn('name', $songData['categories'])->pluck('id');
            $song->categories()->sync($categoryIds);

            // ── 4. Sections → LyricLines → ChordPlacements ─────────────
            foreach ($songData['sections'] as $sectionIdx => $sectionData) {
                $section = SongSection::create([
                    'song_id'  => $song->id,
                    'name'     => $sectionData['name'],
                    'sequence' => $sectionIdx + 1,
                ]);

                foreach ($sectionData['lines'] as $lineIdx => $lineData) {
                    $lyricLine = LyricLine::create([
                        'section_id'  => $section->id,
                        'line_number' => $lineIdx + 1,
                        'content'     => $lineData['content'],
                    ]);

                    foreach ($lineData['chords'] as $cp) {
                        $chord = $chordMap->get($cp['chord']);

                        if (! $chord) {
                            $this->command?->warn(
                                "  ⚠ Chord '{$cp['chord']}' tidak ada di DB — pastikan ChordSeeder sudah dijalankan."
                            );
                            continue;
                        }

                        ChordPlacement::create([
                            'lyric_line_id' => $lyricLine->id,
                            'chord_id'      => $chord->id,
                            'position'      => $cp['position'],
                            'start_beat'    => $cp['beat'],
                        ]);
                    }
                }
            }

            $this->command?->info("  ✔ '{$song->title}' oleh {$artist->name} berhasil di-seed.");
        }
    }
}

