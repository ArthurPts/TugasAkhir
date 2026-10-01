/**
 * beat-grid.js - Modul Kalkulasi Beat Grid & Auto-Continuation (§5.2)
 *
 * Digunakan untuk merender timeline bar & kolom beat lokal per baris lirik.
 * Mendukung browser (window.BeatGrid) dan Node.js (module.exports).
 */

(function (root, factory) {
    if (typeof exports === 'object' && typeof module !== 'undefined') {
        const exported = factory();
        module.exports = exported;
        module.exports.default = exported;
    } else if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        root.BeatGrid = factory();
    }
}(typeof globalThis !== 'undefined' ? globalThis : (typeof self !== 'undefined' ? self : this), function () {
    'use strict';

    /**
     * Mendapatkan jumlah beat per birama (numerator) dengan fallback default 4.
     * @param {number|string|null|undefined} numerator
     * @returns {number}
     */
    function getBeatsPerBar(numerator) {
        if (numerator === null || numerator === undefined || numerator === '') {
            return 4;
        }
        const parsed = parseInt(numerator, 10);
        return (!isNaN(parsed) && parsed > 0) ? parsed : 4;
    }

    /**
     * Menghitung displayStartBeat global untuk setiap baris lirik (Auto-continuation §5.2).
     *
     * Aturan:
     * - cumulativeBeat dimulai dari 0.
     * - Tiap baris mendapatkan displayStarts[line.id] = cumulativeBeat saat itu.
     * - Baris kosong (tanpa chord): mengalokasikan minimal 1 bar penuh (beatsPerBar).
     * - Baris dengan chord: mencari start_beat tertinggi yang dipakai, lalu dibulatkan ke awal bar berikutnya.
     *
     * @param {Array} sections Array section lagu
     * @param {number|string|null|undefined} numerator Nilai time signature numerator lagu
     * @returns {Object} Mapping { [lineId]: displayStartBeat }
     */
    function calculateLineDisplayStarts(sections, numerator) {
        const beatsPerBar = getBeatsPerBar(numerator);
        let cumulativeBeat = 0;
        const displayStarts = {};

        if (!sections || !Array.isArray(sections)) {
            return displayStarts;
        }

        const sortedSections = [...sections].sort((a, b) => (a.sequence || 0) - (b.sequence || 0));

        for (const section of sortedSections) {
            const lines = section.lyric_lines || section.lyricLines || [];
            const sortedLines = [...lines].sort((a, b) => (a.line_number || 0) - (b.line_number || 0));

            for (const line of sortedLines) {
                displayStarts[line.id] = cumulativeBeat;

                const placements = line.chord_placements || line.chordPlacements || [];
                const validBeats = placements
                    .map(p => p.start_beat)
                    .filter(b => b !== null && b !== undefined && !isNaN(b));

                let lineMaxBeatUsed;
                if (validBeats.length === 0) {
                    // Baris kosong mengambil 1 bar penuh
                    lineMaxBeatUsed = cumulativeBeat + beatsPerBar - 1;
                } else {
                    lineMaxBeatUsed = Math.max(...validBeats);
                }

                // Melanjutkan dari beat tertinggi dibulatkan ke awal bar berikutnya
                cumulativeBeat = Math.ceil((lineMaxBeatUsed + 1) / beatsPerBar) * beatsPerBar;
            }
        }

        return displayStarts;
    }

    /**
     * Menghitung berapa bar yang harus dirender untuk sebuah baris lirik.
     * @param {Object} line Objek baris lirik beserta chord_placements
     * @param {number} displayStartBeat Nilai displayStartBeat baris ini
     * @param {number|string|null|undefined} numerator
     * @returns {number} Jumlah bar (minimal 1)
     */
    function calculateLineBarCount(line, displayStartBeat, numerator) {
        const beatsPerBar = getBeatsPerBar(numerator);
        const placements = (line && (line.chord_placements || line.chordPlacements)) || [];

        let maxBeat = displayStartBeat + beatsPerBar - 1;
        for (const p of placements) {
            if (p.start_beat !== null && p.start_beat !== undefined && !isNaN(p.start_beat)) {
                if (p.start_beat > maxBeat) {
                    maxBeat = p.start_beat;
                }
            }
        }

        const totalBeats = maxBeat - displayStartBeat + 1;
        return Math.max(1, Math.ceil(totalBeats / beatsPerBar));
    }

    /**
     * Konversi dari koordinat lokal Beat Grid ke start_beat GLOBAL server.
     * globalBeat = displayStartBeat + (barIndex * beatsPerBar) + colIndex
     */
    function toGlobalBeat(displayStartBeat, barIndex, colIndex, numerator) {
        const beatsPerBar = getBeatsPerBar(numerator);
        return displayStartBeat + (barIndex * beatsPerBar) + colIndex;
    }

    /**
     * Konversi dari start_beat GLOBAL server ke indeks bar dan kolom lokal.
     */
    function toLocalGridCoord(globalBeat, displayStartBeat, numerator) {
        const beatsPerBar = getBeatsPerBar(numerator);
        const offset = globalBeat - displayStartBeat;
        if (offset < 0) return null;
        return {
            barIndex: Math.floor(offset / beatsPerBar),
            colIndex: offset % beatsPerBar,
        };
    }

    return {
        getBeatsPerBar,
        calculateLineDisplayStarts,
        calculateLineBarCount,
        toGlobalBeat,
        toLocalGridCoord,
    };
}));
