# ChordCue — Tahap 1-4 (Fondasi Teknis, Versi Update)

> **File ini menggantikan** `AUDIT_TAHAP_1-4_ChordCue.md` dan
> `KONSOLIDASI_TAHAP_1-4_ChordSheet_Player.md` yang sebelumnya terpisah. Keduanya digabung di sini
> dan sudah disesuaikan dengan **skema final** (lihat §0) — kalau kamu masih pegang 2 file lama
> itu, boleh dihapus/diabaikan.
>
> **Lanjutan** (Fase 5-6: Beat Editor, upload audio referensi, katalog, auth) ada di file terpisah
> `FASE_5-6_ChordCue_Lanjutan.md`.

Prinsip **"Function First, Form Later"** tetap berlaku di seluruh tahap ini — tampilan Blade masih
polos, fokus 100% ke logika berjalan presisi.

---

## 0. Skema Database yang Dipakai di Tahap Ini

Ini kolom-kolom yang relevan untuk Tahap 1-4 (dari migration final kamu). **Tidak ada migration
baru yang perlu dibuat** untuk menyelesaikan tahap ini — semua tabel yang dibutuhkan sudah ada.

| Tabel | Kolom Relevan | Catatan |
|---|---|---|
| `chords` | `id`, `name`, `pronunciation`, `file_path` | `file_path` **langsung** menyimpan path audio TTS chord ini (1 voice tetap, bukan cache per voice) |
| `chord_placements` | `id`, `lyric_line_id`, `chord_id`, `position`, `start_beat` | `position` = index huruf di lirik (dipakai render sederhana), `start_beat` = posisi beat **global** dari awal lagu (dipakai langsung sebagai `beat` di scheduler) |
| `songs` | `id`, `user_id`, `artist_id`, `title`, `bpm`, `time_signature_numerator`, `time_signature_denominator`, `visibility` | kolom `file_path` di tabel ini (backing track) **belum dipakai** di Tahap 1-4, itu scope Fase 5-6 |
| `song_sections`, `lyric_lines` | seperti migration | tidak berubah |

> **Penting:** kalau sebelumnya kamu sempat membuat tabel `chord_audio_caches` mengikuti dokumen
> lama, tabel itu **tidak dipakai lagi**. Buat migration baru untuk drop kalau sudah terlanjur ada:
> ```bash
> php artisan make:migration drop_chord_audio_caches_table
> ```
> ```php
> public function up(): void { Schema::dropIfExists('chord_audio_caches'); }
> public function down(): void { /* sengaja kosong, tidak perlu dibuat ulang */ }
> ```

### Konfigurasi voice TTS tetap (satu untuk semua chord)

**`.env`**
```env
PYTHON_BIN="C:/Users/hansc/AppData/Local/Programs/Python/Python313/python.exe"
TTS_DEFAULT_VOICE=en-US-AriaNeural
```

**`config/services.php`**
```php
'python' => [
    'bin' => env('PYTHON_BIN', 'python'),
],
'tts' => [
    'default_voice' => env('TTS_DEFAULT_VOICE', 'en-US-AriaNeural'),
],
```

---

## Tahap 1: Engine Audio Core (Metronom + Scheduler)

**Tidak ada perubahan dari versi sebelumnya** — bagian ini murni JavaScript, tidak menyentuh
skema database sama sekali, jadi tetap valid apa adanya.

### Goal
1. Metronom bunyi stabil di BPM tertentu, tanpa drift walau dibiarkan jalan lama.
2. Audio buffer bisa dijadwalkan main di detik/beat tertentu dengan presisi tinggi (bukan
   perkiraan `setTimeout`).

### Kode — `public/js/audio-engine.js`

