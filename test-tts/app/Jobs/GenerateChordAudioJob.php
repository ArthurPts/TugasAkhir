<?php

namespace App\Jobs;

use App\Models\Chord;
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
        public Chord $chord,
        public string $voice = 'en-US-AriaNeural',
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Jika file audio sudah ada di storage, skip generate ulang
        if ($this->chord->file_path && Storage::disk('public')->exists($this->chord->file_path)) {
            Log::info("Chord audio hit (sudah ada): {$this->chord->name}");
            return;
        }

        $text = $this->chord->pronunciation ?: $this->chord->name;
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($this->chord->name));
        $filename = "chord_{$this->chord->id}_{$safeName}.mp3";
        $relativePath = 'chord-audio/' . $filename;
        $absolutePath = Storage::disk('public')->path($relativePath);

        // Pastikan folder direktori tersedia
        Storage::disk('public')->makeDirectory('chord-audio');

        $process = new Process(
            [
                config('services.python.bin'),
                base_path('scripts/edge_tts_stream.py'),
                $text,
                $this->voice,
            ],
            base_path(),
            $this->pythonEnv(),
        );

        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error("TTS generation failed for '{$this->chord->name}': " . $process->getErrorOutput());
            throw new \RuntimeException('TTS generation failed: ' . $process->getErrorOutput());
        }

        file_put_contents($absolutePath, $process->getOutput());

        // Update langsung file_path ke tabel chords
        $this->chord->update([
            'file_path' => $relativePath,
        ]);

        Log::info("Chord audio generated: {$this->chord->name} -> {$relativePath}");
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
