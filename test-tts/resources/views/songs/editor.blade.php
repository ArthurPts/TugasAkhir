<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChordCue — Editor Lagu & Chord</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-start: #f6efe3;
            --bg-end: #efe8ff;
            --card-bg: rgba(255, 255, 255, 0.88);
            --text-main: #1e2532;
            --text-muted: #5e6678;
            --accent-orange: #db5d2a;
            --accent-teal: #0f7a70;
            --accent-blue: #1d6bc0;
            --border-color: #dbe0eb;
            --border-focus: #db5d2a;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            background:
                radial-gradient(1200px 500px at 5% -5%, rgba(219, 93, 42, 0.18), transparent 60%),
                radial-gradient(1000px 500px at 95% 5%, rgba(15, 122, 112, 0.16), transparent 58%),
                linear-gradient(145deg, var(--bg-start), var(--bg-end));
            padding: 20px;
        }

        .editor-container {
            max-width: 1280px;
            margin: 0 auto;
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.95);
            box-shadow: 0 20px 48px rgba(18, 24, 40, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Top Header */
        .editor-header {
            padding: 22px 28px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            background: rgba(255, 255, 255, 0.6);
        }

        .header-title-box h1 {
            font-size: 1.6rem;
            margin: 0 0 4px;
            letter-spacing: -0.01em;
            color: var(--text-main);
        }

        .song-meta-text {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        /* Button styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 8px 16px;
            border-radius: 10px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--accent-orange);
            color: #fff;
        }

        .btn-primary:hover {
            background: #c54f20;
        }

        .btn-secondary {
            background: #fff;
            color: var(--text-main);
            border-color: var(--border-color);
        }

        .btn-secondary:hover {
            background: #f4f6fa;
        }

        .btn-teal {
            background: var(--accent-teal);
            color: #fff;
        }

        .btn-teal:hover {
            background: #0b6058;
        }

        .btn-outline-primary {
            background: transparent;
            color: var(--accent-orange);
            border-color: var(--accent-orange);
        }

        .btn-outline-danger {
            background: transparent;
            color: #c92a2a;
            border-color: #ffd8d8;
        }

        .btn-outline-danger:hover {
            background: #ffe3e3;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.82rem;
            border-radius: 8px;
        }

        .btn-xs {
            padding: 2px 7px;
            font-size: 0.75rem;
            border-radius: 6px;
        }

        /* Mode Switcher Group */
        .mode-switcher {
            display: inline-flex;
            background: #edf1f7;
            padding: 4px;
            border-radius: 12px;
            gap: 4px;
        }

        .mode-btn {
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-muted);
            padding: 6px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .mode-btn.active {
            background: #fff;
            color: var(--text-main);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        /* Editor Layout: Sidebar + Canvas */
        .editor-body {
            display: flex;
            min-height: 640px;
        }

        /* Sidebar: Pustaka Chord (M5) */
        .editor-sidebar {
            width: 270px;
            border-right: 1px solid var(--border-color);
            padding: 20px;
            background: rgba(255, 255, 255, 0.5);
            display: flex;
            flex-direction: column;
            gap: 16px;
            flex-shrink: 0;
        }

        .sidebar-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .search-box {
            position: relative;
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
            box-shadow: 0 0 0 2px rgba(219, 93, 42, 0.15);
        }

        .chord-palette-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            max-height: 480px;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
            user-select: none;
        }

        .chord-chip:hover {
            border-color: var(--accent-orange);
            background: #fff9f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(219, 93, 42, 0.15);
        }

        .chord-chip:active {
            cursor: grabbing;
        }

        .chord-name {
            display: block;
            font-family: 'Space Mono', monospace;
            font-weight: 700;
            font-size: 1rem;
            color: var(--accent-blue);
        }

        .chord-pronounce {
            display: block;
            font-size: 0.72rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Canvas Area */
        .editor-canvas-wrapper {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            background: rgba(255, 255, 255, 0.3);
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
            font-size: 0.86rem;
            color: var(--text-muted);
        }

        /* Section Cards */
        .section-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);
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
            background: rgba(15, 122, 112, 0.12);
            color: var(--accent-teal);
            border: 1px solid rgba(15, 122, 112, 0.25);
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

        /* M3: Simple Lyric Line */
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
            background: #ffe6d8;
            color: #a13f1d;
            border: 1px solid rgba(161, 63, 29, 0.3);
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
            background: #ffd5c0;
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
            background: #ffe3d1;
            border-bottom: 2px solid var(--accent-orange);
        }

        .line-actions {
            position: absolute;
            right: 8px;
            top: 8px;
        }

        /* M4: Beat Grid Mode */
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

        .beat-cells-row {
            display: flex;
            gap: 6px;
        }

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

        .beat-cell:hover {
            border-color: var(--accent-orange);
            background: #fffbf9;
        }

        .beat-cell.drag-over {
            border-color: var(--accent-orange);
            border-style: solid;
            background: #ffebe0;
            box-shadow: 0 0 0 2px rgba(219, 93, 42, 0.2);
        }

        .beat-cell.has-chord {
            border-style: solid;
            border-color: #ffd8c4;
            background: #fff9f5;
        }

        .beat-cell-num {
            font-size: 0.68rem;
            color: #adb5bd;
            font-weight: 700;
        }

        .placed-beat-chord {
            background: var(--accent-orange);
            color: #fff;
            font-family: 'Space Mono', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(219, 93, 42, 0.3);
            user-select: none;
            cursor: move;
        }

        .placed-beat-chord:hover {
            transform: scale(1.05);
            background: #c54f20;
        }

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

        .editor-alert.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

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

        @media (max-width: 860px) {
            .editor-body {
                flex-direction: column;
            }
            .editor-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }
            .chord-palette-grid {
                grid-template-columns: repeat(4, 1fr);
                max-height: 180px;
            }
        }
    </style>