```javascript
class AudioEngine {
    constructor() {
        this.audioContext = null;
        this.isPlaying = false;

        this.lookahead = 25.0;        // ms, seberapa sering scheduler loop ngecek
        this.scheduleAheadTime = 0.1; // detik, seberapa jauh ke depan kita schedule

        this.bpm = 120;
        this.currentBeat = 0;
        this.nextNoteTime = 0.0;
        this.timerID = null;

        this.buffers = {};          // cache decoded AudioBuffer, key: nama/teks chord
        this.scheduledEvents = [];  // log presisi, dipakai untuk highlight & debug
    }

    async init() {
        this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        await this.loadBuffer('click', '/audio/click.wav');
    }

    async loadBuffer(key, url) {
        const res = await fetch(url);
        const arrayBuffer = await res.arrayBuffer();
        this.buffers[key] = await this.audioContext.decodeAudioData(arrayBuffer);
    }

    secondsPerBeat() {
        return 60.0 / this.bpm;
    }

    scheduleSound(bufferKey, time) {
        const source = this.audioContext.createBufferSource();
        source.buffer = this.buffers[bufferKey];
        source.connect(this.audioContext.destination);
        source.start(time);

        this.scheduledEvents.push({
            beat: this.currentBeat,
            scheduledAt: time,
        });
    }

    scheduleNextBeat() {
        this.scheduleSound('click', this.nextNoteTime);
        this.nextNoteTime += this.secondsPerBeat();
        this.currentBeat++;
    }

    scheduler() {
        while (this.nextNoteTime < this.audioContext.currentTime + this.scheduleAheadTime) {
            this.scheduleNextBeat();
        }
        this.timerID = setTimeout(() => this.scheduler(), this.lookahead);
    }

    start(bpm) {
        if (this.isPlaying) return;
        this.bpm = bpm;
        this.currentBeat = 0;
        this.scheduledEvents = [];
        this.isPlaying = true;

        this.audioContext.resume();
        this.nextNoteTime = this.audioContext.currentTime + 0.1;
        this.scheduler();
    }

    stop() {
        this.isPlaying = false;
        clearTimeout(this.timerID);
    }
}

window.audioEngine = new AudioEngine();
```

### Testing
- Start metronom BPM 120 selama ±2 menit, Stop, `console.table(window.audioEngine.scheduledEvents)`
  — selisih antar beat harus konsisten `0.5s` (variasi < 1ms).
- Reload halaman, JANGAN klik apapun, buka console — pastikan tidak ada error autoplay policy.
  Baru klik Start.

---

## Tahap 2: Pipeline Generator Audio TTS (Laravel Queue)

**Berubah dari versi sebelumnya**: tidak ada lagi tabel cache terpisah. Job langsung membaca &
menulis ke kolom `chords.file_path`.

### Goal
1. Dispatch job untuk 1 chord → audio ter-generate → `chords.file_path` chord itu terisi.
2. Kalau `file_path` sudah terisi, job **tidak** generate ulang (cache-nya ya kolom itu sendiri).
3. Job gagal tercatat di `failed_jobs`, bukan hilang diam-diam.

### Setup Queue (kalau belum)
```env
QUEUE_CONNECTION=database
```
```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
php artisan storage:link
```

### Job — `app/Jobs/GenerateChordAudioJob.php`

```php
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

    public function __construct(
        public int $chordId,
    ) {}

    public function handle(): void
    {
        $chord = Chord::find($this->chordId);

        if (! $chord) {
            Log::warning("GenerateChordAudioJob: chord id {$this->chordId} tidak ditemukan.");
            return;
        }

        // Cache hit: kolom file_path sudah terisi, tidak perlu generate ulang.
        if (! empty($chord->file_path) && Storage::disk('public')->exists($chord->file_path)) {
            Log::info("Audio chord sudah ada, skip: {$chord->name}");
            return;
        }

        $voice = config('services.tts.default_voice');
        $filename = 'chord-' . $chord->id . '-' . str($chord->name)->slug() . '.mp3';
        $relativePath = 'chord-audio/' . $filename;
        $absolutePath = Storage::disk('public')->path($relativePath);

        Storage::disk('public')->makeDirectory('chord-audio');

        $process = new Process(
            [
                config('services.python.bin'),
                base_path('scripts/edge_tts_stream.py'),
                $chord->pronunciation,
                $voice,
            ],
            base_path(),
            $this->pythonEnv(),
        );

        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error("TTS gagal untuk chord '{$chord->name}': " . $process->getErrorOutput());
            throw new \RuntimeException('TTS generation failed: ' . $process->getErrorOutput());
        }

        file_put_contents($absolutePath, $process->getOutput());

        $chord->update(['file_path' => $relativePath]);

        Log::info("Audio chord berhasil dibuat: {$chord->name} -> {$relativePath}");
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
```

