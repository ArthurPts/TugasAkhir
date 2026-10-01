<?php

namespace App\Policies;

use App\Models\Song;
use App\Models\User;

class SongPolicy
{
    /**
     * Determine whether the user can view the song.
     */
    public function view(?User $user, Song $song): bool
    {
        if ($song->visibility === 'public') {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->role === 'admin' || $user->id === $song->user_id;
    }

    /**
     * Determine whether the user can create songs (quota check).
     */
    public function create(User $user): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->songs()->count() < 10;
    }

    /**
     * Determine whether the user can update the song.
     */
    public function update(User $user, Song $song): bool
    {
        return $user->role === 'admin' || $user->id === $song->user_id;
    }

    /**
     * Determine whether the user can delete the song.
     */
    public function delete(User $user, Song $song): bool
    {
        return $user->role === 'admin' || $user->id === $song->user_id;
    }

    /**
     * Determine whether the user can manage backing track audio for the song.
     */
    public function manageAudio(User $user, Song $song): bool
    {
        return $user->role === 'admin' || $user->id === $song->user_id;
    }
}
