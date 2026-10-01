<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ChordWebController extends Controller
{
    /**
     * GET /chords - Halaman Pustaka Chord (Phase F / M5)
     */
    public function index(): View
    {
        return view('chords.index');
    }
}