> Script Python `scripts/edge_tts_stream.py` **tidak berubah** dari sebelumnya — tetap menulis
> audio ke `stdout`, Laravel yang menyimpannya ke file.

### Model — `app/Models/Chord.php` (pastikan `file_path` fillable)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Chord extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name', 'pronunciation', 'file_path'];

    public function chordPlacements(): HasMany
    {
        return $this->hasMany(ChordPlacement::class);
    }

    public function audioUrl(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    public function hasAudio(): bool
    {
        return ! empty($this->file_path);
    }
}
```

### Testing
```bash
php artisan queue:work --verbose
```
```bash
php artisan tinker
>>> $chord = App\Models\Chord::where('name', 'C')->first();
>>> Illuminate\Support\Facades\Bus::dispatch(new App\Jobs\GenerateChordAudioJob($chord->id));
```
- Cek log `queue:work` — sukses tanpa error.
- `$chord->refresh(); $chord->file_path;` — harus terisi.
- Dispatch job yang sama lagi (chord id sama) — log harus bilang "sudah ada, skip", **bukan**
  generate ulang.
- Test gagal sengaja: dispatch dengan `chordId` yang tidak ada di database — job harus selesai
  tanpa exception aneh (return awal karena `$chord` null), cek log warning muncul.

---

## Tahap 3 & 4 (Digabung): Database, API, dan Halaman Player

Tahap 3 (gabung engine+TTS) dan Tahap 4 (DB & API) digabung di sini karena keduanya saling
bergantung erat, dan hasil akhirnya memang satu halaman player yang utuh — jadi dokumen versi
lama yang memisahnya jadi 2 tahap + 1 dokumen konsolidasi terpisah disederhanakan jadi satu alur
di sini.

### Goal
1. Endpoint `timeline()` mengembalikan seluruh chord placement satu lagu beserta status/URL audio,
   langsung dari `chords.file_path` (tanpa query ke tabel cache manapun).
2. Transpose bekerja **read-only** terhadap database (prinsip yang sudah dikoreksi sebelumnya —
   tidak pernah `UPDATE` ke `chord_placements`).
3. Satu halaman Blade menampilkan chordsheet (chord di atas kata sesuai `position`) dan bisa
   diputar dengan highlight chord aktif tersinkron ke scheduler.

### `app/Support/ChordTransposer.php`

```php
<?php

namespace App\Support;

