<?php

namespace App\Exceptions;

use App\Models\ChordPlacement;
use Exception;

class PlacementCollisionException extends Exception
{
    public function __construct(
        public ChordPlacement $collidingPlacement,
        public ?int $targetBeat,
        string $message = "Chord placement collision at beat."
    ) {
        parent::__construct($message);
    }
}
