<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SongSection extends Model
{
    /** @use HasFactory<\Database\Factories\SongSectionFactory> */
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
        'song_id',
        'name',
        'sequence',
    ];

    /**
     * The song this section belongs to.
     */
    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    /**
     * The lyric lines within this section.
     */
    public function lyricLines(): HasMany
    {
        return $this->hasMany(LyricLine::class, 'section_id');
    }
}