class ChordTransposer
{
    protected static array $chromatic = [
        'C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B',
    ];

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
```

### `app/Http/Controllers/Api/SongController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateChordAudioJob;
use App\Models\Song;
use App\Support\ChordTransposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SongController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Song::with('artist')->latest()->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'                       => ['required', 'string', 'max:75'],
            'artist_id'                   => ['required', 'exists:artists,id'],
            'default_key'                 => ['nullable', 'string', 'max:45'],
            'bpm'                         => ['required', 'integer', 'min:20', 'max:300'],
            'time_signature_numerator'    => ['nullable', 'integer'],
            'time_signature_denominator'  => ['nullable', 'integer'],
            'visibility'                  => ['required', 'in:public,private'],
        ]);

        $validated['user_id'] = $request->user()?->id ?? \App\Models\User::first()->id;

        $song = Song::create($validated);

        return response()->json($song, 201);
    }

    public function show(Song $song): JsonResponse
    {
        return response()->json(
            $song->load('artist', 'sections.lyricLines.chordPlacements.chord')
        );
    }

    public function destroy(Song $song): JsonResponse
    {
        $song->delete();
        return response()->json(null, 204);
    }

    /**
     * Timeline lengkap untuk player: section -> line -> chord placement,
     * plus status/URL audio langsung dari chords.file_path.
     */
    public function timeline(Song $song): JsonResponse
    {
        $song->load('sections.lyricLines.chordPlacements.chord');

        $markers = [];
        foreach ($song->sections as $section) {
            foreach ($section->lyricLines as $line) {
                foreach ($line->chordPlacements as $placement) {
                    $chord = $placement->chord;

                    $markers[] = [
                        'chord_placement_id' => $placement->id,
                        'section' => $section->name,
                        'line_id' => $line->id,
                        'line_number' => $line->line_number,
                        'line_content' => $line->content,
                        'position' => $placement->position,
                        'beat' => $placement->start_beat,
                        'chord_name' => $chord->name,
                        'chord_text' => $chord->pronunciation,
                        'audio_url' => $chord->audioUrl(),
                        'audio_ready' => $chord->hasAudio(),
                    ];
                }
            }
        }

        return response()->json([
            'song' => [
                'id' => $song->id,
                'title' => $song->title,
                'bpm' => $song->bpm,
                'time_signature_numerator' => $song->time_signature_numerator ?? 4,
            ],
            'markers' => $markers,
            'all_audio_ready' => collect($markers)->every(fn ($m) => $m['audio_ready']),
        ]);
    }

    /**
     * Transpose seluruh lagu — READ ONLY terhadap database.
     * Tidak pernah UPDATE chord_placements/chords milik lagu ini.
     */
    public function transpose(Request $request, Song $song): JsonResponse
    {
        $validated = $request->validate(['steps' => ['required', 'integer']]);
        $steps = $validated['steps'];

        $song->load('sections.lyricLines.chordPlacements.chord');

        $markers = [];
        foreach ($song->sections as $section) {
            foreach ($section->lyricLines as $line) {
                foreach ($line->chordPlacements as $placement) {
                    $originalChord = $placement->chord;
                    $newName = $steps === 0
                        ? $originalChord->name
                        : ChordTransposer::transpose($originalChord->name, $steps);

                    // Cari/buat entri chord baru di tabel referensi (bukan mengubah placement lagu)
                    $transposedChord = \App\Models\Chord::firstOrCreate(
                        ['name' => $newName],
                        ['pronunciation' => $newName],
                    );

                    if (! $transposedChord->hasAudio()) {
                        GenerateChordAudioJob::dispatch($transposedChord->id);
                    }

                    $markers[] = [
                        'chord_placement_id' => $placement->id,
                        'section' => $section->name,
                        'line_id' => $line->id,
                        'line_number' => $line->line_number,
                        'line_content' => $line->content,
                        'position' => $placement->position,
                        'beat' => $placement->start_beat,
                        'chord_name' => $transposedChord->name,
                        'chord_text' => $transposedChord->pronunciation,
                        'audio_url' => $transposedChord->audioUrl(),
                        'audio_ready' => $transposedChord->hasAudio(),
                    ];
                }
            }
        }

        return response()->json([
            'song' => [
                'id' => $song->id,
                'title' => $song->title,
                'bpm' => $song->bpm,
                'time_signature_numerator' => $song->time_signature_numerator ?? 4,
            ],
            'markers' => $markers,
            'all_audio_ready' => collect($markers)->every(fn ($m) => $m['audio_ready']),
        ]);
    }
}
```

### Routes — `routes/web.php`

```php
use App\Http\Controllers\Api\SongController;
use App\Http\Controllers\SongPlayerController;

