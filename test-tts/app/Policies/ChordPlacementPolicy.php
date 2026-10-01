<?php

namespace App\Policies;

use App\Models\ChordPlacement;
use App\Models\User;

class ChordPlacementPolicy
{
    /**
     * Determine whether the user can update the chord placement.
     */
    public function update(User $user, ChordPlacement $placement): bool
    {
        $song = $placement->lyricLine?->section?->song;
        return $user->role === 'admin' || ($song && $user->id === $song->user_id);
    }

    /**
     * Determine whether the user can delete the chord placement.
     */
    public function delete(User $user, ChordPlacement $placement): bool
    {
        $song = $placement->lyricLine?->section?->song;
        return $user->role === 'admin' || ($song && $user->id === $song->user_id);
    }
}
