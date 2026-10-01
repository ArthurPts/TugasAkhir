<?php

use App\Models\Artist;
use App\Models\Chord;
use App\Models\LyricLine;
use App\Models\Song;
use App\Models\SongSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->artist = Artist::factory()->create();

    $this->song = Song::create([
        'user_id' => $this->user->id,
        'artist_id' => $this->artist->id,
        'title' => 'Editor Test Song',
        'bpm' => 120,
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'visibility' => 'public',
    ]);

    $this->section = SongSection::create([
        'song_id' => $this->song->id,
        'name' => 'Verse 1',
        'sequence' => 1,
    ]);

    $this->line = LyricLine::create([
        'section_id' => $this->section->id,
        'line_number' => 1,
        'content' => 'Test lyric line content',
    ]);

    Chord::firstOrCreate(['name' => 'C'], ['pronunciation' => 'C Major']);
});

it('redirects legacy editor routes to the simple editor', function () {
    $this->actingAs($this->user)
        ->get("/songs/{$this->song->id}/editor")
        ->assertRedirect("/songs/{$this->song->id}/editor/simple");

    $this->actingAs($this->user)
        ->get("/songs/{$this->song->id}/edit")
        ->assertRedirect("/songs/{$this->song->id}/editor/simple");
});

it('renders the simple chord editor page successfully for owner', function () {
    $response = $this->actingAs($this->user)->get("/songs/{$this->song->id}/editor/simple");

    $response->assertOk()
        ->assertSee('Mode Sederhana')
        ->assertSee('Pustaka Chord')
        ->assertSee('time-signature-numerator', false)
        ->assertSee('/js/chord-editor.js');
});

it('redirects guest accessing chord editor to login', function () {
    $response = $this->get("/songs/{$this->song->id}/editor");

    $response->assertRedirect('/login');
});
