/**
 * AudioEngine
 * 
 * Mengatur sinkronisasi audio menggunakan Web Audio API:
 * 1. Audio lagu utama (backing track dari songs.file_path)
 * 2. Suara ketukan metronom (click)
 * 3. Suara pembacaan chord TTS (chord speech dari chords.file_path)
 * 
 * Sesuai Spesifikasi Fase 5-6 (§5.3 & §5.4):
 * - Multi-track GainNode terpisah untuk metronome, chord voice, reference audio.
 * - Mekanisme count-in 1 birama penuh sebelum songStartTime.
 * - Jump manual ke beat tertentu tanpa count-in ulang.
 * - Mute per-track independen.
 */
class AudioEngine {
    constructor() {
        this.audioContext = null;
        this.isPlaying = false;
        this.bpm = 120;
        this.timeSignatureNumerator = 4;

        // Cache hasil decode AudioBuffer: { 'click': AudioBuffer, 'song': AudioBuffer, [chordText]: AudioBuffer }
        this.buffers = {};

        // Track node audio aktif/terjadwal agar bisa dihentikan saat Stop
        this.activeSources = [];
        this.scheduledEvents = [];
        this.countInEvents = [];

        // Gain nodes untuk multi-track architecture (§5.3)
        this.metronomeGain = null;
        this.chordVoiceGain = null;
        this.referenceAudioGain = null;

        this.mutes = {
            metronome: false,
            chord: false,
            reference: false,
        };

        this.songStartTime = 0;
        this.currentStartBeat = 0;
    }

    /**
     * Inisialisasi AudioContext, GainNodes, & preload suara click dasar.
     */
    async init() {
        if (!this.audioContext) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }

        // Setup gain nodes jika belum dibuat
        if (!this.metronomeGain) {
            this.metronomeGain = this.audioContext.createGain();
            this.metronomeGain.gain.value = this.mutes.metronome ? 0 : 1.0;
            this.metronomeGain.connect(this.audioContext.destination);
        }
        if (!this.chordVoiceGain) {
            this.chordVoiceGain = this.audioContext.createGain();
            this.chordVoiceGain.gain.value = this.mutes.chord ? 0 : 1.0;
            this.chordVoiceGain.connect(this.audioContext.destination);
        }
        if (!this.referenceAudioGain) {
            this.referenceAudioGain = this.audioContext.createGain();
            this.referenceAudioGain.gain.value = this.mutes.reference ? 0 : 1.0;
            this.referenceAudioGain.connect(this.audioContext.destination);
        }

        if (this.audioContext.state === 'suspended') {
            try {
                await this.audioContext.resume();
            } catch (e) {
                // Diabaikan jika browser memerlukan user gesture langsung
            }
        }

