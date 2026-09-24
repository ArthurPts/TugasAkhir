<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LyricLine extends Model
{
    /** @use HasFactory<\Database\Factories\LyricLineFactory> */
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
        'section_id',
        'line_number',
        'content',
    ];

    /**
     * The section this lyric line belongs to.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(SongSection::class, 'section_id');
    }

    /**
     * The chords placed on this lyric line.
     */
    public function chordPlacements(): HasMany
    {
        return $this->hasMany(ChordPlacement::class);
    }
}
