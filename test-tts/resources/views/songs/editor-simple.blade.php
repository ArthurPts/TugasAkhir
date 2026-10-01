<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor Sederhana — {{ $song->title }} | ChordCue</title>
    <meta name="description" content="Editor chord mode sederhana untuk lagu {{ $song->title }} — drag chord ke atas karakter lirik">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-start: #f0f7ff;
            --bg-end: #e8f0fe;
            --card-bg: rgba(255, 255, 255, 0.9);
            --text-main: #1a2640;
            --text-muted: #5c6b84;
            --accent: #3b82f6;
            --accent-dark: #2563eb;
            --accent-teal: #0f7a70;
            --border-color: #dce4ef;
            --border-focus: #3b82f6;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            background:
                radial-gradient(1200px 500px at 5% -5%, rgba(59, 130, 246, 0.14), transparent 60%),
                radial-gradient(1000px 500px at 95% 5%, rgba(15, 122, 112, 0.12), transparent 58%),
                linear-gradient(145deg, var(--bg-start), var(--bg-end));
            padding: 20px;
        }

        .editor-container {
            max-width: 1300px;
            margin: 0 auto;
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.95);
            box-shadow: 0 20px 48px rgba(18,24,40,0.1);
            border-radius: 20px;
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Header */
        .editor-header {
            padding: 20px 28px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            background: rgba(255,255,255,0.6);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: #fff;
            color: var(--text-muted);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }

        .back-btn:hover {
            background: #f0f4ff;
            border-color: var(--accent);
            color: var(--accent);
        }

        .header-title-box h1 {
            font-size: 1.45rem;
            margin: 0 0 3px;
            letter-spacing: -0.01em;
            color: var(--text-main);
        }

        .song-meta-text {
            color: var(--text-muted);
            font-size: 0.88rem;
            font-weight: 500;
        }

        .mode-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: var(--accent-dark);
            font-size: 0.82rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .mode-badge svg { width: 14px; height: 14px; }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 8px 16px;
            border-radius: 10px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            gap: 6px;
        }

        .btn:hover { transform: translateY(-1px); }

        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-dark); }

        .btn-secondary { background: #fff; color: var(--text-main); border-color: var(--border-color); }
        .btn-secondary:hover { background: #f4f8ff; }

        .btn-teal { background: var(--accent-teal); color: #fff; }
        .btn-teal:hover { background: #0b6058; }

        .btn-outline-primary { background: transparent; color: var(--accent); border-color: var(--accent); }
        .btn-outline-danger { background: transparent; color: #c92a2a; border-color: #ffd8d8; }
        .btn-outline-danger:hover { background: #ffe3e3; }

        .btn-sm { padding: 5px 10px; font-size: 0.8rem; border-radius: 8px; }
        .btn-xs { padding: 2px 7px; font-size: 0.74rem; border-radius: 6px; }

        /* Header actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .switch-mode-links {
            display: inline-flex;
            background: #edf1f7;
            padding: 3px;
            border-radius: 10px;
            gap: 3px;
        }

        .switch-mode-links a {
            font-family: inherit;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--text-muted);
            padding: 5px 12px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .switch-mode-links a.active {
            background: #fff;
            color: var(--text-main);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        /* Body layout */
        .editor-body {
            display: flex;
            min-height: 640px;
        }

        /* Sidebar */
        .editor-sidebar {
            width: 260px;
            border-right: 1px solid var(--border-color);
            padding: 20px;
            background: rgba(255,255,255,0.5);
            display: flex;
            flex-direction: column;
            gap: 14px;
            flex-shrink: 0;
        }

        .sidebar-title {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .search-input {
            width: 100%;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-family: inherit;
            font-size: 0.88rem;
            background: #fff;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }

        .chord-palette-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            max-height: 500px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .chord-chip {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 8px 10px;
            cursor: grab;
            text-align: center;
            transition: all 0.12s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            user-select: none;
        }

        .chord-chip:hover {
            border-color: var(--accent);
            background: #f0f6ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.15);
        }

        .chord-chip:active { cursor: grabbing; }

        .chord-name {
            display: block;
            font-family: 'Space Mono', monospace;
            font-weight: 700;
            font-size: 0.98rem;
            color: var(--accent-dark);
        }

        .chord-pronounce {
            display: block;
            font-size: 0.7rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Canvas */
        .editor-canvas-wrapper {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            background: rgba(255,255,255,0.3);
        }

        .canvas-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px dashed var(--border-color);
        }

        .backing-track-bar {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 12px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .backing-info {
            font-size: 0.84rem;
            color: var(--text-muted);
        }

        /* Section Cards */
        .section-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.03);
        }

        .section-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #edf1f7;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .badge-primary {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-dark);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .badge-success {
            background: #e6fcf5;
            color: #0ca678;
            border: 1px solid #c3fae8;
        }

        .badge-secondary {
            background: #f1f3f5;
            color: #868e96;
            border: 1px solid #dee2e6;
        }

        /* M3: Simple Mode */
        .simple-lyric-line {
            margin-bottom: 18px;
            padding: 8px 12px;
            background: #fdfdfd;
            border: 1px solid #edf1f7;
            border-radius: 10px;
            position: relative;
        }

        .simple-chord-track {
            height: 30px;
            position: relative;
            margin-bottom: 4px;
        }

        .placed-chord-badge {
            position: absolute;
            top: 2px;
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid rgba(30, 64, 175, 0.25);
            border-radius: 6px;
            padding: 2px 7px;
            font-family: 'Space Mono', monospace;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: move;
            z-index: 5;
            user-select: none;
            transition: transform 0.1s ease;
        }

        .placed-chord-badge:hover {
            transform: scale(1.05);
            background: #bfdbfe;
        }

        .simple-lyric-chars {
            font-family: 'Space Mono', monospace;
            font-size: 1rem;
            line-height: 1.5;
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            user-select: none;
        }

        .char-slot {
            width: 12px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-bottom: 2px solid transparent;
            transition: all 0.1s ease;
        }

        .char-slot.drag-over {
            background: #dbeafe;
            border-bottom: 2px solid var(--accent);
        }

        .line-actions {
            position: absolute;
            right: 8px;
            top: 8px;
        }

        /* Beat Grid Mode (fallback if JS switches) */
        .beat-lyric-line-block {
            margin-bottom: 24px;
            background: #fcfcfd;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 14px 16px;
        }

        .beat-line-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .beat-lyric-text {
            color: var(--text-main);
            font-family: 'Space Mono', monospace;
        }

        .beat-grid-wrapper {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .beat-bar-box {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: #fff;
            padding: 8px;
            min-width: 220px;
            flex-shrink: 0;
        }

        .beat-bar-header {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--accent-teal);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
            text-align: center;
            border-bottom: 1px dashed #e9ecef;
            padding-bottom: 4px;
        }

        .beat-cells-row { display: flex; gap: 6px; }

        .beat-cell {
            flex: 1;
            height: 58px;
            border: 1px dashed #ced4da;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 4px;
            background: #fdfdfd;
            cursor: pointer;
            position: relative;
            transition: all 0.12s ease;
        }

        .beat-cell:hover { border-color: var(--accent); background: #f0f6ff; }
        .beat-cell.drag-over {
            border-color: var(--accent);
            border-style: solid;
            background: #e0effe;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .beat-cell.has-chord {
            border-style: solid;
            border-color: #93c5fd;
            background: #f0f6ff;
        }

        .beat-cell-num { font-size: 0.68rem; color: #adb5bd; font-weight: 700; }

        .placed-beat-chord {
            background: var(--accent);
            color: #fff;
            font-family: 'Space Mono', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
            user-select: none;
            cursor: move;
        }

        .placed-beat-chord:hover { transform: scale(1.05); background: var(--accent-dark); }

        /* Notifications */
        .editor-alert {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #fff;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.2s ease;
            z-index: 9999;
            pointer-events: none;
        }

        .editor-alert.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
        .alert-info { background: #228be6; }
        .alert-success { background: #12b886; }
        .alert-error { background: #fa5252; }

        .empty-state {
            padding: 48px 24px;
            text-align: center;
            color: var(--text-muted);
            border: 2px dashed var(--border-color);
            border-radius: 14px;
            margin: 20px 0;
        }

        /* Hint box */
        .hint-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.82rem;
            color: var(--accent-dark);
            line-height: 1.5;
        }

        @media (max-width: 860px) {
            .editor-body { flex-direction: column; }
            .editor-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }
            .chord-palette-grid { grid-template-columns: repeat(4, 1fr); max-height: 180px; }
        }
    </style>
</head>
<body>
    <div class="editor-container">
        <!-- Top Bar -->
        <header class="editor-header">
            <div class="header-left">
                <a href="{{ route('me.songs') }}" class="back-btn" title="Kembali ke Lagu Saya">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="header-title-box">
                    <h1 id="song-title">{{ $song->title }}</h1>
                    <div class="song-meta-text" id="song-meta">
                        {{ $song->artist?->name ?? 'Artis Tidak Diketahui' }} &bull;
                        {{ $song->bpm }} BPM &bull;
                        {{ $song->time_signature_numerator ?? 4 }}/{{ $song->time_signature_denominator ?? 4 }} &bull;
                        Key: {{ $song->default_key ?? 'C' }}
                    </div>
                </div>
            </div>

            <div class="header-actions">
                <span class="mode-badge">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Mode Sederhana
                </span>

                <div class="switch-mode-links">
                    <a href="{{ route('songs.editor.simple', $song) }}" class="active">Sederhana</a>
                    <a href="{{ route('songs.editor.beat', $song) }}">Beat Grid</a>
                </div>

                <a href="{{ route('songs.player', $song) }}" class="btn btn-teal">
                    <svg fill="currentColor" viewBox="0 0 20 20" style="width:16px;height:16px;">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                    </svg>
                    Buka Player
                </a>
            </div>
        </header>

        <!-- Body -->
        <div class="editor-body">
            <!-- Sidebar: Pustaka Chord (M5) -->
            <aside class="editor-sidebar">
                <div class="sidebar-title">
                    <span>Pustaka Chord</span>
                    <button class="btn btn-xs btn-outline-primary" id="btn-add-custom-chord">+ Custom</button>
                </div>

                <input type="text" id="chord-search" class="search-input" placeholder="Cari chord...">

                <div class="hint-box">
                    <strong>Mode Sederhana:</strong> Drag chord ke atas karakter huruf lirik untuk menempatkannya. Dobel-klik chord untuk menghapus.
                </div>

                <div class="hint-box">
                    <strong>Time Signature:</strong>
                    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:end;margin-top:8px;">
                        <label style="display:flex;flex-direction:column;gap:4px;font-size:0.75rem;">
                            <span>Ketukan/Birama</span>
                            <input type="number" id="time-signature-numerator" min="1" max="16" value="{{ $song->time_signature_numerator ?? 4 }}" style="padding:7px 10px;border:1px solid var(--border-color);border-radius:8px;font:inherit;">
                        </label>
                        <label style="display:flex;flex-direction:column;gap:4px;font-size:0.75rem;">
                            <span>Nilai Not</span>
                            <input type="number" id="time-signature-denominator" min="1" max="16" value="{{ $song->time_signature_denominator ?? 4 }}" style="padding:7px 10px;border:1px solid var(--border-color);border-radius:8px;font:inherit;">
                        </label>
                        <button type="button" class="btn btn-xs btn-primary" id="btn-save-time-signature">Simpan</button>
                    </div>
                    <div style="margin-top:6px;line-height:1.45;">Dipakai player untuk hitungan mundur 1 birama sebelum playback.</div>
                </div>

                <div class="chord-palette-grid" id="chord-palette-list">
                    <!-- Populated dynamically -->
                </div>
            </aside>

            <!-- Main Canvas -->
            <main class="editor-canvas-wrapper">
                <!-- Backing Track Bar (M6) -->
                <div class="backing-track-bar">
                    <div>
                        <strong>Audio Referensi (Backing Track)</strong>
                        <div class="backing-info">
                            File sinkron otomatis — 1 birama count-in sebelum ketukan pertama lagu.
                        </div>
                    </div>
                    <div id="audio-status" style="display:flex;align-items:center;gap:8px;"></div>
                    <div>
                        <input type="file" id="audio-file-input" accept=".mp3,.wav,.ogg" style="display:none;">
                        <button class="btn btn-sm btn-secondary" onclick="document.getElementById('audio-file-input').click()">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Upload Audio
                        </button>
                    </div>
                </div>

                <!-- Canvas Toolbar -->
                <div class="canvas-toolbar">
                    <button class="btn btn-sm btn-primary" id="btn-add-section">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Section
                    </button>
                    <span style="font-size:0.84rem;color:var(--text-muted);">
                        💡 <em>Tips:</em> Dobel-klik chord pada lirik untuk menghapusnya.
                    </span>
                </div>

                <!-- Canvas Content -->
                <div id="editor-canvas"></div>
            </main>
        </div>
    </div>

    <!-- Alert / Toast -->
    <div id="editor-notification" class="editor-alert"></div>

    <script src="/js/chord-editor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editor = new ChordEditor({{ $song->id }}, 'simple');
            editor.init();
        });
    </script>
</body>
</html>
