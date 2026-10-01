<?php

namespace App\Policies;

use App\Models\SongSection;
use App\Models\User;

class SongSectionPolicy
{
    /**
     * Determine whether the user can update the section.
     */
    public function update(User $user, SongSection $section): bool
    {
        $song = $section->song;
        return $user->role === 'admin' || ($song && $user->id === $song->user_id);
    }

    /**
     * Determine whether the user can delete the section.
     */
    public function delete(User $user, SongSection $section): bool
    {
        $song = $section->song;
        return $user->role === 'admin' || ($song && $user->id === $song->user_id);
    }
}
