<?php

use App\Models\Artist;
use App\Models\Chord;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    $artist = Artist::factory()->create();

    $this->song = Song::create([
        'user_id' => $user->id,
        'artist_id' => $artist->id,
        'title' => 'Structure Test Song',
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);
});

it('creates sections with automatic sequence assignment', function () {
    $res1 = $this->postJson("/api/songs/{$this->song->id}/sections", [
        'name' => 'Intro',
    ])->assertCreated();

    expect($res1->json('sequence'))->toBe(1);

    $res2 = $this->postJson("/api/songs/{$this->song->id}/sections", [
        'name' => 'Verse',
    ])->assertCreated();

    expect($res2->json('sequence'))->toBe(2);
});

it('updates and deletes a section cascading to lines and placements', function () {
    $section = SongSection::create([
        'song_id' => $this->song->id,
        'name' => 'Chorus',
        'sequence' => 1,
    ]);

    $line = LyricLine::create([
        'section_id' => $section->id,
        'line_number' => 1,
        'content' => 'La la la sing a song',
    ]);

    $chord = Chord::firstOrCreate(['name' => 'F'], ['pronunciation' => 'F Major']);
    $placement = ChordPlacement::create([
        'lyric_line_id' => $line->id,
        'chord_id' => $chord->id,
        'position' => 0,
        'start_beat' => 0,
    ]);

    // Update section name
    $this->patchJson("/api/sections/{$section->id}", [
        'name' => 'Final Chorus',
    ])->assertOk()->assertJsonPath('name', 'Final Chorus');

    // Delete section
    $this->deleteJson("/api/sections/{$section->id}")->assertNoContent();

    expect(SongSection::find($section->id))->toBeNull();
    expect(LyricLine::find($line->id))->toBeNull();
    expect(ChordPlacement::find($placement->id))->toBeNull();
});

it('creates, updates and deletes lyric lines cascading to placements', function () {
    $section = SongSection::create([
        'song_id' => $this->song->id,
        'name' => 'Bridge',
        'sequence' => 1,
    ]);

    $lineRes = $this->postJson("/api/sections/{$section->id}/lines", [
        'content' => 'Take me to the bridge',
    ])->assertCreated();

    $lineId = $lineRes->json('id');
    expect($lineRes->json('line_number'))->toBe(1);

    // Update line content
    $this->patchJson("/api/lines/{$lineId}", [
        'content' => 'Take me higher',
    ])->assertOk()->assertJsonPath('content', 'Take me higher');

    $chord = Chord::firstOrCreate(['name' => 'Dm'], ['pronunciation' => 'D Minor']);
    $placement = ChordPlacement::create([
        'lyric_line_id' => $lineId,
        'chord_id' => $chord->id,
        'position' => 2,
        'start_beat' => 1,
    ]);

    // Delete line
    $this->deleteJson("/api/lines/{$lineId}")->assertNoContent();

    expect(LyricLine::find($lineId))->toBeNull();
    expect(ChordPlacement::find($placement->id))->toBeNull();
});
