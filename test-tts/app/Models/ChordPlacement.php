<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChordPlacement extends Model
{
    /** @use HasFactory<\Database\Factories\ChordPlacementFactory> */
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
        'lyric_line_id',
        'chord_id',
        'position',
        'start_time',
        'start_beat',
    ];

    /**
     * The lyric line this chord is placed on.
     */
    public function lyricLine(): BelongsTo
    {
        return $this->belongsTo(LyricLine::class);
    }

    /**
     * The chord being placed.
     */
    public function chord(): BelongsTo
    {
        return $this->belongsTo(Chord::class);
    }
}
