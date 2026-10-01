<?php

use App\Models\Artist;
use App\Models\Chord;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use App\Services\ChordPlacementCoordinateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    $artist = Artist::factory()->create();

    $this->song = Song::create([
        'user_id' => $user->id,
        'artist_id' => $artist->id,
        'title' => 'Test Song',
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'bpm' => 120,
        'visibility' => 'public',
    ]);

    $this->section = SongSection::create([
        'song_id' => $this->song->id,
        'name' => 'Verse 1',
        'sequence' => 1,
    ]);

    $this->line1 = LyricLine::create([
        'section_id' => $this->section->id,
        'line_number' => 1,
        'content' => 'Hello darkness my old friend', // 28 chars
    ]);

    $this->line2 = LyricLine::create([
        'section_id' => $this->section->id,
        'line_number' => 2,
        'content' => 'I have come to talk with you again',
    ]);

    $this->chordC = Chord::firstOrCreate(['name' => 'C'], ['pronunciation' => 'C Major']);
    $this->chordG = Chord::firstOrCreate(['name' => 'G'], ['pronunciation' => 'G Major']);
    $this->chordAm = Chord::firstOrCreate(['name' => 'Am'], ['pronunciation' => 'A Minor']);
});

it('stores visual-only placement without deriving a beat', function () {
    $response = $this->postJson('/api/chord-placements', [
        'lyric_line_id' => $this->line1->id,
        'chord_id' => $this->chordC->id,
        'position' => 0,
    ]);

    $response->assertCreated()
        ->assertJsonPath('position', 0)
        ->assertJsonPath('start_beat', null);
});

it('stores beat-only placement without deriving a visual position', function () {
    $response = $this->postJson('/api/chord-placements', [
        'lyric_line_id' => $this->line1->id,
        'chord_id' => $this->chordG->id,
        'start_beat' => 3,
    ]);

    $response->assertCreated()
        ->assertJsonPath('start_beat', 3)
        ->assertJsonPath('position', 0);

    expect($response->json('position'))->toBe(0);
});

it('rejects placement when colliding on same start_beat with 409 Conflict', function () {
    // Create first placement at beat 0
    $this->postJson('/api/chord-placements', [
        'lyric_line_id' => $this->line1->id,
        'chord_id' => $this->chordC->id,
        'start_beat' => 0,
    ])->assertCreated();

    // Try to place another chord at beat 0
    $collisionResponse = $this->postJson('/api/chord-placements', [
        'lyric_line_id' => $this->line1->id,
        'chord_id' => $this->chordG->id,
        'start_beat' => 0,
    ]);

    $collisionResponse->assertStatus(409)
        ->assertJsonPath('collision', true)
        ->assertJsonPath('colliding_placement.chord_id', $this->chordC->id)
        ->assertJsonPath('colliding_placement.start_beat', 0);
});

it('detects collision across different lines within the same song', function () {
    // Placement on line 1 at beat 4
    $this->postJson('/api/chord-placements', [
        'lyric_line_id' => $this->line1->id,
        'chord_id' => $this->chordC->id,
        'start_beat' => 4,
    ])->assertCreated();

    // Attempt to place chord on line 2 at beat 4
    $collisionResponse = $this->postJson('/api/chord-placements', [
        'lyric_line_id' => $this->line2->id,
        'chord_id' => $this->chordAm->id,
        'start_beat' => 4,
    ]);

    $collisionResponse->assertStatus(409)
        ->assertJsonPath('collision', true)
        ->assertJsonPath('colliding_placement.chord_name', 'C');
});

it('overrides collision when force is true', function () {
    $first = $this->postJson('/api/chord-placements', [
        'lyric_line_id' => $this->line1->id,
        'chord_id' => $this->chordC->id,
        'start_beat' => 2,
    ])->assertCreated();

    $firstId = $first->json('id');

    $forceResponse = $this->postJson('/api/chord-placements', [
        'lyric_line_id' => $this->line1->id,
        'chord_id' => $this->chordG->id,
        'start_beat' => 2,
        'force' => true,
    ]);

    $forceResponse->assertCreated()
        ->assertJsonPath('chord_id', $this->chordG->id)
        ->assertJsonPath('start_beat', 2);

    expect(ChordPlacement::find($firstId))->toBeNull();
});

it('does not collide with itself when updating without changing beat', function () {
    $placement = ChordPlacement::create([
        'lyric_line_id' => $this->line1->id,
        'chord_id' => $this->chordC->id,
        'position' => 5,
        'start_beat' => 2,
    ]);

    $updateResponse = $this->patchJson("/api/chord-placements/{$placement->id}", [
        'chord_id' => $this->chordAm->id,
    ]);

    $updateResponse->assertOk()
        ->assertJsonPath('chord_id', $this->chordAm->id)
        ->assertJsonPath('start_beat', 2);
});

it('keeps position independent when PATCHing start beat', function () {
    $placement = ChordPlacement::create([
        'lyric_line_id' => $this->line1->id,
        'chord_id' => $this->chordC->id,
        'position' => 0,
        'start_beat' => 0,
    ]);

    // Update by start_beat -> recalculate position
    $response = $this->patchJson("/api/chord-placements/{$placement->id}", [
        'start_beat' => 3,
    ]);

    $response->assertOk()
        ->assertJsonPath('start_beat', 3)
        ->assertJsonPath('position', 0);

    expect($response->json('position'))->toBe(0);
});

it('validates that either position or start_beat is supplied on store', function () {
    $response = $this->postJson('/api/chord-placements', [
        'lyric_line_id' => $this->line1->id,
        'chord_id' => $this->chordC->id,
    ]);

    $response->assertStatus(422);
});
