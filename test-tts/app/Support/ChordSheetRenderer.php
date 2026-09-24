<?php

namespace App\Support;

class ChordSheetRenderer
{
    /**
     * Build a monospace chord row from sorted placements.
     *
     * @param  array<int, array{position:int, chord_name:string}>  $placements
     */
    public static function buildChordRow(array $placements, int $lineLength): string
    {
        $rowLength = max($lineLength, self::maxRequiredLength($placements));
        $row = array_fill(0, $rowLength, ' ');

        foreach ($placements as $placement) {
            $position = $placement['position'];
            $chordName = $placement['chord_name'];

            foreach (str_split($chordName) as $offset => $character) {
                $row[$position + $offset] = $character;
            }
        }

        return implode('', $row);
    }

    /**
     * @param  array<int, array{position:int, chord_name:string}>  $placements
     */
    protected static function maxRequiredLength(array $placements): int
    {
        $max = 0;

        foreach ($placements as $placement) {
            $max = max($max, $placement['position'] + strlen($placement['chord_name']));
        }

        return $max;
    }
}