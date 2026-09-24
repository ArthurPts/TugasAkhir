<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Chord extends Model
{
    /** @use HasFactory<\Database\Factories\ChordFactory> */
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'pronunciation',
        'file_path',
    ];

    /**
     * URL audio speech chord di storage public.
     */
    public function getAudioUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    /**
     * Helper method URL audio speech.
     */
    public function url(): ?string
    {
        return $this->audio_url;
    }

    /**
     * The places this chord is used in a song.
     */
    public function chordPlacements(): HasMany
    {
        return $this->hasMany(ChordPlacement::class);
    }
}
