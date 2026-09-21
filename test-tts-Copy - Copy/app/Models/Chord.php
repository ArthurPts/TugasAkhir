<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    /**
     * The places this chord is used in a song.
     */
    public function chordPlacements(): HasMany
    {
        return $this->hasMany(ChordPlacement::class);
    }
}