</head>
<body>
    <div class="editor-container">
        <!-- Top Bar -->
        <header class="editor-header">
            <div class="header-title-box">
                <h1 id="song-title">Memuat Lagu...</h1>
                <div class="song-meta-text" id="song-meta"></div>
            </div>

            <div class="header-actions">
                <div class="mode-switcher">
                    <button class="mode-btn active" id="btn-mode-simple" title="Editor Sederhana: Drag chord ke atas karakter huruf lirik">Mode Sederhana</button>
                    <button class="mode-btn" id="btn-mode-beat" title="Beat Grid Editor: Drag chord ke ketukan bar/beat presisi">Mode Beat</button>
                </div>

                <a href="/songs/{{ $songId }}/player" class="btn btn-teal" title="Buka Halaman Pemutar Latihan">
                    ▶ Buka Player
                </a>
            </div>
        </header>

        <!-- Body Layout -->
        <div class="editor-body">
            <!-- Sidebar: Chord Library (M5) -->
            <aside class="editor-sidebar">
                <div class="sidebar-title">
                    <span>Pustaka Chord</span>
                    <button class="btn btn-xs btn-outline-primary" id="btn-add-custom-chord">+ Custom</button>
                </div>

                <div class="search-box">
                    <input type="text" id="chord-search" class="search-input" placeholder="Cari chord...">
                </div>

                <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0;">
                    Drag chord dari kotak di bawah ke lirik atau sel ketukan:
                </p>

                <div class="chord-palette-grid" id="chord-palette-list">
                    <!-- Populated dynamically -->
                </div>
            </aside>

            <!-- Main Editor Canvas (M2, M3, M4, M6) -->
            <main class="editor-canvas-wrapper">
                <!-- Backing Track Bar (§5.4 & M6) -->
                <div class="backing-track-bar">
                    <div>
                        <strong>Audio Referensi (Backing Track)</strong>
                        <div class="backing-info">
                            File otomatis sinkron 1 birama count-in sebelum ketukan pertama lagu.
                        </div>
                    </div>
                    <div id="audio-status" style="display: flex; align-items: center; gap: 8px;"></div>
                    <div>
                        <input type="file" id="audio-file-input" accept=".mp3,.wav,.ogg" style="display: none;">
                        <button class="btn btn-sm btn-secondary" onclick="document.getElementById('audio-file-input').click()">
                            📁 Upload Audio
                        </button>
                    </div>
                </div>

                <!-- Canvas Toolbar -->
                <div class="canvas-toolbar">
                    <button class="btn btn-sm btn-primary" id="btn-add-section">+ Tambah Section</button>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">
                        💡 <em>Tips:</em> Dobel-klik chord pada lirik atau sel ketukan untuk menghapusnya.
                    </span>
                </div>

                <!-- Canvas Content Area -->
                <div id="editor-canvas"></div>
            </main>
        </div>
    </div>

    <!-- Alert / Toast Notification -->
    <div id="editor-notification" class="editor-alert"></div>

    <script src="/js/chord-editor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editor = new ChordEditor({{ $songId }});
            editor.init();
        });
    </script>
</body>
</html>
