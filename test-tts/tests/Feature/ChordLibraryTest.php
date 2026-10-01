<?php

use App\Jobs\GenerateChordAudioJob;
use App\Models\Chord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('lists all chords and filters by search keyword', function () {
    Chord::firstOrCreate(['name' => 'C'], ['pronunciation' => 'C Major']);
    Chord::firstOrCreate(['name' => 'Cm'], ['pronunciation' => 'C Minor']);
    Chord::firstOrCreate(['name' => 'G'], ['pronunciation' => 'G Major']);

    $resAll = $this->getJson('/api/chords');
    $resAll->assertOk()->assertJsonStructure([
        '*' => ['id', 'name', 'pronunciation', 'file_path', 'audio_url', 'audio_ready'],
    ]);

    $resSearch = $this->getJson('/api/chords?search=Minor');
    $resSearch->assertOk();
    expect($resSearch->json())->toHaveCount(1);
    expect($resSearch->json('0.name'))->toBe('Cm');
});

it('creates a custom chord and dispatches audio generation with configured voice', function () {
    Queue::fake();

    $response = $this->postJson('/api/chords', [
        'name' => 'F#m7b5',
        'pronunciation' => 'F Sharp Minor Seven Flat Five',
    ]);

    $response->assertCreated()
        ->assertJsonPath('name', 'F#m7b5')
        ->assertJsonPath('pronunciation', 'F Sharp Minor Seven Flat Five')
        ->assertJsonPath('audio_ready', false);

    Queue::assertPushed(GenerateChordAudioJob::class, function (GenerateChordAudioJob $job) {
        return $job->chord->name === 'F#m7b5'
            && $job->voice === config('services.tts.default_voice', 'en-US-AriaNeural');
    });
});

it('rejects duplicate custom chord names', function () {
    Chord::firstOrCreate(['name' => 'Dsus4'], ['pronunciation' => 'D Suspended Fourth']);

    $response = $this->postJson('/api/chords', [
        'name' => 'Dsus4',
        'pronunciation' => 'D Suspended Fourth Duplicate',
    ]);

    $response->assertStatus(422);
});