        if (!this.buffers['click']) {
            await this.loadBuffer('click', '/audio/click-short.mp3');
        }
    }

    /**
     * Atur status mute per-track (§5.3).
     * @param {'metronome'|'chord'|'reference'} track 
     * @param {boolean} isMuted 
     */
    setMute(track, isMuted) {
        this.mutes[track] = Boolean(isMuted);
        const gainValue = this.mutes[track] ? 0 : 1.0;

        if (track === 'metronome' && this.metronomeGain) {
            this.metronomeGain.gain.setValueAtTime(gainValue, this.audioContext ? this.audioContext.currentTime : 0);
        } else if (track === 'chord' && this.chordVoiceGain) {
            this.chordVoiceGain.gain.setValueAtTime(gainValue, this.audioContext ? this.audioContext.currentTime : 0);
        } else if (track === 'reference' && this.referenceAudioGain) {
            this.referenceAudioGain.gain.setValueAtTime(gainValue, this.audioContext ? this.audioContext.currentTime : 0);
        }
    }

    isMuted(track) {
        return Boolean(this.mutes[track]);
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

    async loadChordBuffer(text, url) {
        await this.loadBuffer(text, url);
    }

    async loadSongBuffer(url) {
        await this.loadBuffer('song', url);
    }

    secondsPerBeat() {
        return 60.0 / this.bpm;
    }

    /**
     * Jadwalkan satu bunyi click metronom ke metronomeGain.
     */
    scheduleSound(bufferKey, time) {
        if (!this.buffers[bufferKey] || !this.metronomeGain) return;
        const source = this.audioContext.createBufferSource();
        source.buffer = this.buffers[bufferKey];
        source.connect(this.metronomeGain);
        source.start(time);
        this.activeSources.push(source);
    }

    /**
    * Memainkan timeline lagu dengan scheduling presisi (§5.3 & §5.4):
    * - Selalu memberi count-in 1 birama sebelum audio mulai
    * - Backing track & chord voice mulai tepat di songStartTime
    * - Jump manual ke beat tertentu tetap melewati count-in yang sama
     */
    playSongTimeline(timeline, bpm, totalBeats, originalBpm = null, numerator = 4, startFromBeat = 0) {
        this.stop();

        this.bpm = bpm;
        this.timeSignatureNumerator = numerator || 4;
        this.currentStartBeat = Math.max(0, startFromBeat);
        this.isPlaying = true;
        this.scheduledEvents = [];
        this.countInEvents = [];
        this.activeSources = [];

        if (this.audioContext.state === 'suspended') {
            this.audioContext.resume();
        }

        const beatsPerBar = this.timeSignatureNumerator;
        const secondsPerBeat = this.secondsPerBeat();
        const countInDuration = beatsPerBar * secondsPerBeat;

        const t0 = this.audioContext.currentTime + 0.1;
        const songStartTime = t0 + countInDuration;
        this.songStartTime = songStartTime;

        // 1. METRONOM COUNT-IN & SONG CLICKS (§5.4)
        for (let beat = -beatsPerBar; beat < 0; beat++) {
            const clickTime = songStartTime + beat * secondsPerBeat;
            this.scheduleSound('click', clickTime);
            this.countInEvents.push({
                countNumber: beatsPerBar + beat + 1, // e.g. 1, 2, 3, 4
                time: clickTime,
                endTime: clickTime + secondsPerBeat,
            });
        }

        // Metronom selama lagu
        for (let beat = this.currentStartBeat; beat < totalBeats; beat++) {
            const beatOffset = beat - this.currentStartBeat;
            const clickTime = songStartTime + beatOffset * secondsPerBeat;
            this.scheduleSound('click', clickTime);
        }

        // 2. AUDIO REFERENSI (BACKING TRACK) (§5.4)
        if (this.buffers['song'] && this.referenceAudioGain) {
            const songSource = this.audioContext.createBufferSource();
            songSource.buffer = this.buffers['song'];
            if (originalBpm && originalBpm > 0) {
                songSource.playbackRate.value = bpm / originalBpm;
            }
            songSource.connect(this.referenceAudioGain);

            const trackOffsetSeconds = this.currentStartBeat * secondsPerBeat;
            const duration = this.buffers['song'].duration;

            if (trackOffsetSeconds < duration) {
                songSource.start(songStartTime, trackOffsetSeconds);
                this.activeSources.push(songSource);
            }
        }

        // 3. SUARA CHORD (TTS SPEECH) (§5.4)
        for (const marker of timeline) {
            if (marker.beat < this.currentStartBeat) continue;

            const beatOffset = marker.beat - this.currentStartBeat;
            const beatTime = songStartTime + beatOffset * secondsPerBeat;
            const chordBuffer = this.buffers[marker.text];
            if (!chordBuffer) continue;

            const duration = chordBuffer.duration;
            let chordStartTime = beatTime - duration;

            if (chordStartTime < this.audioContext.currentTime) {
                chordStartTime = this.audioContext.currentTime + 0.01;
            }

            const source = this.audioContext.createBufferSource();
            source.buffer = chordBuffer;
            source.connect(this.chordVoiceGain);
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
     * Hentikan seluruh pemutaran audio.
     */
    stop() {
        this.isPlaying = false;

        if (this.activeSources.length > 0) {
            for (const source of this.activeSources) {
                try {
                    source.stop();
                    source.disconnect();
                } catch (e) {
                    // Ignore
                }
            }
            this.activeSources = [];
        }

        this.scheduledEvents = [];
        this.countInEvents = [];
    }
}

window.audioEngine = new AudioEngine();