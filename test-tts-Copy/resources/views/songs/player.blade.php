<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Chord Sheet Player</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .chordsheet-line { font-family: monospace; white-space: pre; margin: 0; }
        .chord-row .chord.active { text-decoration: underline; }
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