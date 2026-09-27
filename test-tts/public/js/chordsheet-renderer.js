const statusEl = document.getElementById('status');
const chordsheetEl = document.getElementById('chordsheet');
const btnPlay = document.getElementById('play');
const btnStop = document.getElementById('stop');

const bpmInput = document.getElementById('bpm-input');
const btnBpmMinus5 = document.getElementById('bpm-minus5');
const btnBpmDown = document.getElementById('bpm-down');
const btnBpmUp = document.getElementById('bpm-up');
const btnBpmPlus5 = document.getElementById('bpm-plus5');
const btnBpmReset = document.getElementById('bpm-reset');

const btnTransposeUp = document.getElementById('transpose-up');
const btnTransposeDown = document.getElementById('transpose-down');
const btnTransposeReset = document.getElementById('transpose-reset');
const transposeValueEl = document.getElementById('transpose-value');

const engine = window.audioEngine;

let displayData = null;
let originalBpm = 120;
let currentBpm = 120;
let currentTransposeSteps = 0;
let highlightFrame = null;

function print(message) {
    if (statusEl) {
        statusEl.textContent = message;
    }
}

async function fetchTimeline() {
    const response = await fetch(`/api/songs/${window.SONG_ID}/timeline`);
    if (!response.ok) {
        throw new Error(`Gagal memuat timeline (${response.status})`);
    }
    return response.json();
}

async function fetchTransposedTimeline(steps, dispatch = true) {
    if (steps === 0) {
        return fetchTimeline();
    }
    const response = await fetch(`/api/songs/${window.SONG_ID}/transpose`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ steps, dispatch }),
    });
    if (!response.ok) {
        throw new Error(`Gagal transpose timeline (${response.status})`);
    }
    return response.json();
}

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value ?? '';
    return element.innerHTML;
}

function buildChordRow(placements, lineLength) {
    if (!placements || placements.length === 0) {
        return '';
    }
    const maxLength = Math.max(
        lineLength || 0,
        ...placements.map((placement) => (placement.position || 0) + (placement.chord_name ? placement.chord_name.length : 0))
    );
    const row = new Array(maxLength).fill(' ');
    for (const placement of placements) {
        if (!placement.chord_name) continue;
        const pos = placement.position || 0;
        placement.chord_name.split('').forEach((character, offset) => {
            row[pos + offset] = character;
        });
    }
    return row.join('');
}

function renderChordRowHtml(chordRowText, placements) {
    if (!placements || placements.length === 0) {
        return '';
    }

    let html = '';
    let cursor = 0;

    const sortedPlacements = [...placements].sort((left, right) => (left.position || 0) - (right.position || 0));

    for (const placement of sortedPlacements) {
        const pos = placement.position || 0;
        const chordName = placement.chord_name || '';
        const markerId = placement.chord_placement_id || placement.id;

        html += escapeHtml(chordRowText.slice(cursor, pos));
        html += `<span class="chord" id="marker-${markerId}">${escapeHtml(chordName)}</span>`;
        cursor = pos + chordName.length;
    }

    html += escapeHtml(chordRowText.slice(cursor));
    return html;
}

function renderChordsheet(data) {
    chordsheetEl.innerHTML = '';

    if (!data) return;

    // Prioritaskan render berbasis data.sections dari database
    if (data.sections && data.sections.length > 0) {
        for (const section of data.sections) {
            const sectionEl = document.createElement('div');
            sectionEl.className = 'chordsheet-section';

            sectionEl.dataset.section = section.name || '';
            const headerEl = document.createElement('div');
            headerEl.className = 'section-header';
            headerEl.innerHTML = `<span class="section-badge">[${escapeHtml(section.name || 'Section')}]</span>`;
            sectionEl.appendChild(headerEl);

            for (const line of section.lines || []) {
                const placements = line.placements || [];
                const lineContent = line.content || '';
                const chordRowText = buildChordRow(placements, lineContent.length);

                if (chordRowText.length > 0) {
                    const chordRow = document.createElement('div');
                    chordRow.className = 'chordsheet-line chord-row';
                    chordRow.dataset.lineId = line.line_id || line.id;
                    chordRow.innerHTML = renderChordRowHtml(chordRowText, placements);
                    sectionEl.appendChild(chordRow);
                }

                const lyricRow = document.createElement('div');
                lyricRow.className = 'chordsheet-line lyric-row';
                lyricRow.dataset.lineId = line.line_id || line.id;
                lyricRow.textContent = lineContent;
                sectionEl.appendChild(lyricRow);
            }

            chordsheetEl.appendChild(sectionEl);
        }
        return;
    }

    // Fallback: Kelompokkan markers berdasarkan section dari database
    const sectionsMap = new Map();

    for (const marker of data.markers || []) {
        const sectionName = marker.section || 'Section';
        if (!sectionsMap.has(sectionName)) {
            sectionsMap.set(sectionName, new Map());
        }

        const linesMap = sectionsMap.get(sectionName);
        if (!linesMap.has(marker.line_id)) {
            linesMap.set(marker.line_id, {
                content: marker.line_content,
                placements: [],
            });
        }

        linesMap.get(marker.line_id).placements.push(marker);
    }

    for (const [sectionName, linesMap] of sectionsMap) {
        const sectionEl = document.createElement('div');
        sectionEl.className = 'chordsheet-section';
        sectionEl.dataset.section = sectionName;

        const headerEl = document.createElement('div');
        headerEl.className = 'section-header';
        headerEl.innerHTML = `<span class="section-badge">[${escapeHtml(sectionName)}]</span>`;
        sectionEl.appendChild(headerEl);

        for (const [lineId, line] of linesMap) {
            const chordRowText = buildChordRow(line.placements, line.content.length);

            const chordRow = document.createElement('div');
            chordRow.className = 'chordsheet-line chord-row';
            chordRow.dataset.lineId = lineId;
            chordRow.innerHTML = renderChordRowHtml(chordRowText, line.placements);

            const lyricRow = document.createElement('div');
            lyricRow.className = 'chordsheet-line lyric-row';
            lyricRow.textContent = line.content;

            sectionEl.appendChild(chordRow);
            sectionEl.appendChild(lyricRow);
        }

        chordsheetEl.appendChild(sectionEl);
    }
}


