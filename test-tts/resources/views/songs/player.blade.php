<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chord Sheet Player</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-start: #f6efe3;
            --bg-end: #efe8ff;
            --card: rgba(255, 255, 255, 0.8);
            --text: #1f2430;
            --muted: #4e5667;
            --accent: #db5d2a;
            --accent-2: #0f7a70;
            --line: #d7dce7;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            color: var(--text);
            background:
                radial-gradient(1200px 500px at 10% -10%, rgba(219, 93, 42, 0.2), transparent 60%),
                radial-gradient(1000px 500px at 90% 0%, rgba(15, 122, 112, 0.18), transparent 58%),
                linear-gradient(145deg, var(--bg-start), var(--bg-end));
            padding: 24px;
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
            background: var(--card);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 18px 40px rgba(22, 27, 42, 0.12);
            border-radius: 18px;
            padding: 24px;
            backdrop-filter: blur(8px);
        }

        h1 {
            font-size: clamp(1.4rem, 2.2vw, 2rem);
            letter-spacing: 0.02em;
            margin: 0 0 6px;
        }

        .subtitle {
            margin: 0 0 20px;
            color: var(--muted);
        }

        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 14px;
        }

        .button {
            border: 0;
            border-radius: 12px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            padding: 10px 14px;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
        }

        .button-primary {
            background: var(--accent);
            color: #fff;
        }

        .button-secondary {
            background: #fff;
            color: var(--text);
            border: 1px solid var(--line);
        }

        .button-sm {
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .control-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 4px 8px;
        }
        .control-label {
            font-weight: 700;
            font-size: 0.82rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-right: 2px;
        }

        .bpm-input {
            width: 58px;
            padding: 5px 2px;
            border: 1px solid var(--line);
            border-radius: 8px;
            text-align: center;
            font-family: inherit;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--accent);
            background: #fff;
            -moz-appearance: textfield;
        }
        .bpm-input::-webkit-outer-spin-button,
        .bpm-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .bpm-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(219, 93, 42, 0.2);
        }
        .transpose {
            margin-left: auto;
        }

        .transpose-value {
            min-width: 32px;
            text-align: center;
            font-weight: 700;
            color: var(--accent-2);
        }

        #status {
            border: 1px dashed var(--line);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.7);
            padding: 10px 12px;
            color: var(--muted);
            min-height: 44px;
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }

        #chordsheet {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            padding: 16px;
            overflow-x: auto;
        }

        .chordsheet-section {
            margin-bottom: 22px;
        }
        .chordsheet-section:last-child {
            margin-bottom: 0;
        }
        .section-header {
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px dashed var(--line);
        }
        .section-badge {
            display: inline-block;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: var(--accent-2);
            background: rgba(15, 122, 112, 0.1);
            padding: 3px 10px;
            border-radius: 6px;
            border: 1px solid rgba(15, 122, 112, 0.25);
        }

        .chordsheet-line {
            font-family: 'Space Mono', monospace;
            white-space: pre;
            margin: 0;
            font-size: 0.95rem;
        }

        .chord-row {
            color: #0e4f94;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .chord {
            border-radius: 4px;
            transition: background-color 0.12s ease, color 0.12s ease;
        }

        .chord-row .chord.active {
            background: #ffe6d8;
            color: #a13f1d;
            outline: 1px solid rgba(161, 63, 29, 0.2);
        }

        .lyric-row {
            color: #2f3748;
            margin-bottom: 14px;
        }

        @media (max-width: 768px) {
            body {
                padding: 14px;
            }

            .container {
                padding: 16px;
            }

            .transpose {
                width: 100%;
                margin-left: 0;
                justify-content: flex-start;
            }

            .control-group {
                flex-wrap: wrap;
            }

        }
    </style>
</head>
<body>
    <main class="container">
        <h1>Chord Sheet Player</h1>
        <p class="subtitle">Sinkronisasi chord, audio, dan transpose dalam satu pemutar.</p>

        <div class="controls">
            <button class="button button-primary" id="play" disabled>Play</button>
            <button class="button button-secondary" id="stop" disabled>Stop</button>

            <div class="control-group bpm-control">
                <span class="control-label">BPM</span>
                <button type="button" class="button button-secondary button-sm" id="bpm-minus5" title="Kurangi 5 BPM">-5</button>
                <button type="button" class="button button-secondary button-sm" id="bpm-down" title="Kurangi 1 BPM">-1</button>
                <input type="number" id="bpm-input" min="20" max="300" class="bpm-input" value="120" title="BPM Live (tidak mengubah database)">
                <button type="button" class="button button-secondary button-sm" id="bpm-up" title="Tambah 1 BPM">+1</button>
                <button type="button" class="button button-secondary button-sm" id="bpm-plus5" title="Tambah 5 BPM">+5</button>
                <button type="button" class="button button-secondary button-sm" id="bpm-reset" title="Reset ke tempo asli lagu">Reset</button>
            </div>
            <div class="control-group transpose">
                <span class="control-label">Transpose</span>
                <button type="button" class="button button-secondary button-sm" id="transpose-down">-1</button>
                <span class="transpose-value" id="transpose-value">0</span>
                <button type="button" class="button button-secondary button-sm" id="transpose-up">+1</button>
                <button type="button" class="button button-secondary button-sm" id="transpose-reset">Reset</button>
            </div>
        </div>

        <div id="status"></div>
        <div id="chordsheet"></div>
    </main>

    <script>
        window.SONG_ID = {{ $songId }};
    </script>
    <script src="/js/audio-engine.js"></script>
    <script src="/js/chordsheet-renderer.js"></script>
</body>
</html>