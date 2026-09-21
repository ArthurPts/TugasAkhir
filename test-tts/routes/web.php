<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChordController;
use App\Http\Controllers\TtsController;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use App\Jobs\GenerateChordAudioJob;
use App\Http\Controllers\ChordAudioController;



Route::get('/', function () {
    return view('welcome');
});

Route::post('/api/chord-audio/batch', [ChordAudioController::class, 'batch']);
Route::get('/song-test', fn() => view('song-test'));


Route::get('/audio-test', fn() => view('audio-test'));

Route::get('/test-dispatch/{text}', function (string $text) {
    GenerateChordAudioJob::dispatch($text, 'en-US-AriaNeural');
    return "Dispatched job for: {$text}. Cek queue:work di terminal.";
});


//! bagian yang ga terpakai, tapi bisa dipakai kalau mau bikin fitur chord audio generation di web

// Route::resource('chords', ChordController::class);


// Route::get('/tts-test', [TtsController::class, 'test'])->name('tts.test');

// Route::post('/tts-test/speak', [TtsController::class, 'speak'])->name('tts.speak');

// Route::get('/tts-test', function () {
//     return view('tts-test');
// });

// Route::post('/tts-test/speak', function (Request $request) {

//     $request->validate([
//         'text' => ['required', 'string', 'max:500'],
//         'voice' => ['nullable', 'string'],
//     ]);

//     $python = 'C:\\Users\\hansc\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';

//     $text = $request->input('text');
//     $voice = $request->input('voice', 'en-US-AriaNeural');

//     $env = array_merge(getenv(), [
//         'SystemRoot' => 'C:\\Windows',
//         'SYSTEMROOT' => 'C:\\Windows',
//         'WINDIR' => 'C:\\Windows',
//     ]);

//     $process = new \Symfony\Component\Process\Process(
//         [
//             $python,
//             base_path('scripts/edge_tts_stream.py'),
//             $text,
//             $voice,
//         ],
//         base_path(),
//         $env
//     );

//     $process->setTimeout(30);

//     $process->run();

//     if (!$process->isSuccessful()) {
//         return response()->json([
//             'error' => 'TTS process failed',
//             'message' => $process->getErrorOutput(),
//         ], 500);
//     }

//     return response(
//         $process->getOutput(),
//         200,
//         [
//             'Content-Type' => 'audio/mpeg',
//             'Content-Disposition' => 'inline',
//             'Cache-Control' => 'no-cache',
//         ]
//     );
// });





// Route::get('/python-test', function () {
//     $python = 'C:\\Users\\hansc\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';

//     $env = array_merge(getenv(), [
//         'SystemRoot' => 'C:\\Windows',
//         'SYSTEMROOT' => 'C:\\Windows',
//         'WINDIR' => 'C:\\Windows',
//         'ComSpec' => 'C:\\Windows\\System32\\cmd.exe',
//     ]);

//     $process = new \Symfony\Component\Process\Process(
//         [
//             $python,
//             '-c',
//             'import sys; import asyncio; print(sys.executable); print(sys.version); print("asyncio OK")',
//         ],
//         base_path(),
//         $env
//     );

//     $process->run();

//     return response()->json([
//         'successful' => $process->isSuccessful(),
//         'output' => $process->getOutput(),
//         'error' => $process->getErrorOutput(),
//     ]);
// });

// Route::get('/env-test', function () {
//     return response()->json([
//         'SystemRoot' => getenv('SystemRoot'),
//         'SYSTEMROOT' => getenv('SYSTEMROOT'),
//         'WINDIR' => getenv('WINDIR'),
//         'PATH' => getenv('PATH'),
//     ]);
// });

// Route::get('/edge-tts-test', function () {
//     $python = 'C:\\Users\\hansc\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';

//     $env = array_merge(getenv(), [
//         'SystemRoot' => 'C:\\Windows',
//         'SYSTEMROOT' => 'C:\\Windows',
//         'WINDIR' => 'C:\\Windows',
//     ]);

//     $process = new \Symfony\Component\Process\Process(
//         [
//             $python,
//             '-c',
//             'import edge_tts; print("edge-tts OK"); print(edge_tts.__file__)',
//         ],
//         base_path(),
//         $env
//     );

//     $process->run();

//     return response()->json([
//         'successful' => $process->isSuccessful(),
//         'output' => $process->getOutput(),
//         'error' => $process->getErrorOutput(),
//     ]);
// });


// Route::get('/edge-tts-speak-test', function () {
//     $python = 'C:\\Users\\hansc\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';

//     $env = array_merge(getenv(), [
//         'SystemRoot' => 'C:\\Windows',
//         'SYSTEMROOT' => 'C:\\Windows',
//         'WINDIR' => 'C:\\Windows',
//     ]);

//     $process = new \Symfony\Component\Process\Process(
//         [
//             $python,
//             '-c',
//             <<<'PY'
// import asyncio
// import edge_tts

// async def main():
//     communicate = edge_tts.Communicate(
//         "C major",
//         "en-US-AriaNeural"
//     )

//     total = 0

//     async for chunk in communicate.stream():
//         if chunk["type"] == "audio":
//             total += len(chunk["data"])

//     print(f"AUDIO_BYTES={total}")

// asyncio.run(main())
// PY
//         ],
//         base_path(),
//         $env
//     );

//     $process->setTimeout(30);
//     $process->run();

//     return response()->json([
//         'successful' => $process->isSuccessful(),
//         'output' => $process->getOutput(),
//         'error' => $process->getErrorOutput(),
//     ]);
// });
