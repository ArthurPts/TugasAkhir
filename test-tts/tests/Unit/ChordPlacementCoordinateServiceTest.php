<?php

namespace Tests\Unit;

use App\Exceptions\PlacementCollisionException;
use App\Models\Artist;
use App\Models\Chord;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use App\Services\ChordPlacementCoordinateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ChordPlacementCoordinateServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChordPlacementCoordinateService $service;
    private Song $song;
    private LyricLine $line1;
    private LyricLine $line2;
    private Chord $chordC;
    private Chord $chordG;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChordPlacementCoordinateService();

        $user = User::factory()->create();
        $artist = Artist::factory()->create();

        $this->song = Song::create([
            'user_id' => $user->id,
            'artist_id' => $artist->id,
            'title' => 'Unit Test Song',
            'time_signature_numerator' => 4,
            'time_signature_denominator' => 4,
            'bpm' => 120,
        ]);

        $section = SongSection::create([
            'song_id' => $this->song->id,
            'name' => 'Verse 1',
            'sequence' => 1,
        ]);

        $this->line1 = LyricLine::create([
            'section_id' => $section->id,
            'line_number' => 1,
            'content' => '1234567890', // 10 chars, indices 0..9
        ]);

        $this->line2 = LyricLine::create([
            'section_id' => $section->id,
            'line_number' => 2,
            'content' => 'abcdefghij', // 10 chars
        ]);

        $this->chordC = Chord::firstOrCreate(['name' => 'C'], ['pronunciation' => 'C Major']);
        $this->chordG = Chord::firstOrCreate(['name' => 'G'], ['pronunciation' => 'G Major']);
    }

    public function test_throws_exception_on_invalid_coordinates(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->convertCoordinates($this->line1, null, null);
    }

    public function test_keeps_position_without_deriving_start_beat(): void
    {
        $coords0 = $this->service->convertCoordinates($this->line1, 0, null);
        $this->assertSame(0, $coords0['position']);
        $this->assertNull($coords0['start_beat']);

        $coords9 = $this->service->convertCoordinates($this->line1, 9, null);
        $this->assertSame(9, $coords9['position']);
        $this->assertNull($coords9['start_beat']);
    }

    public function test_keeps_start_beat_without_deriving_position(): void
    {
        $coords0 = $this->service->convertCoordinates($this->line1, null, 0);
        $this->assertSame(0, $coords0['position']);
        $this->assertSame(0, $coords0['start_beat']);

        $coords3 = $this->service->convertCoordinates($this->line1, null, 3);
        $this->assertSame(0, $coords3['position']);
        $this->assertSame(3, $coords3['start_beat']);
    }

    public function test_collision_detection_throws_exception(): void
    {
        $this->service->savePlacement($this->line1, $this->chordC->id, 0, 0);

        $this->expectException(PlacementCollisionException::class);
        $this->service->savePlacement($this->line1, $this->chordG->id, null, 0, false);
    }

    public function test_collision_across_lines_throws_exception(): void
    {
        $this->service->savePlacement($this->line1, $this->chordC->id, null, 4);

        $this->expectException(PlacementCollisionException::class);
        $this->service->savePlacement($this->line2, $this->chordG->id, null, 4, false);
    }

    public function test_force_overrides_colliding_placement(): void
    {
        $p1 = $this->service->savePlacement($this->line1, $this->chordC->id, null, 1);
        $p2 = $this->service->savePlacement($this->line1, $this->chordG->id, null, 1, true);

        $this->assertSame($this->chordG->id, $p2->chord_id);
        $this->assertSame(1, $p2->start_beat);
        $this->assertNull(ChordPlacement::find($p1->id));
    }

    public function test_visual_only_placement_can_skip_start_beat(): void
    {
        $placement = $this->service->savePlacement($this->line1, $this->chordC->id, 7, null);

        $this->assertSame(7, $placement->position);
        $this->assertNull($placement->start_beat);
    }

    public function test_updating_placement_does_not_collide_with_itself(): void
    {
        $p = $this->service->savePlacement($this->line1, $this->chordC->id, null, 2);

        $updated = $this->service->savePlacement(
            $this->line1,
            $this->chordG->id,
            $p->position,
            $p->start_beat,
            false,
            $p
        );

        $this->assertSame($p->id, $updated->id);
        $this->assertSame($this->chordG->id, $updated->chord_id);
        $this->assertSame(2, $updated->start_beat);
    }
}
