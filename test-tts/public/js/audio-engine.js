/**
 * AudioEngine
 * 
 * Mengatur sinkronisasi audio menggunakan Web Audio API:
 * 1. Audio lagu utama (backing track dari songs.file_path)
 * 2. Suara ketukan metronom (click)
 * 3. Suara pembacaan chord TTS (chord speech dari chords.file_path)
 */
class AudioEngine {
    constructor() {
        this.audioContext = null;
        this.isPlaying = false;

        this.bpm = 120;

        // Cache hasil decode AudioBuffer: { 'click': AudioBuffer, 'song': AudioBuffer, [chordText]: AudioBuffer }
        this.buffers = {};

        // Track node audio aktif/terjadwal agar bisa dihentikan saat Stop
        this.activeSources = [];
        this.scheduledEvents = [];
    }

    /**
     * Inisialisasi AudioContext & preload suara dasar (click metronom).
     */
    async init() {
        if (!this.audioContext) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }

        // Pastikan AudioContext aktif setelah user gesture
        if (this.audioContext.state === 'suspended') {
            await this.audioContext.resume();
        }

        // Muat suara click metronom bawaan jika belum ada
        if (!this.buffers['click']) {
            await this.loadBuffer('click', '/audio/click-short.mp3');
        }
    }

    /**
     * Helper umum untuk fetch audio dari URL dan decode ke AudioBuffer.
     */
    async loadBuffer(key, url) {
        if (!url) return;
        const res = await fetch(url);
        if (!res.ok) {
            throw new Error(`Gagal mengunduh audio "${key}" (${res.status}) dari ${url}`);
        }
        const arrayBuffer = await res.arrayBuffer();
        this.buffers[key] = await this.audioContext.decodeAudioData(arrayBuffer);
    }

    /**
     * Memuat audio chord speech.
     */
    async loadChordBuffer(text, url) {
        await this.loadBuffer(text, url);
    }

    /**
     * Memuat file audio lagu (backing track).
     */
    async loadSongBuffer(url) {
        await this.loadBuffer('song', url);
    }

    /**
     * Menghitung durasi 1 ketukan (beat) dalam detik.
     */
    secondsPerBeat() {
        return 60.0 / this.bpm;
    }

    /**
     * Jadwalkan satu bunyi metronom click pada waktu audioContext tertentu.
     */
    scheduleSound(bufferKey, time) {
        if (!this.buffers[bufferKey]) return;
        const source = this.audioContext.createBufferSource();
        source.buffer = this.buffers[bufferKey];
        source.connect(this.audioContext.destination);
        source.start(time);
        this.activeSources.push(source);
    }

    /**
     * Memainkan lagu lengkap secara terkoordinasi:
     * - Audio lagu (backing track) dimulai tepat pada waktu startTime
     * - Metronom klik tiap beat
     * - Suara chord speech dimulai beberapa saat sebelum beat agar selesai tepat saat chord berganti
     *
     * @param {Array} timeline Daftar marker chord: [{ beat: 0, text: 'C Major' }, ...]
     * @param {number} bpm Tempo lagu
     * @param {number} totalBeats Total ketukan yang akan dimainkan
     */
    playSongTimeline(timeline, bpm, totalBeats) {
        this.stop(); // Hentikan playback sebelumnya jika ada

        this.bpm = bpm;
        this.isPlaying = true;
        this.scheduledEvents = [];
        this.activeSources = [];

        if (this.audioContext.state === 'suspended') {
            this.audioContext.resume();
        }

        const secondsPerBeat = this.secondsPerBeat();
        // Berikan buffer jeda 0.2 detik (200ms) agar semua node audio dijadwalkan secara presisi & serentak
        const startTime = this.audioContext.currentTime + 0.2;

        // 1. MAINKAN AUDIO LAGU (Backing Track) jika tersedia
        if (this.buffers['song']) {
            const songSource = this.audioContext.createBufferSource();
            songSource.buffer = this.buffers['song'];
            songSource.connect(this.audioContext.destination);
            songSource.start(startTime);
            this.activeSources.push(songSource);
        }

        // 2. JADWALKAN KLIK METRONOM untuk setiap beat
        for (let beat = 0; beat < totalBeats; beat++) {
            const beatTime = startTime + beat * secondsPerBeat;
            this.scheduleSound('click', beatTime);
        }

        // 3. JADWALKAN SUARA CHORD (TTS Speech)
        // Ucapan disetting selesai tepat sebelum pergantian beat
        for (const marker of timeline) {
            const beatTime = startTime + marker.beat * secondsPerBeat;
            const chordBuffer = this.buffers[marker.text];
            if (!chordBuffer) continue;

            const duration = chordBuffer.duration;
            let chordStartTime = beatTime - duration;

            // Jika durasi audio chord lebih panjang dari waktu beat, mulai secepatnya
            if (chordStartTime < this.audioContext.currentTime) {
                chordStartTime = this.audioContext.currentTime + 0.01;
            }

            const source = this.audioContext.createBufferSource();
            source.buffer = chordBuffer;
            source.connect(this.audioContext.destination);
            source.start(chordStartTime);

            this.activeSources.push(source);

            this.scheduledEvents.push({
                markerId: marker.id,
                chord: marker.text,
                beat: marker.beat,
                chordStartTime,
                chordEndTime: chordStartTime + duration,
                beatTime,
            });
        }
    }

    /**
     * Hentikan seluruh pemutaran audio (lagu, chord, dan metronom).
     */
    stop() {
        this.isPlaying = false;

        if (this.activeSources.length > 0) {
            for (const source of this.activeSources) {
                try {
                    source.stop();
                    source.disconnect();
                } catch (e) {
                    // Ignore jika node sudah selesai atau belum start
                }
            }
            this.activeSources = [];
        }

        this.scheduledEvents = [];
    }
}

window.audioEngine = new AudioEngine();