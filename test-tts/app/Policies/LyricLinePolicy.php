<?php

namespace App\Policies;

use App\Models\LyricLine;
use App\Models\User;

class LyricLinePolicy
{
    /**
     * Determine whether the user can update the lyric line.
     */
    public function update(User $user, LyricLine $line): bool
    {
        $song = $line->section?->song;
        return $user->role === 'admin' || ($song && $user->id === $song->user_id);
    }

    /**
     * Determine whether the user can delete the lyric line.
     */
    public function delete(User $user, LyricLine $line): bool
    {
        $song = $line->section?->song;
        return $user->role === 'admin' || ($song && $user->id === $song->user_id);
    }
}
