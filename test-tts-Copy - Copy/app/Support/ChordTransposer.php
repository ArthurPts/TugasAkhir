<?php

namespace App\Support;

class ChordTransposer
{
    /** @var list<string> */
    protected static array $chromatic = [
        'C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B',
    ];

    /**
     * Transpose nama chord sebanyak $steps semitone.
     * Contoh: transpose('C', 1) -> 'C#', transpose('Bm', 1) -> 'Cm'
     */
    public static function transpose(string $chordName, int $steps): string
    {
        if (! preg_match('/^([A-G]#?)(.*)$/', $chordName, $m)) {
            throw new \InvalidArgumentException("Format chord tidak dikenali: {$chordName}");
        }

        [, $root, $suffix] = $m;

        $index = array_search($root, self::$chromatic, true);
        if ($index === false) {
            throw new \InvalidArgumentException("Root chord tidak dikenali: {$root}");
        }

        $newIndex = (($index + $steps) % 12 + 12) % 12;

        return self::$chromatic[$newIndex] . $suffix;
    }
}