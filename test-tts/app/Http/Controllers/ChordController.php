<?php

namespace App\Http\Controllers;

use App\Models\Chord;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $chords = Chord::latest('id')->paginate(20);

        return view('chords.index', compact('chords'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('chords.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:45', 'unique:chords,name'],
            'root' => ['required', 'string', 'max:45'],
            'type' => ['required', 'string', 'max:45', 'exists:chord_type_pronunciations,type'],
            'bass' => ['nullable', 'string', 'max:45'],
        ]);

        Chord::create($validated);

        return redirect()->route('chords.index')->with('success', 'Chord berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Chord $chord): View
    {
        return view('chords.show', compact('chord'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Chord $chord): View
    {
        return view('chords.edit', compact('chord'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Chord $chord): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:45', 'unique:chords,name,' . $chord->id],
            'root' => ['required', 'string', 'max:45'],
            'type' => ['required', 'string', 'max:45', 'exists:chord_type_pronunciations,type'],
            'bass' => ['nullable', 'string', 'max:45'],
        ]);

        $chord->update($validated);

        return redirect()->route('chords.index')->with('success', 'Chord berhasil diupdate.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Chord $chord): RedirectResponse
    {
        $chord->delete();

        return redirect()->route('chords.index')->with('success', 'Chord berhasil dihapus.');
    }
}