Route::prefix('api')->group(function () {
    Route::get('/songs', [SongController::class, 'index']);
    Route::post('/songs', [SongController::class, 'store']);
    Route::get('/songs/{song}', [SongController::class, 'show']);
    Route::delete('/songs/{song}', [SongController::class, 'destroy']);
    Route::get('/songs/{song}/timeline', [SongController::class, 'timeline']);
    Route::post('/songs/{song}/transpose', [SongController::class, 'transpose']);
});

Route::get('/songs/{song}/player', [SongPlayerController::class, 'show'])->name('songs.player');
```

### `app/Http/Controllers/SongPlayerController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Song;
use Illuminate\View\View;

class SongPlayerController extends Controller
{
    public function show(Song $song): View
    {
        return view('songs.player', ['songId' => $song->id]);
    }
}
```

### `resources/views/songs/player.blade.php`

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Chord Sheet Player</title>
    <style>
        /* Styling fungsional minimum (bukan desain) — monospace wajib untuk
           menyejajarkan posisi chord dengan huruf lirik secara presisi. */
        .chordsheet-line { font-family: monospace; white-space: pre; margin: 0; }
        .chord.active { text-decoration: underline; }
        .lyric-row { margin-bottom: 12px; }
    </style>
</head>
<body>
    <h3>Chord Sheet Player</h3>

    <p>
        <button id="preload">1. Preload</button>
        <button id="play" disabled>2. Play</button>
        <button id="stop" disabled>Stop</button>
        &nbsp;|&nbsp;
        Transpose:
        <button id="transpose-down">-1</button>
        <span id="transpose-value">0</span>
        <button id="transpose-up">+1</button>
        <button id="transpose-reset">Reset</button>
    </p>

    <div id="status"></div>
    <div id="chordsheet"></div>

    <script>
        window.SONG_ID = {{ $songId }};
    </script>
    <script src="/js/audio-engine.js"></script>
    <script src="/js/chordsheet-renderer.js"></script>
</body>
</html>
```

### `public/js/chordsheet-renderer.js`