async function preloadAudioFor(data) {
    if (!data.all_audio_ready) {
        const missing = [...new Set(data.markers.filter((marker) => !marker.audio_ready).map((marker) => marker.chord_text))];
        throw new Error(`Audio chord belum siap untuk: ${missing.join(', ')}`);
    }

    // 1. Preload file audio lagu (backing track) jika ada
    if (data.song && data.song.audio_url) {
        await engine.loadSongBuffer(data.song.audio_url);
    }

    // 2. Preload audio untuk tiap chord unik
    const loaded = new Set();
    for (const marker of data.markers) {
        if (!loaded.has(marker.chord_text) && marker.audio_url) {
            await engine.loadChordBuffer(marker.chord_text, marker.audio_url);
            loaded.add(marker.chord_text);
        }
    }
}

async function waitForTimelineReady(steps, timeoutMs = 30000) {
    const startedAt = Date.now();

    while (Date.now() - startedAt < timeoutMs) {
        const freshData = await fetchTransposedTimeline(steps, false);

        if (freshData.all_audio_ready) {
            return freshData;
        }

        await new Promise((resolve) => setTimeout(resolve, 1500));
    }

    throw new Error('Audio hasil transpose belum siap setelah menunggu. Coba lagi setelah queue selesai memproses.');
}

function clearAllHighlights() {
    document.querySelectorAll('.chord.active').forEach((element) => element.classList.remove('active'));
}

function stopHighlightLoop() {
    if (highlightFrame !== null) {
        cancelAnimationFrame(highlightFrame);
        highlightFrame = null;
    }
}

function startHighlightLoop(totalBeats) {
    stopHighlightLoop();

    const secondsPerBeat = 60.0 / currentBpm;
    const estimatedEndTime = engine.audioContext.currentTime + (totalBeats * secondsPerBeat) + 0.5;

    const tick = () => {
        const now = engine.audioContext.currentTime;

        if (now >= estimatedEndTime) {
            stopPlayback();
            print(`Selesai diputar.`);
            return;
        }

        clearAllHighlights();

        for (const event of engine.scheduledEvents) {
            const element = document.getElementById(`marker-${event.markerId}`);
            if (!element) {
                continue;
            }

            if (now >= event.chordStartTime && now < event.chordEndTime) {
                element.classList.add('active');
            }
        }

        highlightFrame = requestAnimationFrame(tick);
    };

    highlightFrame = requestAnimationFrame(tick);
}

function startPlayback() {
    if (!displayData) return;

    const timeline = (displayData.markers || []).map((marker) => ({
        id: marker.chord_placement_id,
        beat: marker.beat,
        text: marker.chord_text,
    }));

    const totalBeats = timeline.length > 0
        ? Math.max(...timeline.map((marker) => marker.beat)) + 4
        : 4;

    engine.playSongTimeline(timeline, currentBpm, totalBeats, originalBpm);
    clearAllHighlights();
    startHighlightLoop(totalBeats);

    btnPlay.disabled = true;
    btnStop.disabled = false;
    const hasSongAudio = Boolean(displayData.song?.audio_url);
    print(`Memainkan "${displayData.song.title}" pada ${currentBpm} BPM (Metronom + Chord Speech${hasSongAudio ? ' + Backing Track' : ''}).`);
}

