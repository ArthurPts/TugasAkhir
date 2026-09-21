const statusEl = document.getElementById('status');
const chordsheetEl = document.getElementById('chordsheet');
const btnPreload = document.getElementById('preload');
const btnPlay = document.getElementById('play');
const btnStop = document.getElementById('stop');
const btnTransposeUp = document.getElementById('transpose-up');
const btnTransposeDown = document.getElementById('transpose-down');
const btnTransposeReset = document.getElementById('transpose-reset');
const transposeValueEl = document.getElementById('transpose-value');

const engine = window.audioEngine;

let timelineData = null;
let displayData = null;
let currentTransposeSteps = 0;
let highlightFrame = null;

function print(message) {
    statusEl.textContent = message;
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
    element.textContent = value;
    return element.innerHTML;
}

function buildChordRow(placements, lineLength) {
    const maxLength = Math.max(lineLength, ...placements.map((placement) => placement.position + placement.chord_name.length));
    const row = new Array(maxLength).fill(' ');

    for (const placement of placements) {
        placement.chord_name.split('').forEach((character, offset) => {
            row[placement.position + offset] = character;
        });
    }

    return row.join('');
}

function renderChordRowHtml(chordRowText, placements) {
    let html = '';
    let cursor = 0;

    for (const placement of [...placements].sort((left, right) => left.position - right.position)) {
        html += escapeHtml(chordRowText.slice(cursor, placement.position));
        html += `<span class="chord" id="marker-${placement.chord_placement_id}">${escapeHtml(placement.chord_name)}</span>`;
        cursor = placement.position + placement.chord_name.length;
    }

    html += escapeHtml(chordRowText.slice(cursor));
    return html;
}

function renderChordsheet(data) {
    chordsheetEl.innerHTML = '';

    const lines = new Map();

    for (const marker of data.markers) {
        if (!lines.has(marker.line_id)) {
            lines.set(marker.line_id, {
                content: marker.line_content,
                placements: [],
            });
        }

        lines.get(marker.line_id).placements.push(marker);
    }

    for (const [lineId, line] of lines) {
        const chordRowText = buildChordRow(line.placements, line.content.length);

        const chordRow = document.createElement('div');
        chordRow.className = 'chordsheet-line chord-row';
        chordRow.dataset.lineId = lineId;
        chordRow.innerHTML = renderChordRowHtml(chordRowText, line.placements);

        const lyricRow = document.createElement('div');
        lyricRow.className = 'chordsheet-line lyric-row';
        lyricRow.textContent = line.content;

        chordsheetEl.appendChild(chordRow);
        chordsheetEl.appendChild(lyricRow);
    }
}

async function preloadAudioFor(data) {
    if (!data.all_audio_ready) {
        const missing = [...new Set(data.markers.filter((marker) => !marker.audio_ready).map((marker) => marker.chord_text))];
        throw new Error(`Audio belum siap untuk: ${missing.join(', ')}`);
    }

    for (const marker of data.markers) {
        await engine.loadChordBuffer(marker.chord_text, marker.audio_url);
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

function startHighlightLoop() {
    const tick = () => {
        const now = engine.audioContext.currentTime;

        for (const event of engine.scheduledEvents) {
            const marker = displayData.markers.find((item) => item.chord_text === event.chord && item.beat === event.beat);
            if (!marker) {
                continue;
            }

            const element = document.getElementById(`marker-${marker.chord_placement_id}`);
            if (!element) {
                continue;
            }

            element.classList.toggle('active', now >= event.chordStartTime && now < event.chordEndTime);
        }

        highlightFrame = requestAnimationFrame(tick);
    };

    highlightFrame = requestAnimationFrame(tick);
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

btnPreload.addEventListener('click', async () => {
    try {
        print('Memuat data lagu...');
        await engine.init();

        timelineData = await fetchTimeline();
        displayData = timelineData;
        currentTransposeSteps = 0;
        transposeValueEl.textContent = '0';

        renderChordsheet(displayData);
        await preloadAudioFor(displayData);

        btnPlay.disabled = false;
        print(`Preload sukses. "${displayData.song.title}" siap diputar.`);
    } catch (error) {
        print(`Preload gagal: ${error.message}`);
    }
});

btnPlay.addEventListener('click', () => {
    const timeline = displayData.markers.map((marker) => ({
        beat: marker.beat,
        text: marker.chord_text,
    }));

    const totalBeats = Math.max(...timeline.map((marker) => marker.beat)) + 4;

    engine.playSongTimeline(timeline, displayData.song.bpm, totalBeats);
    clearAllHighlights();
    startHighlightLoop();

    btnPlay.disabled = true;
    btnStop.disabled = false;
    print(`Playing "${displayData.song.title}" at ${displayData.song.bpm} BPM (transpose ${currentTransposeSteps >= 0 ? '+' : ''}${currentTransposeSteps}).`);
});

btnStop.addEventListener('click', () => {
    engine.stop();
    stopHighlightLoop();
    clearAllHighlights();
    btnPlay.disabled = false;
    btnStop.disabled = true;
    print('Stopped.');
});

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