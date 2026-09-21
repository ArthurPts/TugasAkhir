<?php

namespace App\Jobs;

use App\Models\ChordAudioCache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class GenerateChordAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $text,
        public string $voice = 'en-US-AriaNeural',
    ) {}


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $hash = ChordAudioCache::hashFor($this->text, $this->voice);

        // Cache hit: skip generate ulang
        if (ChordAudioCache::where('text_hash', $hash)->exists()) {
            Log::info("Chord audio cache hit: {$this->text}");
            return;
        }

        $filename = $hash . '.mp3';
        $relativePath = 'chord-audio/' . $filename;
        $absolutePath = Storage::disk('public')->path($relativePath);

        // pastikan folder ada
        Storage::disk('public')->makeDirectory('chord-audio');

        $process = new Process(
            [
                config('services.python.bin'),
                base_path('scripts/edge_tts_stream.py'),
                $this->text,
                $this->voice,
            ],
            base_path(),
            $this->pythonEnv(),
        );

        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error("TTS generation failed for '{$this->text}': " . $process->getErrorOutput());
            throw new \RuntimeException('TTS generation failed: ' . $process->getErrorOutput());
        }

        file_put_contents($absolutePath, $process->getOutput());

        ChordAudioCache::create([
            'text_hash' => $hash,
            'text'      => $this->text,
            'voice'     => $this->voice,
            'file_path' => $relativePath,
        ]);

        Log::info("Chord audio generated: {$this->text} -> {$relativePath}");
    }

    private function pythonEnv(): array
    {
        return array_merge(getenv(), [
            'SystemRoot' => 'C:\\Windows',
            'SYSTEMROOT' => 'C:\\Windows',
            'WINDIR'     => 'C:\\Windows',
        ]);
    }
}