function stopPlayback() {
    engine.stop();
    stopHighlightLoop();
    clearAllHighlights();
    btnPlay.disabled = false;
    btnStop.disabled = true;
    print('Stopped.');
}

function setBpm(newBpm) {
    const parsed = parseInt(newBpm, 10);

    if (isNaN(parsed)) return;

    currentBpm = Math.min(300, Math.max(20, parsed));

    if (bpmInput) {
        bpmInput.value = currentBpm;
    }

    if (engine.isPlaying) {
        startPlayback();
    } else if (displayData) {
        print(`BPM diubah menjadi ${currentBpm} (BPM asli: ${originalBpm}).`);
    }
}

async function refreshTimeline(steps) {
    const initialData = await fetchTransposedTimeline(steps);
    displayData = initialData;
    currentTransposeSteps = steps;

    transposeValueEl.textContent = steps >= 0 ? `+${steps}` : String(steps);

    renderChordsheet(displayData);

    if (!displayData.all_audio_ready) {
        print('Menunggu audio hasil transpose selesai diproses...');

        displayData = await waitForTimelineReady(steps);

        renderChordsheet(displayData);
    }
    await preloadAudioFor(displayData);
}


// ── Inisialisasi Otomatis (Tanpa Tombol Preload) ──────────────────
async function initPlayer() {
    try {
        print('Memuat data lagu...');
        displayData = await fetchTimeline();

        originalBpm = displayData.song?.bpm || 120;

        currentBpm = originalBpm;

        if (bpmInput) {
            bpmInput.value = currentBpm;
        }

        currentTransposeSteps = 0;

        if (transposeValueEl) {
            transposeValueEl.textContent = '0';
        }

        // Langsung tampilkan struktur section, lirik, dan chord
        renderChordsheet(displayData);

        // Preload audio di latar belakang
        print('Menyiapkan audio player...');
        await engine.init();
        await preloadAudioFor(displayData);

        btnPlay.disabled = false;
        print(`"${displayData.song.title}" siap diputar (${currentBpm} BPM).`);
    } catch (error) {
        print(`Status: ${error.message}`);
        if (displayData) {
            renderChordsheet(displayData);
        }
    }
}

// ── Event Listeners ──────────────────────────────────────────────
btnPlay.addEventListener('click', async () => {
    try {
        await engine.init();
        startPlayback();
    } catch (e) {
        print(`Gagal memulai audio: ${e.message}`);
    }
});
btnStop.addEventListener('click', () => {
    stopPlayback();
});

// BPM Controls (Hanya mempengaruhi live view & playback, tidak mengubah database)
if (btnBpmMinus5) {
    btnBpmMinus5.addEventListener('click', () => setBpm(currentBpm - 5));
}
if (btnBpmDown) {
    btnBpmDown.addEventListener('click', () => setBpm(currentBpm - 1));
}
if (btnBpmUp) {
    btnBpmUp.addEventListener('click', () => setBpm(currentBpm + 1));
}
if (btnBpmPlus5) {
    btnBpmPlus5.addEventListener('click', () => setBpm(currentBpm + 5));
}
if (btnBpmReset) {
    btnBpmReset.addEventListener('click', () => setBpm(originalBpm));
}
if (bpmInput) {
    bpmInput.addEventListener('change', (e) => setBpm(e.target.value));
    bpmInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            setBpm(e.target.value);
            bpmInput.blur();
        }
    });
}

// Transpose Controls
btnTransposeUp.addEventListener('click', async () => {
    try {
        btnPlay.disabled = true;
        print('Transpose +1 diproses...');
        await refreshTimeline(currentTransposeSteps + 1);
        btnPlay.disabled = false;
        print(`Transpose ${currentTransposeSteps >= 0 ? '+' : ''}${currentTransposeSteps} siap.`);
    } catch (error) {
        print(`Transpose gagal: ${error.message}`);
    }
});

btnTransposeDown.addEventListener('click', async () => {
    try {
        btnPlay.disabled = true;
        print('Transpose -1 diproses...');
        await refreshTimeline(currentTransposeSteps - 1);
        btnPlay.disabled = false;
        print(`Transpose ${currentTransposeSteps >= 0 ? '+' : ''}${currentTransposeSteps} siap.`);
    } catch (error) {
        print(`Transpose gagal: ${error.message}`);
    }
});

btnTransposeReset.addEventListener('click', async () => {
    try {
        btnPlay.disabled = true;
        print('Reset transpose diproses...');
        await refreshTimeline(0);
        btnPlay.disabled = false;
        print('Transpose reset ke 0.');
    } catch (error) {
        print(`Transpose gagal: ${error.message}`);
    }
});
// Jalankan auto-load saat halaman dimuat
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPlayer);
} else {
    initPlayer();
}
