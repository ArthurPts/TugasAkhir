<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChordAudioCache;
use Illuminate\Http\JsonResponse;

class ChordAudioController extends Controller
{
    /**
     * Ambil URL audio untuk sekumpulan teks chord sekaligus.
     * Hanya mengembalikan yang sudah ter-generate (cache hit).
     * Yang belum ada (cache miss) akan hilang dari response —
     * frontend wajib mengecek kelengkapannya sebelum Play (lihat testing).
     */
    public function batch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'texts'   => ['required', 'array'],
            'texts.*' => ['string'],
            'voice'   => ['nullable', 'string'],
        ]);

        $voice = $validated['voice'] ?? 'en-US-AriaNeural';

        $hashes = collect($validated['texts'])
            ->mapWithKeys(fn($text) => [ChordAudioCache::hashFor($text, $voice) => $text]);

        $caches = ChordAudioCache::whereIn('text_hash', $hashes->keys())->get();

        $result = $caches->map(fn($cache) => [
            'text' => $cache->text,
            'url'  => $cache->url(),
        ]);

        return response()->json($result->values());
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
