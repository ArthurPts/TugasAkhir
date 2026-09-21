class AudioEngine {
    constructor() {
        this.audioContext = null;
        this.isPlaying = false;

        // Scheduler config
        this.lookahead = 25.0;        // ms, seberapa sering scheduler loop ngecek
        this.scheduleAheadTime = 0.1; // detik, seberapa jauh ke depan kita schedule

        this.bpm = 120;
        this.currentBeat = 0;
        this.nextNoteTime = 0.0;      // waktu (AudioContext time) untuk beat berikutnya
        this.timerID = null;

        this.buffers = {}; // cache decoded AudioBuffer, key: nama file
        this.scheduledEvents = []; // log untuk keperluan testing/debug
    }

    async init() {
        this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        // preload buffer dummy (Tahap 1 belum ada dari server, pakai file statis dulu)
        await this.loadBuffer('click', '/audio/click-short.mp3');
        await this.loadBuffer('dummy-chord', '/audio/dummy-chord.mp3');
    }

    async loadBuffer(key, url) {
        const res = await fetch(url);
        const arrayBuffer = await res.arrayBuffer();
        this.buffers[key] = await this.audioContext.decodeAudioData(arrayBuffer);
    }

    secondsPerBeat() {
        return 60.0 / this.bpm;
    }

    // Jadwalkan satu "note" (bisa click metronom atau chord) di waktu tertentu
    scheduleSound(bufferKey, time) {
        const source = this.audioContext.createBufferSource();
        source.buffer = this.buffers[bufferKey];
        source.connect(this.audioContext.destination);
        source.start(time);

        // catat buat verifikasi presisi nanti
        this.scheduledEvents.push({
            beat: this.currentBeat,
            scheduledAt: time,
            scheduledFromNow: time - this.audioContext.currentTime,
        });
    }

    // Dipanggil tiap beat untuk nge-set jadwal berikutnya
    scheduleNextBeat() {
        this.scheduleSound('click', this.nextNoteTime);
        this.nextNoteTime += this.secondsPerBeat();
        this.currentBeat++;
    }

    // Loop utama: cek apakah ada beat yang perlu dijadwalkan dalam scheduleAheadTime ke depan
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

        // resume() wajib dipanggil dalam user gesture (klik tombol) untuk browser modern
        this.audioContext.resume();
        this.nextNoteTime = this.audioContext.currentTime + 0.1;
        this.scheduler();
    }

    stop() {
        this.isPlaying = false;
        clearTimeout(this.timerID);
    }

    // Test khusus Tahap 1: jadwalkan 1 dummy chord tepat di detik ke-N dari sekarang
    testScheduleChordAt(secondsFromNow) {
        const targetTime = this.audioContext.currentTime + secondsFromNow;
        this.scheduleSound('dummy-chord', targetTime);
        return targetTime;
    }
}

window.audioEngine = new AudioEngine();

// === TAMBAHAN Tahap 3 ===

AudioEngine.prototype.loadChordBuffer = async function (text, url) {
    const res = await fetch(url);
    const arrayBuffer = await res.arrayBuffer();
    this.buffers[text] = await this.audioContext.decodeAudioData(arrayBuffer);
};

/**
 * Preload semua chord unik yang dibutuhkan sebuah timeline.
 * timeline: [{ beat: 0, text: 'C major' }, { beat: 4, text: 'D major' }, ...]
 */
AudioEngine.prototype.preloadTimeline = async function (timeline, voice = 'en-US-AriaNeural') {
    const uniqueTexts = [...new Set(timeline.map(t => t.text))];

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const res = await fetch('/api/chord-audio/batch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ texts: uniqueTexts, voice }),
    });

    if (!res.ok) {
        const errText = await res.text();
        throw new Error(`Request gagal (${res.status}): ${errText.slice(0, 200)}`);
    }

    const found = await res.json();

    const foundTexts = found.map(f => f.text);
    const missing = uniqueTexts.filter(t => !foundTexts.includes(t));
    if (missing.length > 0) {
        throw new Error('Audio belum ter-generate untuk: ' + missing.join(', '));
    }

    for (const item of found) {
        await this.loadChordBuffer(item.text, item.url);
    }

    return { loaded: foundTexts.length };
};

/**
 * Jadwalkan seluruh lagu sekaligus: klik metronom tiap beat + chord speech
 * yang berakhir tepat sebelum beat pergantian chord.
 */
AudioEngine.prototype.playSongTimeline = function (timeline, bpm, totalBeats) {
    this.bpm = bpm;
    this.scheduledEvents = [];
    this.audioContext.resume();

    const secondsPerBeat = this.secondsPerBeat();
    const startTime = this.audioContext.currentTime + 0.2; // jeda kecil biar aman

    // 1. Jadwalkan klik tiap beat
    for (let beat = 0; beat < totalBeats; beat++) {
        const beatTime = startTime + beat * secondsPerBeat;
        this.scheduleSound('click', beatTime);
    }

    // 2. Jadwalkan chord speech, selesai TEPAT sebelum beatTime-nya
    for (const marker of timeline) {
        const beatTime = startTime + marker.beat * secondsPerBeat;
        const chordBuffer = this.buffers[marker.text];
        const duration = chordBuffer.duration;

        let chordStartTime = beatTime - duration;

        // Edge case: durasi ucapan lebih panjang dari jarak antar beat.
        // Belum di-fix sempurna di tahap ini — cuma di-log sebagai warning.
        if (chordStartTime < this.audioContext.currentTime) {
            console.warn(
                `[WARNING] Chord "${marker.text}" durasinya (${duration.toFixed(2)}s) `
                + `lebih panjang dari waktu tersedia sebelum beat ${marker.beat}. `
                + `Chord akan dimulai langsung (kemungkinan numpuk).`
            );
            chordStartTime = this.audioContext.currentTime + 0.01;
        }

        const source = this.audioContext.createBufferSource();
        source.buffer = chordBuffer;
        source.connect(this.audioContext.destination);
        source.start(chordStartTime);

        this.scheduledEvents.push({
            chord: marker.text,
            beat: marker.beat,
            chordStartTime,
            chordEndTime: chordStartTime + duration,
            beatTime,
            gapBeforeBeat: beatTime - (chordStartTime + duration), // idealnya ~0
        });
    }
};