<!DOCTYPE html>

<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Audio Engine Test</title>
</head>

<body>
    <h3>Tahap 1: Audio Engine Core Test</h3>

```
<p>
    BPM:
    <input type="number" id="bpm" value="120">

    <button id="start">
        Start Metronome
    </button>

    <button id="stop">
        Stop
    </button>
</p>

<p>
    <button id="test-chord1">
        Test: Schedule dummy chord di detik ke-3
    </button>

    <button id="test-chord">
        Test 2: Dummy Chord +5 Detik
    </button>
</p>

<pre id="log"></pre>


<script src="/js/audio-engine.js"></script>

<script>
    const engine = window.audioEngine;
    const log = document.getElementById('log');

    function print(msg) {
        log.textContent += msg + "\n";
    }


    // ==========================================
    // INIT AUDIO ENGINE
    // ==========================================

    (async () => {
        try {
            await engine.init();
            print('Engine ready.');
        } catch (error) {
            console.error(error);
            print('Gagal initialize Audio Engine.');
        }
    })();


    // ==========================================
    // TEST METRONOME
    // ==========================================

    document.getElementById('start').addEventListener('click', async () => {

        const bpm = parseInt(
            document.getElementById('bpm').value,
            10
        );

        await engine.audioContext.resume();

        engine.start(bpm);

        print(`Metronome started at ${bpm} BPM`);
    });


    // ==========================================
    // STOP METRONOME
    // ==========================================

    document.getElementById('stop').addEventListener('click', () => {

        engine.stop();

        // Dump log timing ke console
        console.table(engine.scheduledEvents);

        print('Stopped.');
        print('Cek Console untuk tabel scheduledEvents.');
    });


    // ==========================================
    // TEST 1 / BASIC CHORD SCHEDULING
    // +3 DETIK
    // ==========================================

    document.getElementById('test-chord1').addEventListener('click', async () => {

        await engine.audioContext.resume();

        const currentTime = engine.audioContext.currentTime;

        const targetTime = engine.testScheduleChordAt(3);

        print('');
        print('--- Test Chord +3 Detik ---');
        print(`Current AudioContext time : ${currentTime.toFixed(4)}s`);
        print(`Target AudioContext time  : ${targetTime.toFixed(4)}s`);
        print(`Selisih                   : ${(targetTime - currentTime).toFixed(4)}s`);
        print('Dummy chord akan berbunyi sekitar 3 detik kemudian.');
    });


    // ==========================================
    // TEST 2
    // ONE-SHOT AUDIO SCHEDULING
    // +5 DETIK
    // ==========================================

    document.getElementById('test-chord').addEventListener('click', async () => {

        // Pastikan AudioContext aktif
        await engine.audioContext.resume();

        const currentTime = engine.audioContext.currentTime;

        console.log('========== TEST 2 ==========');
        console.log('Current AudioContext time:', currentTime);

        // Schedule dummy chord 5 detik dari sekarang
        const targetTime = engine.testScheduleChordAt(5);

        console.log('Target AudioContext time:', targetTime);
        console.log(
            'Scheduled delay:',
            targetTime - currentTime,
            'seconds'
        );

        print('');
        print('--- Test 2: One-Shot Audio Scheduling ---');
        print(`Current time : ${currentTime.toFixed(4)}s`);
        print(`Target time  : ${targetTime.toFixed(4)}s`);
        print(`Delay        : ${(targetTime - currentTime).toFixed(4)}s`);
        print('Dummy chord akan berbunyi sekitar 5 detik kemudian.');
    });

</script>
```

</body>

</html>
