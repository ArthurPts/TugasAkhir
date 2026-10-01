/**
 * ChordCue - Chord & Beat Editor (M2, M3, M4, M5, M6)
 * 
 * Implementasi Editor Mode Sederhana & Mode Beat sesuai Spesifikasi Fase 5-6:
 * - §5.1 Dual-Koordinat (position ↔ start_beat di server)
 * - §5.2 Auto-continuation per baris untuk Mode Beat
 * - Collision 409 Handling & Force Override
 * - Pustaka Chord & Custom Chord (M5)
 * - Section & Lyric Line CRUD (M2)
 * - Backing track upload & delete (M6)
 */

class ChordEditor {
    constructor(songId, initialMode = 'simple') {
        this.songId = songId;
        this.song = null;
        this.chords = [];
        this.mode = initialMode; // 'simple' or 'beat'
        this.draggedChord = null;
        this.draggedPlacement = null;

        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async init() {
        await Promise.all([
            this.loadSong(),
            this.loadChords(),
        ]);

        this.setupEventListeners();
        this.render();
    }

    async loadSong() {
        const res = await fetch(`/api/songs/${this.songId}`);
        if (!res.ok) {
            this.showNotification('Gagal memuat data lagu', 'error');
            return;
        }
        this.song = await res.json();
    }

    async loadChords(search = '') {
        const url = search ? `/api/chords?search=${encodeURIComponent(search)}` : '/api/chords';
        const res = await fetch(url);
        if (res.ok) {
            this.chords = await res.json();
            this.renderChordPalette();
        }
    }

    get beatsPerBar() {
        return (this.song && this.song.time_signature_numerator) ? parseInt(this.song.time_signature_numerator, 10) : 4;
    }

    /**
     * Hitung displayStartBeat untuk setiap baris lirik (Auto-continuation §5.2).
     */
    calculateLineDisplayStarts() {
        const beatsPerBar = this.beatsPerBar;
        let cumulativeBeat = 0;
        const displayStarts = {};

        if (!this.song || !this.song.sections) return displayStarts;

        const sortedSections = [...this.song.sections].sort((a, b) => a.sequence - b.sequence);
        for (const section of sortedSections) {
            const sortedLines = [...(section.lyric_lines || [])].sort((a, b) => a.line_number - b.line_number);
            for (const line of sortedLines) {
                displayStarts[line.id] = cumulativeBeat;

                const placements = line.chord_placements || [];
                const validBeats = placements
                    .map(p => p.start_beat)
                    .filter(b => b !== null && b !== undefined);

                let lineMaxBeatUsed;
                if (validBeats.length === 0) {
                    lineMaxBeatUsed = cumulativeBeat + beatsPerBar - 1;
                } else {
                    lineMaxBeatUsed = Math.max(...validBeats);
                }

                // Bulatkan ke kelipatan bar berikutnya
                cumulativeBeat = Math.ceil((lineMaxBeatUsed + 1) / beatsPerBar) * beatsPerBar;
            }
        }

        return displayStarts;
    }

    setupEventListeners() {
        // Mode toggle buttons
        const btnSimple = document.getElementById('btn-mode-simple');
        const btnBeat = document.getElementById('btn-mode-beat');

        if (btnSimple && btnBeat) {
            btnSimple.addEventListener('click', () => {
                this.mode = 'simple';
                btnSimple.classList.add('active');
                btnBeat.classList.remove('active');
                this.render();
            });

            btnBeat.addEventListener('click', () => {
                this.mode = 'beat';
                btnBeat.classList.add('active');
                btnSimple.classList.remove('active');
                this.render();
            });
        }

        // Search chord
        const searchInput = document.getElementById('chord-search');
        if (searchInput) {
            let timeout = null;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.loadChords(e.target.value.trim());
                }, 300);
            });
        }

        // Add custom chord modal
        const btnAddCustomChord = document.getElementById('btn-add-custom-chord');
        if (btnAddCustomChord) {
            btnAddCustomChord.addEventListener('click', () => this.showCustomChordModal());
        }

        // Add section button
        const btnAddSection = document.getElementById('btn-add-section');
        if (btnAddSection) {
            btnAddSection.addEventListener('click', () => this.showAddSectionModal());
        }

        // Backing track upload
        const audioInput = document.getElementById('audio-file-input');
        if (audioInput) {
            audioInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.uploadBackingTrack(file);
                }
            });
        }

        const btnDeleteAudio = document.getElementById('btn-delete-audio');
        if (btnDeleteAudio) {
            btnDeleteAudio.addEventListener('click', () => this.deleteBackingTrack());
        }

        const btnSaveTimeSignature = document.getElementById('btn-save-time-signature');
        if (btnSaveTimeSignature) {
            btnSaveTimeSignature.addEventListener('click', () => this.saveTimeSignature());
        }
    }

    render() {
        this.renderSongHeader();
        this.renderChordPalette();

        const container = document.getElementById('editor-canvas');
        if (!container) return;

        if (this.mode === 'simple') {
            this.renderSimpleMode(container);
        } else {
            this.renderBeatMode(container);
        }
    }

    renderSongHeader() {
        if (!this.song) return;
        const titleEl = document.getElementById('song-title');
        const metaEl = document.getElementById('song-meta');
        const audioStatusEl = document.getElementById('audio-status');

        if (titleEl) titleEl.textContent = this.song.title;
        if (metaEl) {
            const artistName = this.song.artist ? this.song.artist.name : 'Unknown Artist';
            const num = this.song.time_signature_numerator || 4;
            const den = this.song.time_signature_denominator || 4;
            metaEl.textContent = `${artistName} • ${this.song.bpm} BPM • ${num}/${den} • ${this.song.visibility}`;
        }

        const numeratorInput = document.getElementById('time-signature-numerator');
        const denominatorInput = document.getElementById('time-signature-denominator');
        if (numeratorInput) {
            numeratorInput.value = this.song.time_signature_numerator || 4;
        }
        if (denominatorInput) {
            denominatorInput.value = this.song.time_signature_denominator || 4;
        }

        if (audioStatusEl) {
            if (this.song.file_path) {
                audioStatusEl.innerHTML = `
                    <span class="badge badge-success">✓ Backing Track Terpasang</span>
                    <button id="btn-delete-audio" class="btn btn-sm btn-outline-danger ml-2">Hapus Audio</button>
                `;
                document.getElementById('btn-delete-audio')?.addEventListener('click', () => this.deleteBackingTrack());
            } else {
                audioStatusEl.innerHTML = `
                    <span class="badge badge-secondary">Belum ada backing track</span>
                `;
            }
        }
    }

    async saveTimeSignature() {
        if (!this.song) return;

        const numeratorInput = document.getElementById('time-signature-numerator');
        const denominatorInput = document.getElementById('time-signature-denominator');
        if (!numeratorInput || !denominatorInput) return;

        const numerator = parseInt(numeratorInput.value, 10);
        const denominator = parseInt(denominatorInput.value, 10);

        const payload = {
            time_signature_numerator: Number.isFinite(numerator) ? numerator : null,
            time_signature_denominator: Number.isFinite(denominator) ? denominator : null,
        };

        try {
            const res = await fetch(`/api/songs/${this.songId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!res.ok) {
                const err = await res.json();
                this.showNotification(err.message || 'Gagal menyimpan time signature', 'error');
                return;
            }

            this.showNotification('Time signature berhasil diperbarui', 'success');
            await this.loadSong();
            this.render();
        } catch (e) {
            this.showNotification('Terjadi kesalahan saat menyimpan time signature', 'error');
        }
    }

    renderChordPalette() {
        const listEl = document.getElementById('chord-palette-list');
        if (!listEl) return;
        listEl.innerHTML = '';

        for (const chord of this.chords) {
            const chip = document.createElement('div');
            chip.className = 'chord-chip';
            chip.draggable = true;
            chip.dataset.chordId = chord.id;
            chip.dataset.chordName = chord.name;
            chip.innerHTML = `
                <span class="chord-name">${this.escape(chord.name)}</span>
                <span class="chord-pronounce">${this.escape(chord.pronunciation || '')}</span>
            `;

            chip.addEventListener('dragstart', (e) => {
                this.draggedChord = chord;
                this.draggedPlacement = null;
                e.dataTransfer.setData('text/plain', chord.name);
                e.dataTransfer.effectAllowed = 'copy';
            });

            chip.addEventListener('dragend', () => {
                this.draggedChord = null;
            });

            listEl.appendChild(chip);
        }
    }

    /**
     * M3: Render Simple Editor Mode (Drag over characters in lyrics)
     */
    renderSimpleMode(container) {
        container.innerHTML = '';

        if (!this.song.sections || this.song.sections.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>Belum ada section. Tambahkan section pertama Anda untuk mulai mengisi lirik dan menempatkan chord.</p>
                </div>
            `;
            return;
        }

        const sortedSections = [...this.song.sections].sort((a, b) => a.sequence - b.sequence);

        for (const section of sortedSections) {
            const sectionCard = document.createElement('div');
            sectionCard.className = 'section-card';
            sectionCard.innerHTML = `
                <div class="section-card-header">
                    <div class="section-title">
                        <span class="badge badge-primary">${this.escape(section.name)}</span>
                        <span class="text-muted ml-2">(Urutan: ${section.sequence})</span>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-sm btn-outline-primary btn-add-line" data-section-id="${section.id}">+ Baris</button>
                        <button class="btn btn-sm btn-outline-danger btn-delete-section" data-section-id="${section.id}">Hapus</button>
                    </div>
                </div>
                <div class="section-lines" id="section-lines-${section.id}"></div>
            `;

            container.appendChild(sectionCard);

            const linesContainer = sectionCard.querySelector(`#section-lines-${section.id}`);
            const sortedLines = [...(section.lyric_lines || [])].sort((a, b) => a.line_number - b.line_number);

            for (const line of sortedLines) {
                const lineRow = this.createSimpleLineElement(line);
                linesContainer.appendChild(lineRow);
            }
        }

        // Attach action handlers
        container.querySelectorAll('.btn-add-line').forEach(btn => {
            btn.addEventListener('click', () => {
                const secId = parseInt(btn.dataset.sectionId, 10);
                this.showAddLineModal(secId);
            });
        });

        container.querySelectorAll('.btn-delete-section').forEach(btn => {
            btn.addEventListener('click', () => {
                const secId = parseInt(btn.dataset.sectionId, 10);
                this.deleteSection(secId);
            });
        });
    }

    createSimpleLineElement(line) {
        const lineEl = document.createElement('div');
        lineEl.className = 'simple-lyric-line';
        lineEl.dataset.lineId = line.id;

        const content = line.content || '';
        const placements = line.chord_placements || [];

        // Build chord track and char track
        const chordTrack = document.createElement('div');
        chordTrack.className = 'simple-chord-track';

        // Render placed chords
        for (const p of placements) {
            const marker = document.createElement('div');
            marker.className = 'placed-chord-badge';
            marker.draggable = true;
            marker.textContent = p.chord ? p.chord.name : 'Chord';
            marker.style.left = `${(p.position || 0) * 12}px`; // 12px monospace character width
            marker.dataset.placementId = p.id;
            marker.dataset.position = p.position;

            marker.addEventListener('dragstart', (e) => {
                e.stopPropagation();
                this.draggedPlacement = p;
                this.draggedChord = null;
                e.dataTransfer.setData('text/plain', p.id);
            });

            marker.addEventListener('dblclick', (e) => {
                e.stopPropagation();
                if (confirm(`Hapus chord ${p.chord ? p.chord.name : ''}?`)) {
                    this.deletePlacement(p.id);
                }
            });

            chordTrack.appendChild(marker);
        }

        // Lyric character strip
        const lyricTrack = document.createElement('div');
        lyricTrack.className = 'simple-lyric-chars';

        const chars = content.length > 0 ? content.split('') : [' '];
        chars.forEach((char, index) => {
            const charSlot = document.createElement('span');
            charSlot.className = 'char-slot';
            charSlot.textContent = char === ' ' ? '\u00A0' : char;
            charSlot.dataset.index = index;

            charSlot.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                charSlot.classList.add('drag-over');
            });

            charSlot.addEventListener('dragleave', () => {
                charSlot.classList.remove('drag-over');
            });

            charSlot.addEventListener('drop', (e) => {
                e.preventDefault();
                charSlot.classList.remove('drag-over');
                const targetPosition = index;

                if (this.draggedChord) {
                    this.savePlacement({
                        lyric_line_id: line.id,
                        chord_id: this.draggedChord.id,
                        position: targetPosition,
                    });
                } else if (this.draggedPlacement) {
                    this.updatePlacement(this.draggedPlacement.id, {
                        position: targetPosition,
                    });
                }
            });

            lyricTrack.appendChild(charSlot);
        });

        const lineActions = document.createElement('div');
        lineActions.className = 'line-actions';
        lineActions.innerHTML = `
            <button class="btn btn-xs btn-outline-danger btn-delete-line" title="Hapus baris">×</button>
        `;
        lineActions.querySelector('.btn-delete-line').addEventListener('click', () => {
            this.deleteLine(line.id);
        });

        lineEl.appendChild(chordTrack);
        lineEl.appendChild(lyricTrack);
        lineEl.appendChild(lineActions);

        return lineEl;
    }

    /**
     * M4: Render Beat Mode with Auto-continuation (§5.2)
     */
    renderBeatMode(container) {
        container.innerHTML = '';

        if (!this.song.sections || this.song.sections.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>Belum ada section. Tambahkan section untuk menggunakan Beat Grid Editor.</p>
                </div>
            `;
            return;
        }

        const beatsPerBar = this.beatsPerBar;
        const displayStarts = this.calculateLineDisplayStarts();
        const sortedSections = [...this.song.sections].sort((a, b) => a.sequence - b.sequence);

        for (const section of sortedSections) {
            const sectionCard = document.createElement('div');
            sectionCard.className = 'section-card';
            sectionCard.innerHTML = `
                <div class="section-card-header">
                    <div class="section-title">
                        <span class="badge badge-primary">${this.escape(section.name)}</span>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-sm btn-outline-primary btn-add-line" data-section-id="${section.id}">+ Baris</button>
                    </div>
                </div>
                <div class="section-lines" id="section-beat-lines-${section.id}"></div>
            `;

            container.appendChild(sectionCard);

            const linesContainer = sectionCard.querySelector(`#section-beat-lines-${section.id}`);
            const sortedLines = [...(section.lyric_lines || [])].sort((a, b) => a.line_number - b.line_number);

            for (const line of sortedLines) {
                const displayStartBeat = displayStarts[line.id] ?? 0;
                const lineGridEl = this.createBeatLineElement(line, displayStartBeat);
                linesContainer.appendChild(lineGridEl);
            }
        }

        // Attach add line listeners
        container.querySelectorAll('.btn-add-line').forEach(btn => {
            btn.addEventListener('click', () => {
                const secId = parseInt(btn.dataset.sectionId, 10);
                this.showAddLineModal(secId);
            });
        });
    }

    createBeatLineElement(line, displayStartBeat) {
        const beatsPerBar = this.beatsPerBar;
        const lineEl = document.createElement('div');
        lineEl.className = 'beat-lyric-line-block';
        lineEl.dataset.lineId = line.id;

        // Header showing lyric content and local bar offset
        const lyricHeader = document.createElement('div');
        lyricHeader.className = 'beat-line-header';
        lyricHeader.innerHTML = `
            <span class="beat-lyric-text">${this.escape(line.content || '(Baris tanpa teks)')}</span>
            <span class="text-muted ml-auto" style="font-size: 0.8rem;">Global Beat Start: ${displayStartBeat}</span>
        `;
        lineEl.appendChild(lyricHeader);

        // Calculate how many bars to show for this line
        const placements = line.chord_placements || [];
        const validBeats = placements.map(p => p.start_beat).filter(b => b !== null && b !== undefined);

        let maxBeatInLine = validBeats.length > 0 ? Math.max(...validBeats) : (displayStartBeat + beatsPerBar - 1);
        let spanBeats = Math.max(beatsPerBar, (maxBeatInLine - displayStartBeat + 1));
        let numBars = Math.ceil(spanBeats / beatsPerBar) + 1; // Always show at least 1 extra bar for dropping next chords

        const gridWrapper = document.createElement('div');
        gridWrapper.className = 'beat-grid-wrapper';

        for (let barIdx = 0; barIdx < numBars; barIdx++) {
            const barBox = document.createElement('div');
            barBox.className = 'beat-bar-box';

            const barHeader = document.createElement('div');
            barHeader.className = 'beat-bar-header';
            barHeader.textContent = `Bar ${barIdx + 1}`;
            barBox.appendChild(barHeader);

            const beatsRow = document.createElement('div');
            beatsRow.className = 'beat-cells-row';

            for (let beatInBar = 0; beatInBar < beatsPerBar; beatInBar++) {
                const localBeatOffset = (barIdx * beatsPerBar) + beatInBar;
                const globalBeat = displayStartBeat + localBeatOffset;

                const cell = document.createElement('div');
                cell.className = 'beat-cell';
                cell.dataset.globalBeat = globalBeat;
                cell.dataset.localBeat = localBeatOffset;
                cell.title = `Ketukan ${beatInBar + 1} (Global Beat: ${globalBeat})`;

                const cellBeatNum = document.createElement('span');
                cellBeatNum.className = 'beat-cell-num';
                cellBeatNum.textContent = beatInBar + 1;
                cell.appendChild(cellBeatNum);

                // Find placement on this exact global beat
                const placed = placements.find(p => p.start_beat === globalBeat);
                if (placed) {
                    const badge = document.createElement('div');
                    badge.className = 'placed-beat-chord';
                    badge.draggable = true;
                    badge.textContent = placed.chord ? placed.chord.name : 'Chord';
                    badge.dataset.placementId = placed.id;

                    badge.addEventListener('dragstart', (e) => {
                        e.stopPropagation();
                        this.draggedPlacement = placed;
                        this.draggedChord = null;
                        e.dataTransfer.setData('text/plain', placed.id);
                    });

                    badge.addEventListener('dblclick', (e) => {
                        e.stopPropagation();
                        if (confirm(`Hapus chord ${placed.chord ? placed.chord.name : ''}?`)) {
                            this.deletePlacement(placed.id);
                        }
                    });

                    cell.appendChild(badge);
                    cell.classList.add('has-chord');
                }

                // Drop target logic
                cell.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'copy';
                    cell.classList.add('drag-over');
                });

                cell.addEventListener('dragleave', () => {
                    cell.classList.remove('drag-over');
                });

                cell.addEventListener('drop', (e) => {
                    e.preventDefault();
                    cell.classList.remove('drag-over');

                    if (this.draggedChord) {
                        this.savePlacement({
                            lyric_line_id: line.id,
                            chord_id: this.draggedChord.id,
                            start_beat: globalBeat,
                        });
                    } else if (this.draggedPlacement) {
                        this.updatePlacement(this.draggedPlacement.id, {
                            start_beat: globalBeat,
                        });
                    }
                });

                beatsRow.appendChild(cell);
            }

            barBox.appendChild(beatsRow);
            gridWrapper.appendChild(barBox);
        }

        lineEl.appendChild(gridWrapper);
        return lineEl;
    }

    /**
     * API: Save placement with 409 Conflict check and force override (§5.1)
     */
    async savePlacement(payload, isForce = false) {
        const body = { ...payload };
        if (isForce) {
            body.force = true;
        }

        try {
            const res = await fetch('/api/chord-placements', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(body),
            });

            if (res.status === 409) {
                const data = await res.json();
                const beatNum = data.colliding_placement ? data.colliding_placement.start_beat : '';
                const chordName = data.colliding_placement?.chord_name || 'lain';

                const confirmOverride = confirm(
                    `Tabrakan Chord! Sudah ada chord "${chordName}" pada ketukan ke-${beatNum}.\n\nApakah Anda ingin menimpa (overwrite) chord tersebut?`
                );

                if (confirmOverride) {
                    await this.savePlacement(payload, true);
                }
                return;
            }

            if (!res.ok) {
                const err = await res.json();
                this.showNotification(err.message || 'Gagal menyimpan placement', 'error');
                return;
            }

            this.showNotification('Chord berhasil ditempatkan', 'success');
            await this.loadSong();
            this.render();
        } catch (e) {
            this.showNotification('Terjadi kesalahan jaringan', 'error');
        }
    }

    async updatePlacement(placementId, payload, isForce = false) {
        const body = { ...payload };
        if (isForce) {
            body.force = true;
        }

        try {
            const res = await fetch(`/api/chord-placements/${placementId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(body),
            });

            if (res.status === 409) {
                const data = await res.json();
                const beatNum = data.colliding_placement ? data.colliding_placement.start_beat : '';
                const chordName = data.colliding_placement?.chord_name || 'lain';

                const confirmOverride = confirm(
                    `Tabrakan Chord! Sudah ada chord "${chordName}" pada ketukan ke-${beatNum}.\n\nApakah Anda ingin menimpa (overwrite) chord tersebut?`
                );

                if (confirmOverride) {
                    await this.updatePlacement(placementId, payload, true);
                }
                return;
            }

            if (!res.ok) {
                const err = await res.json();
                this.showNotification(err.message || 'Gagal memindahkan placement', 'error');
                return;
            }

            this.showNotification('Posisi chord diperbarui', 'success');
            await this.loadSong();
            this.render();
        } catch (e) {
            this.showNotification('Terjadi kesalahan jaringan', 'error');
        }
    }

    async deletePlacement(placementId) {
        const res = await fetch(`/api/chord-placements/${placementId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
            },
        });

        if (res.ok) {
            this.showNotification('Placement dihapus', 'info');
            await this.loadSong();
            this.render();
        }
    }

    // Modal & CRUD Section (M2)
    showAddSectionModal() {
        const name = prompt('Masukkan nama Section (contoh: Intro, Verse 1, Chorus):');
        if (!name || !name.trim()) return;

        fetch(`/api/songs/${this.songId}/sections`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            },
            body: JSON.stringify({ name: name.trim() }),
        }).then(async res => {
            if (res.ok) {
                this.showNotification('Section ditambahkan', 'success');
                await this.loadSong();
                this.render();
            }
        });
    }

    async deleteSection(sectionId) {
        if (!confirm('Hapus section ini beserta seluruh baris lirik dan chord di dalamnya?')) return;

        const res = await fetch(`/api/sections/${sectionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
            },
        });

        if (res.ok) {
            this.showNotification('Section dihapus', 'info');
            await this.loadSong();
            this.render();
        }
    }

    // Modal & CRUD Line (M2)
    showAddLineModal(sectionId) {
        const content = prompt('Masukkan isi baris lirik:');
        if (content === null) return;

        fetch(`/api/sections/${sectionId}/lines`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            },
            body: JSON.stringify({ content: content }),
        }).then(async res => {
            if (res.ok) {
                this.showNotification('Baris lirik ditambahkan', 'success');
                await this.loadSong();
                this.render();
            }
        });
    }

    async deleteLine(lineId) {
        if (!confirm('Hapus baris lirik ini?')) return;

        const res = await fetch(`/api/lines/${lineId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
            },
        });

        if (res.ok) {
            this.showNotification('Baris lirik dihapus', 'info');
            await this.loadSong();
            this.render();
        }
    }

    // Modal Custom Chord (M5)
    showCustomChordModal() {
        const name = prompt('Nama Chord Custom (misal: F#m7b5):');
        if (!name || !name.trim()) return;

        const pronunciation = prompt('Teks Pengucapan Suara TTS (misal: F sharp minor seven flat five):', name.trim());
        if (!pronunciation || !pronunciation.trim()) return;

        fetch('/api/chords', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            },
            body: JSON.stringify({
                name: name.trim(),
                pronunciation: pronunciation.trim(),
            }),
        }).then(async res => {
            if (res.ok) {
                this.showNotification('Chord custom berhasil ditambahkan', 'success');
                await this.loadChords();
            } else {
                const err = await res.json();
                this.showNotification(err.message || 'Gagal menambahkan chord', 'error');
            }
        });
    }

    // Backing Track Upload / Delete (M6)
    async uploadBackingTrack(file) {
        const formData = new FormData();
        formData.append('audio', file);

        this.showNotification('Mengunggah backing track...', 'info');

        try {
            const res = await fetch(`/api/songs/${this.songId}/audio`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: formData,
            });

            if (res.ok) {
                this.showNotification('Backing track berhasil diunggah', 'success');
                await this.loadSong();
                this.render();
            } else {
                const err = await res.json();
                this.showNotification(err.message || 'Gagal mengunggah audio', 'error');
            }
        } catch (e) {
            this.showNotification('Terjadi kesalahan saat upload', 'error');
        }
    }

    async deleteBackingTrack() {
        if (!confirm('Hapus backing track dari lagu ini?')) return;

        try {
            const res = await fetch(`/api/songs/${this.songId}/audio`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                },
            });

            if (res.ok) {
                this.showNotification('Backing track dihapus', 'info');
                await this.loadSong();
                this.render();
            }
        } catch (e) {
            this.showNotification('Gagal menghapus backing track', 'error');
        }
    }

    showNotification(msg, type = 'info') {
        const alertBox = document.getElementById('editor-notification');
        if (!alertBox) return;

        alertBox.textContent = msg;
        alertBox.className = `editor-alert alert-${type} show`;
        setTimeout(() => {
            alertBox.className = 'editor-alert';
        }, 3500);
    }

    escape(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }
}

window.ChordEditor = ChordEditor;
