<?php

use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artist = Artist::create(['name' => 'Test Artist']);
});

it('registers a new user and issues a sanctum token', function () {
    $response = $this->postJson('/api/register', [
        'username' => 'budi_gitar',
        'email' => 'budi@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['user' => ['id', 'username', 'email', 'role'], 'token']);

    expect($response->json('user.role'))->toBe('user');
});

it('logs in user and accesses protected routes', function () {
    $user = User::factory()->create([
        'email' => 'siti@example.com',
        'password' => bcrypt('password123'),
    ]);

    $loginRes = $this->postJson('/api/login', [
        'login' => 'siti@example.com',
        'password' => 'password123',
    ])->assertOk();

    $token = $loginRes->json('token');
    expect($token)->not->toBeEmpty();

    // Access /api/me with token
    $meRes = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/me');
    $meRes->assertOk()->assertJsonPath('email', 'siti@example.com');

    // Logout
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/logout')->assertOk();

    // Token is deleted from database
    expect(\Laravel\Sanctum\PersonalAccessToken::count())->toBe(0);

    app('auth')->forgetGuards();

    // Token revoked -> 401
    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/me')->assertStatus(401);
});

it('returns quota information and enforces 10 active songs limit for regular users', function () {
    $user = User::factory()->create(['role' => 'user']);

    // Check initial quota
    $this->actingAs($user)->getJson('/api/me/quota')
        ->assertOk()
        ->assertJson([
            'role' => 'user',
            'active_songs' => 0,
            'max_songs' => 10,
            'remaining' => 10,
        ]);

    // Create 10 songs
    for ($i = 1; $i <= 10; $i++) {
        Song::create([
            'user_id' => $user->id,
            'artist_id' => $this->artist->id,
            'title' => "Song {$i}",
            'bpm' => 100,
            'visibility' => 'public',
        ]);
    }

    // Quota now exhausted
    $this->actingAs($user)->getJson('/api/me/quota')
        ->assertOk()
        ->assertJson([
            'active_songs' => 10,
            'remaining' => 0,
        ]);

    // 11th song creation is rejected with 403
    $this->actingAs($user)->postJson('/api/songs', [
        'artist_id' => $this->artist->id,
        'title' => '11th Song Exceeding Quota',
        'bpm' => 100,
        'visibility' => 'public',
    ])->assertStatus(403);

    // Deleting one song frees up slot
    $user->songs()->first()->delete();

    $this->actingAs($user)->getJson('/api/me/quota')
        ->assertOk()
        ->assertJson([
            'active_songs' => 9,
            'remaining' => 1,
        ]);

    // Now creation succeeds
    $this->actingAs($user)->postJson('/api/songs', [
        'artist_id' => $this->artist->id,
        'title' => 'Allowed 10th Song',
        'bpm' => 100,
        'visibility' => 'public',
    ])->assertCreated();
});

it('does not restrict admin by song quota', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    for ($i = 1; $i <= 10; $i++) {
        Song::create([
            'user_id' => $admin->id,
            'artist_id' => $this->artist->id,
            'title' => "Admin Song {$i}",
            'bpm' => 100,
            'visibility' => 'public',
        ]);
    }

    $this->actingAs($admin)->postJson('/api/songs', [
        'artist_id' => $this->artist->id,
        'title' => '11th Admin Song',
        'bpm' => 100,
        'visibility' => 'public',
    ])->assertCreated();
});
