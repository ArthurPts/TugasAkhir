<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Song Timeline Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    <h3>Tahap 3: Audio Engine + TTS Chord Test</h3>
    <p>Transpose di API hanya mengubah chord yang diputar pada sesi ini; refresh halaman atau load ulang lagu akan kembali ke chord asli yang tersimpan di database.</p>

    <p>
        BPM: <input type="number" id="bpm" value="90">
        <button id="preload">1. Preload Audio</button>
        <button id="play" disabled>2. Play Test Song</button>
    </p>

    <pre id="log"></pre>

    {{-- <script src="/js/audio-engine.js"></script>
    <script>
        const log = document.getElementById('log');
        const print = (msg) => log.textContent += msg + "\n";

        // Timeline sederhana: chord berganti tiap 4 beat, total 16 beat (4 bar)
        const timeline = [
            { beat: 5,  text: 'C major' },
            { beat: 7,  text: 'D major' },
            // { beat: 9,  text: 'A minor' },
            // { beat: 13, text: 'G major' },
        ];
        const totalBeats = 12;

        (async () => {
            await window.audioEngine.init();
            print('Engine ready.');
        })();

        document.getElementById('preload').addEventListener('click', async () => {
            try {
                const result = await window.audioEngine.preloadTimeline(timeline);
                print(`Preload sukses: ${result.loaded} chord audio dimuat.`);
                document.getElementById('play').disabled = false;
            } catch (e) {
                print('Preload gagal: ' + e.message);
            }
        });

        document.getElementById('play').addEventListener('click', () => {
            const bpm = parseInt(document.getElementById('bpm').value, 10);
            window.audioEngine.playSongTimeline(timeline, bpm, totalBeats);
            print(`Playing timeline at ${bpm} BPM...`);

            setTimeout(() => {
                console.table(window.audioEngine.scheduledEvents);
                print('Selesai. Cek console untuk tabel timing presisi (gapBeforeBeat idealnya mendekati 0).');
            }, (totalBeats * (60 / bpm) + 1) * 1000);
        });
    </script> --}}

    <script src="/js/audio-engine.js"></script>
    <script>
        const log = document.getElementById('log');
        const print = (msg) => log.textContent += msg + "\n";

        // GANTI: sebelumnya array hardcoded, sekarang fetch dari song asli (misal song id=1)
        const SONG_ID = 1;
        let timelineData = null;

        (async () => {
            await window.audioEngine.init();
            print('Engine ready.');
        })();

        document.getElementById('preload').addEventListener('click', async () => {
            try {
                const res = await fetch(`/api/songs/${SONG_ID}/timeline`);
                timelineData = await res.json();

                if (!timelineData.all_audio_ready) {
                    const missing = timelineData.markers.filter(m => !m.audio_ready);
                    throw new Error('Audio belum siap untuk: ' + missing.map(m => m.chord_text).join(', '));
                }

                // load semua buffer langsung dari audio_url yang dikasih API
                for (const marker of timelineData.markers) {
                    await window.audioEngine.loadChordBuffer(marker.chord_text, marker.audio_url);
                }

                print(`Preload sukses: ${timelineData.markers.length} chord marker dimuat.`);
                document.getElementById('play').disabled = false;
            } catch (e) {
                print('Preload gagal: ' + e.message);
            }
        });

        document.getElementById('play').addEventListener('click', () => {
            const timeline = timelineData.markers.map(m => ({
                beat: m.beat,
                text: m.chord_text
            }));
            const totalBeats = Math.max(...timeline.map(m => m.beat)) + 4; // buffer 4 beat ekstra di akhir

            window.audioEngine.playSongTimeline(timeline, timelineData.song.bpm, totalBeats);
            print(`Playing "${timelineData.song.title}" at ${timelineData.song.bpm} BPM...`);
        });
    </script>
</body>

</html>
