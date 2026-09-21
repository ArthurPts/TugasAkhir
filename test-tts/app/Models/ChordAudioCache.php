<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChordAudioCache extends Model
{
    protected $fillable = [
        'text_hash',
        'text',
        'voice',
        'file_path',
        'duration_ms',
    ];

    public static function hashFor(string $text, string $voice): string
    {
        return sha1(strtolower(trim($text)) . '|' . $voice);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}