```javascript
const statusEl = document.getElementById('status');
const print = (msg) => { statusEl.textContent = msg; };

const chordsheetEl = document.getElementById('chordsheet');
const btnPreload = document.getElementById('preload');
const btnPlay = document.getElementById('play');
const btnStop = document.getElementById('stop');
const btnTransposeUp = document.getElementById('transpose-up');
const btnTransposeDown = document.getElementById('transpose-down');
const btnTransposeReset = document.getElementById('transpose-reset');
const transposeValueEl = document.getElementById('transpose-value');

let displayData = null;
let currentTransposeSteps = 0;
let highlightLoopHandle = null;

async function fetchTimeline() {
    const res = await fetch(`/api/songs/${window.SONG_ID}/timeline`);
    return res.json();
}

async function fetchTransposedTimeline(steps) {
    if (steps === 0) return fetchTimeline();
    const res = await fetch(`/api/songs/${window.SONG_ID}/transpose`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ steps }),
    });
    return res.json();
}

function renderChordsheet(data) {
    chordsheetEl.innerHTML = '';

    const lines = new Map();
    for (const m of data.markers) {
        if (!lines.has(m.line_id)) {
            lines.set(m.line_id, { content: m.line_content, placements: [] });
        }
        lines.get(m.line_id).placements.push(m);
    }

    for (const [lineId, line] of lines) {
        const chordRowText = buildChordRow(line.placements, line.content.length);

        const chordRow = document.createElement('div');
        chordRow.className = 'chordsheet-line';
        chordRow.innerHTML = renderChordRowHtml(chordRowText, line.placements);

        const lyricRow = document.createElement('div');
        lyricRow.className = 'chordsheet-line lyric-row';
        lyricRow.textContent = line.content;

        chordsheetEl.appendChild(chordRow);
        chordsheetEl.appendChild(lyricRow);
    }
}

function buildChordRow(placements, lineLength) {
    const maxPos = Math.max(lineLength, ...placements.map(p => p.position + p.chord_name.length));
    const row = new Array(maxPos).fill(' ');
    for (const p of placements) {
        p.chord_name.split('').forEach((c, i) => { row[p.position + i] = c; });
    }
    return row.join('');
}

function renderChordRowHtml(chordRowText, placements) {
    let html = '';
    let cursor = 0;
    const sorted = [...placements].sort((a, b) => a.position - b.position);

    for (const p of sorted) {
        html += escapeHtml(chordRowText.slice(cursor, p.position));
        html += `<span class="chord" id="marker-${p.chord_placement_id}">${escapeHtml(p.chord_name)}</span>`;
        cursor = p.position + p.chord_name.length;
    }
    html += escapeHtml(chordRowText.slice(cursor));
    return html;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

btnPreload.addEventListener('click', async () => {
    try {
        print('Memuat data lagu...');
        await window.audioEngine.init();

        displayData = await fetchTimeline();
        currentTransposeSteps = 0;
        transposeValueEl.textContent = '0';

        renderChordsheet(displayData);
        await preloadAudioFor(displayData);

        print(`Preload sukses. "${displayData.song.title}" siap diputar.`);
        btnPlay.disabled = false;
    } catch (e) {
        print('Preload gagal: ' + e.message);
    }
});

async function preloadAudioFor(data) {
    if (!data.all_audio_ready) {
        const missing = [...new Set(data.markers.filter(m => !m.audio_ready).map(m => m.chord_name))];
        throw new Error('Audio belum siap untuk: ' + missing.join(', ') + ' (tunggu queue:work selesai, ulangi Preload)');
    }
    for (const marker of data.markers) {
        await window.audioEngine.loadBuffer(marker.chord_text, marker.audio_url);
    }
}

btnPlay.addEventListener('click', () => {
    playTimeline(displayData);
    btnPlay.disabled = true;
    btnStop.disabled = false;
    startHighlightLoop();
});

btnStop.addEventListener('click', () => {
    window.audioEngine.stop();
    stopHighlightLoop();
    clearAllHighlights();
    btnPlay.disabled = false;
    btnStop.disabled = true;
    print('Stopped.');
});

/**
 * Jadwalkan metronom + suara chord dalam satu AudioContext yang sama.
 * Chord diucapkan selesai TEPAT sebelum beat pergantiannya.
 */
function playTimeline(data) {
    const engine = window.audioEngine;
    engine.bpm = data.song.bpm;
    engine.scheduledEvents = [];
    engine.audioContext.resume();

    const secondsPerBeat = 60 / data.song.bpm;
    const startTime = engine.audioContext.currentTime + 0.2;
    const totalBeats = Math.max(...data.markers.map(m => m.beat)) + 4;

    for (let beat = 0; beat < totalBeats; beat++) {
        engine.scheduleSound('click', startTime + beat * secondsPerBeat);
    }

    for (const marker of data.markers) {
        const beatTime = startTime + marker.beat * secondsPerBeat;
        const buffer = engine.buffers[marker.chord_text];
        const duration = buffer.duration;
        let chordStartTime = beatTime - duration;

        if (chordStartTime < engine.audioContext.currentTime) {
            console.warn(`Chord "${marker.chord_text}" durasinya lebih panjang dari jarak antar beat.`);
            chordStartTime = engine.audioContext.currentTime + 0.01;
        }

        const source = engine.audioContext.createBufferSource();
        source.buffer = buffer;
        source.connect(engine.audioContext.destination);
        source.start(chordStartTime);

        engine.scheduledEvents.push({
            marker_id: marker.chord_placement_id,
            chordStartTime,
            chordEndTime: chordStartTime + duration,
        });
    }

    print(`Playing "${data.song.title}" at ${data.song.bpm} BPM (transpose ${currentTransposeSteps >= 0 ? '+' : ''}${currentTransposeSteps})...`);
}

function startHighlightLoop() {
    function tick() {
        const now = window.audioEngine.audioContext.currentTime;
        for (const ev of window.audioEngine.scheduledEvents) {
            if (!ev.marker_id) continue;
            const el = document.getElementById(`marker-${ev.marker_id}`);
            if (!el) continue;
            el.classList.toggle('active', now >= ev.chordStartTime && now < ev.chordEndTime);
        }
        highlightLoopHandle = requestAnimationFrame(tick);
    }
    highlightLoopHandle = requestAnimationFrame(tick);
}

function stopHighlightLoop() {
    if (highlightLoopHandle) cancelAnimationFrame(highlightLoopHandle);
    highlightLoopHandle = null;
}

function clearAllHighlights() {
    document.querySelectorAll('.chord.active').forEach(el => el.classList.remove('active'));
}

async function applyTranspose(newSteps) {
    try {
        print('Menghitung transpose & menyiapkan audio...');
        btnPlay.disabled = true;

        currentTransposeSteps = newSteps;
        transposeValueEl.textContent = (newSteps >= 0 ? '+' : '') + newSteps;

        displayData = await fetchTransposedTimeline(newSteps);
        renderChordsheet(displayData);
        await preloadAudioFor(displayData);

        print(`Transpose ${newSteps >= 0 ? '+' : ''}${newSteps} siap. Data asli di database TIDAK berubah.`);
        btnPlay.disabled = false;
    } catch (e) {
        print('Transpose gagal: ' + e.message);
    }
}

btnTransposeUp.addEventListener('click', () => applyTranspose(currentTransposeSteps + 1));
btnTransposeDown.addEventListener('click', () => applyTranspose(currentTransposeSteps - 1));
btnTransposeReset.addEventListener('click', () => applyTranspose(0));
```

