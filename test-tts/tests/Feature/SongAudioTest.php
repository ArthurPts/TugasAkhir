<?php

use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $artist = Artist::factory()->create();

    $this->song = Song::create([
        'user_id' => $user->id,
        'artist_id' => $artist->id,
        'title' => 'Backing Track Test Song',
        'time_signature_numerator' => 4,
        'time_signature_denominator' => 4,
        'bpm' => 120,
        'visibility' => 'public',
    ]);
});

it('uploads backing track audio and updates song file_path', function () {
    $file = UploadedFile::fake()->create('backing_track.mp3', 2048, 'audio/mpeg');

    $response = $this->postJson("/api/songs/{$this->song->id}/audio", [
        'audio' => $file,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'file_path', 'audio_url']);

    $storedPath = $response->json('file_path');
    expect($storedPath)->not->toBeEmpty();
    Storage::disk('public')->assertExists($storedPath);

    expect($this->song->fresh()->file_path)->toBe($storedPath);
});

it('replaces old backing track and deletes old file from storage', function () {
    $firstFile = UploadedFile::fake()->create('first.mp3', 1024, 'audio/mpeg');
    $firstRes = $this->postJson("/api/songs/{$this->song->id}/audio", ['audio' => $firstFile]);
    $oldPath = $firstRes->json('file_path');
    Storage::disk('public')->assertExists($oldPath);

    $secondFile = UploadedFile::fake()->create('second.mp3', 2048, 'audio/mpeg');
    $secondRes = $this->postJson("/api/songs/{$this->song->id}/audio", ['audio' => $secondFile]);
    $newPath = $secondRes->json('file_path');

    expect($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($newPath);
});

it('rejects invalid file types or oversized files for backing track', function () {
    $invalidFile = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');
    $this->postJson("/api/songs/{$this->song->id}/audio", [
        'audio' => $invalidFile,
    ])->assertStatus(422);

    // Exceeds 15MB (16000 KB)
    $tooBig = UploadedFile::fake()->create('huge.mp3', 16000, 'audio/mpeg');
    $this->postJson("/api/songs/{$this->song->id}/audio", [
        'audio' => $tooBig,
    ])->assertStatus(422);
});

it('deletes backing track from storage and sets file_path to null', function () {
    $file = UploadedFile::fake()->create('backing.wav', 1024, 'audio/wav');
    $res = $this->postJson("/api/songs/{$this->song->id}/audio", ['audio' => $file]);
    $path = $res->json('file_path');
    Storage::disk('public')->assertExists($path);

    $delResponse = $this->deleteJson("/api/songs/{$this->song->id}/audio");
    $delResponse->assertOk();

    Storage::disk('public')->assertMissing($path);
    expect($this->song->fresh()->file_path)->toBeNull();
});
