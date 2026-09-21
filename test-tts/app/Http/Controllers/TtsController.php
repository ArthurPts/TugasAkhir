<?php

namespace App\Http\Controllers;

use App\Models\Chord;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class TtsController extends Controller
{
    public function test(): View
    {
        $chords = Chord::with('typePronunciation')->orderBy('name')->get();

        return view('tts.test', compact('chords'));
    }

    public function speak(Request $request): Response
    {
        $validated = $request->validate([
            'text'  => ['required', 'string', 'max:200'],
            'voice' => ['nullable', 'string', 'max:100'],
        ]);

        $process = new Process(
            [
                config('services.python.bin'),
                base_path('scripts/edge_tts_stream.py'),
                $validated['text'],
                $validated['voice'] ?? 'en-US-AriaNeural',
            ],
            base_path(),
            $this->pythonEnv(),
        );

        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            return response([
                'error'  => 'TTS generation failed',
                'stderr' => $process->getErrorOutput(),
            ], 500);
        }

        return response($process->getOutput(), 200, [
            'Content-Type'  => 'audio/mpeg',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Windows (khususnya kalau dijalankan via Apache/XAMPP sebagai service)
     * kadang gak mewarisi SystemRoot ke proses child, yang bikin asyncio
     * gagal load Winsock provider (WinError 10106). Ini fix-nya.
     */
    private function pythonEnv(): array
    {
        return array_merge(getenv(), [
            'SystemRoot' => 'C:\\Windows',
            'SYSTEMROOT' => 'C:\\Windows',
            'WINDIR'     => 'C:\\Windows',
        ]);
    }
}