### Testing — End-to-End

1. `php artisan migrate:fresh --seed`, jalankan `php artisan queue:work` di terminal terpisah.
2. Buka `http://localhost:8000/songs/1/player`, klik **Preload**.
   - Kalau ada chord belum ter-generate, pesan error jelas muncul di `#status`. Setelah
     `queue:work` selesai, klik Preload lagi — sukses, chordsheet muncul (chord nempel di atas
     huruf sesuai `position`).
3. Klik **Play** — metronom + suara chord terdengar presisi, chord yang aktif ter-*underline*
   tepat saat diucapkan.
4. Klik **Transpose +1** beberapa kali lalu **-1** sampai balik ke 0 — chordsheet re-render nama
   chord baru tiap klik, audio baru otomatis di-generate kalau belum ada. **Reload halaman** (F5)
   tanpa transpose apapun — harus kembali ke chord asli (buktikan cek `chord_placements.chord_id`
   di database tidak pernah berubah selama sesi transpose berlangsung).
5. Ulangi di `songs/2` (atau ID lain) — memastikan halaman generic, bukan hardcoded ke 1 lagu.

**Known limitation** (dicatat, bukan bug yang wajib diperbaiki di tahap ini): `AudioEngine.stop()`
menghentikan scheduler metronom, tapi source yang sudah terlanjur dijadwalkan tetap bunyi sampai
selesai. Perbaikan ini bisa masuk backlog Fase 5-6 kalau dibutuhkan.

---

## Yang TIDAK Termasuk di Tahap Ini (lihat file lanjutan)

- Membuat section/baris lirik lewat UI (di tahap ini datanya dari seeder).
- Mode Beat Editor (drag chord ke grid bar/beat).
- Upload audio referensi/backing track (`songs.file_path` belum dipakai).
- Katalog pencarian publik, auth, kuota.

Semua itu ada di `FASE_5-6_ChordCue_Lanjutan.md`.
