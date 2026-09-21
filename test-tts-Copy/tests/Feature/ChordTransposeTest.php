<?php

use App\Jobs\GenerateChordAudioJob;
use App\Models\Artist;
use App\Models\Chord;
use App\Models\ChordAudioCache;
use App\Models\ChordPlacement;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use App\Support\ChordTransposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('transpose tidak mengubah chord_id yang tersimpan di database', function () {
    Queue::fake();

    $placement = createPlacementGraph('C', 'C Major');
    $originalChordId = $placement->chord_id;

    $response = $this->postJson("/api/chord-placements/{$placement->id}/transpose", [
        'steps' => 1,
        'voice' => 'en-US-AriaNeural',
    ]);

    $response->assertOk()
        ->assertJsonPath('chord_name', 'C#')
        ->assertJsonPath('chord_text', 'C#')
        ->assertJsonPath('audio_ready', false);

    expect(ChordPlacement::findOrFail($placement->id)->chord_id)->toBe($originalChordId);

    Queue::assertPushed(GenerateChordAudioJob::class, function (GenerateChordAudioJob $job) {
        return $job->text === 'C#' && $job->voice === 'en-US-AriaNeural';
    });
});

it('transpose round-trip mengembalikan nama chord semula', function () {
    $cases = [
        'C',
        'Bm',
        'Cmaj7',
        'Asus4',
    ];

    foreach ($cases as $chordName) {
        $up = ChordTransposer::transpose($chordName, 1);
        $back = ChordTransposer::transpose($up, -1);

        expect($back)->toBe($chordName);
    }
});

it('transpose satu lagu penuh mengembalikan seluruh marker ter-transpose tanpa mengubah db', function () {
    $song = Song::create([
        'user_id' => User::factory()->create()->id,
        'title' => 'Transpose Timeline Song',
        'artist_id' => Artist::factory()->create()->id,
        'default_key' => 'C',
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $lateSection = SongSection::create([
        'song_id' => $song->id,
        'name' => 'Chorus',
        'sequence' => 2,
    ]);

    $earlySection = SongSection::create([
        'song_id' => $song->id,
        'name' => 'Verse',
        'sequence' => 1,
    ]);

    $lateLine = LyricLine::create([
        'section_id' => $lateSection->id,
        'line_number' => 2,
        'content' => 'Late line',
    ]);

    $earlyLine = LyricLine::create([
        'section_id' => $earlySection->id,
        'line_number' => 1,
        'content' => 'Early line',
    ]);

    $firstChord = Chord::create([
        'name' => 'C',
        'pronunciation' => 'C Major',
    ]);

    $secondChord = Chord::create([
        'name' => 'Bm',
        'pronunciation' => 'B Minor',
    ]);

    $firstPlacement = ChordPlacement::create([
        'lyric_line_id' => $lateLine->id,
        'chord_id' => $firstChord->id,
        'position' => 3,
        'start_time' => 1.500,
        'start_beat' => 4,
    ]);

    $secondPlacement = ChordPlacement::create([
        'lyric_line_id' => $earlyLine->id,
        'chord_id' => $secondChord->id,
        'position' => 1,
        'start_time' => 0.500,
        'start_beat' => 1,
    ]);

    $transposedFirst = Chord::firstOrCreate([
        'name' => 'C#',
    ], [
        'pronunciation' => 'C#',
    ]);

    $transposedSecond = Chord::firstOrCreate([
        'name' => 'Cm',
    ], [
        'pronunciation' => 'Cm',
    ]);

    ChordAudioCache::create([
        'text_hash' => ChordAudioCache::hashFor($transposedFirst->pronunciation, 'en-US-AriaNeural'),
        'text' => $transposedFirst->pronunciation,
        'voice' => 'en-US-AriaNeural',
        'file_path' => 'chord-audio/c-sharp.mp3',
    ]);

    ChordAudioCache::create([
        'text_hash' => ChordAudioCache::hashFor($transposedSecond->pronunciation, 'en-US-AriaNeural'),
        'text' => $transposedSecond->pronunciation,
        'voice' => 'en-US-AriaNeural',
        'file_path' => 'chord-audio/c-minor.mp3',
    ]);

    $response = $this->postJson("/api/songs/{$song->id}/transpose", [
        'steps' => 1,
        'voice' => 'en-US-AriaNeural',
    ]);

    $response->assertOk()
        ->assertJsonPath('markers.0.section', 'Verse')
        ->assertJsonPath('markers.0.line_number', 1)
        ->assertJsonPath('markers.0.chord_name', 'Cm')
        ->assertJsonPath('markers.0.chord_text', 'Cm')
        ->assertJsonPath('markers.1.section', 'Chorus')
        ->assertJsonPath('markers.1.line_number', 2)
        ->assertJsonPath('markers.1.chord_name', 'C#')
        ->assertJsonPath('markers.1.chord_text', 'C#')
        ->assertJsonPath('all_audio_ready', true);

    expect(ChordPlacement::findOrFail($firstPlacement->id)->chord_id)->toBe($firstChord->id)
        ->and(ChordPlacement::findOrFail($secondPlacement->id)->chord_id)->toBe($secondChord->id);

    $timeline = $this->getJson("/api/songs/{$song->id}/timeline");

    $timeline->assertOk()
        ->assertJsonPath('markers.0.section', 'Verse')
        ->assertJsonPath('markers.0.chord_name', 'Bm')
        ->assertJsonPath('markers.0.chord_text', 'B Minor')
        ->assertJsonPath('markers.1.section', 'Chorus')
        ->assertJsonPath('markers.1.chord_name', 'C')
        ->assertJsonPath('markers.1.chord_text', 'C Major');
});

it('menyajikan halaman chord sheet player untuk lagu', function () {
    $song = Song::create([
        'user_id' => User::factory()->create()->id,
        'title' => 'Player Shell Song',
        'artist_id' => Artist::factory()->create()->id,
        'default_key' => 'C',
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $response = $this->get("/songs/{$song->id}/player");

    $response->assertOk()
        ->assertSee('Chord Sheet Player')
        ->assertSee((string) $song->id);
});

function createPlacementGraph(string $chordName, string $pronunciation): ChordPlacement
{
    $song = Song::create([
        'user_id' => User::factory()->create()->id,
        'title' => 'Chord Placement Song',
        'artist_id' => Artist::factory()->create()->id,
        'default_key' => 'C',
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $section = SongSection::create([
        'song_id' => $song->id,
        'name' => 'Verse',
        'sequence' => 1,
    ]);

    $line = LyricLine::create([
        'section_id' => $section->id,
        'line_number' => 1,
        'content' => 'Test line',
    ]);

    $chord = Chord::create([
        'name' => $chordName,
        'pronunciation' => $pronunciation,
    ]);

    return ChordPlacement::create([
        'lyric_line_id' => $line->id,
        'chord_id' => $chord->id,
        'position' => 0,
        'start_time' => 0.000,
        'start_beat' => 0,
    ]);
}