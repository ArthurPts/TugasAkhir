<?php

use App\Models\Artist;
use App\Models\Category;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::factory()->create(['role' => 'user']);
    $this->otherUser = User::factory()->create(['role' => 'user']);
    $this->admin = User::factory()->create(['role' => 'admin']);

    $this->artistQueen = Artist::create(['name' => 'Queen']);
    $this->artistBeatles = Artist::create(['name' => 'The Beatles']);

    $this->catRock = Category::create(['name' => 'Rock', 'type' => 'genre']);
    $this->catPop = Category::create(['name' => 'Pop', 'type' => 'genre']);

    // Public song 1
    $this->publicSong1 = Song::create([
        'user_id' => $this->owner->id,
        'artist_id' => $this->artistQueen->id,
        'title' => 'Bohemian Rhapsody',
        'bpm' => 72,
        'visibility' => 'public',
    ]);
    $this->publicSong1->categories()->attach($this->catRock);

    // Public song 2
    $this->publicSong2 = Song::create([
        'user_id' => $this->otherUser->id,
        'artist_id' => $this->artistBeatles->id,
        'title' => 'Yesterday',
        'bpm' => 96,
        'visibility' => 'public',
    ]);
    $this->publicSong2->categories()->attach($this->catPop);

    // Private song
    $this->privateSong = Song::create([
        'user_id' => $this->owner->id,
        'artist_id' => $this->artistQueen->id,
        'title' => 'Secret Queen Song',
        'bpm' => 110,
        'visibility' => 'private',
    ]);
});

it('defaults guest catalog listing to only public songs', function () {
    $response = $this->getJson('/api/songs');
    $response->assertOk();

    $titles = collect($response->json('data'))->pluck('title')->all();
    expect($titles)->toContain('Bohemian Rhapsody', 'Yesterday')
        ->not->toContain('Secret Queen Song');
});

it('searches songs by title and artist name', function () {
    // Search by title
    $resTitle = $this->getJson('/api/songs?search=Bohemian');
    $resTitle->assertOk();
    expect($resTitle->json('data'))->toHaveCount(1);
    expect($resTitle->json('data.0.title'))->toBe('Bohemian Rhapsody');

    // Search by artist
    $resArtist = $this->getJson('/api/songs?search=Beatles');
    $resArtist->assertOk();
    expect($resArtist->json('data'))->toHaveCount(1);
    expect($resArtist->json('data.0.title'))->toBe('Yesterday');
});

it('filters songs by category and artist_id', function () {
    $resCat = $this->getJson("/api/songs?category={$this->catRock->name}");
    $resCat->assertOk();
    expect($resCat->json('data'))->toHaveCount(1);
    expect($resCat->json('data.0.title'))->toBe('Bohemian Rhapsody');

    $resArtist = $this->getJson("/api/songs?artist_id={$this->artistBeatles->id}");
    $resArtist->assertOk();
    expect($resArtist->json('data'))->toHaveCount(1);
    expect($resArtist->json('data.0.title'))->toBe('Yesterday');
});

it('allows owners to see their own private songs but hides from other users', function () {
    // Authenticated owner can see their private songs
    $resOwner = $this->actingAs($this->owner)->getJson('/api/songs');
    $titlesOwner = collect($resOwner->json('data'))->pluck('title')->all();
    expect($titlesOwner)->toContain('Secret Queen Song');

    // Other user cannot see owner\'s private song
    $resOther = $this->actingAs($this->otherUser)->getJson('/api/songs');
    $titlesOther = collect($resOther->json('data'))->pluck('title')->all();
    expect($titlesOther)->not->toContain('Secret Queen Song');
});

it('blocks guests from accessing private song details or timeline', function () {
    $this->getJson("/api/songs/{$this->privateSong->id}")->assertStatus(404);
    $this->getJson("/api/songs/{$this->privateSong->id}/timeline")->assertStatus(404);
});
