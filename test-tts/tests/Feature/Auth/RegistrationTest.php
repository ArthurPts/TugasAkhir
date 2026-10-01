<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('me.songs', absolute: false));

    $user = \App\Models\User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->username)->toBe('testuser');
    expect($user->role)->toBe('user');
});

test('registration forces role to user even if admin role is provided', function () {
    $response = $this->post('/register', [
        'username' => 'hacker',
        'email' => 'hacker@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ]);

    $this->assertAuthenticated();
    $user = \App\Models\User::where('email', 'hacker@example.com')->first();
    expect($user->role)->toBe('user');
});

