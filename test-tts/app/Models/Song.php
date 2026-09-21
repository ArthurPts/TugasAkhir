<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Song extends Model
{
    /** @use HasFactory<\Database\Factories\SongFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'artist_id',
        'default_key',
        'bpm',
        'time_signature_numerator',
        'time_signature_denominator',
        'visibility',
    ];

    /**
     * The user who uploaded this song.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The artist who performs this song.
     */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    /**
     * The sections (verse, chorus, etc.) that make up this song.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(SongSection::class);
    }

    /**
     * The categories this song is tagged with.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_song');
    }
}
