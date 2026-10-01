/**
 * Unit Test untuk public/js/beat-grid.js (ES Module)
 * Dijalankan dengan: node tests/js/beat-grid.test.js
 */

import assert from 'assert';
import { createRequire } from 'module';
const require = createRequire(import.meta.url);
require('../../public/js/beat-grid.js');
const BeatGrid = globalThis.BeatGrid;

console.log('--- Running BeatGrid Unit Tests ---');

// Test 1: getBeatsPerBar fallback
{
    assert.strictEqual(BeatGrid.getBeatsPerBar(4), 4, '4 should return 4');
    assert.strictEqual(BeatGrid.getBeatsPerBar(3), 3, '3 should return 3');
    assert.strictEqual(BeatGrid.getBeatsPerBar(null), 4, 'null should fallback to 4');
    assert.strictEqual(BeatGrid.getBeatsPerBar(undefined), 4, 'undefined should fallback to 4');
    assert.strictEqual(BeatGrid.getBeatsPerBar(''), 4, 'empty string should fallback to 4');
    assert.strictEqual(BeatGrid.getBeatsPerBar('6'), 6, 'string "6" should return 6');
    console.log('✔ Test 1: getBeatsPerBar fallback (4/4, 3/4, null -> 4) PASSED');
}

// Test 2: Baris kosong dalam birama 4/4
{
    const sections = [
        {
            sequence: 1,
            lyric_lines: [
                { id: 101, line_number: 1, chord_placements: [] },
                { id: 102, line_number: 2, chord_placements: [] },
                { id: 103, line_number: 3, chord_placements: [] },
            ]
        }
    ];

    const starts = BeatGrid.calculateLineDisplayStarts(sections, 4);
    assert.strictEqual(starts[101], 0, 'Line 1 begins at cumulative beat 0');
    assert.strictEqual(starts[102], 4, 'Line 2 continues after 1 bar of 4 beats (beat 4)');
    assert.strictEqual(starts[103], 8, 'Line 3 continues after line 2 (beat 8)');
    console.log('✔ Test 2: Empty lines in 4/4 auto-continuation PASSED');
}

// Test 3: Baris dalam birama 3/4
{
    const sections = [
        {
            sequence: 1,
            lyric_lines: [
                { id: 201, line_number: 1, chord_placements: [{ start_beat: 1 }] },
                { id: 202, line_number: 2, chord_placements: [{ start_beat: 4 }] },
            ]
        }
    ];

    const starts = BeatGrid.calculateLineDisplayStarts(sections, 3);
    assert.strictEqual(starts[201], 0, 'Line 1 starts at 0');
    // Line 1 uses beat 1, maxBeatUsed = 1. Ceil((1+1)/3)*3 = 3.
    assert.strictEqual(starts[202], 3, 'Line 2 starts at bar boundary beat 3 in 3/4');
    console.log('✔ Test 3: 3/4 time signature auto-continuation PASSED');
}

// Test 4: Numerator null fallback to 4 di auto-continuation
{
    const sections = [
        {
            sequence: 1,
            lyric_lines: [
                { id: 301, line_number: 1, chord_placements: [] },
                { id: 302, line_number: 2, chord_placements: [] },
            ]
        }
    ];

    const starts = BeatGrid.calculateLineDisplayStarts(sections, null);
    assert.strictEqual(starts[301], 0, 'Line 1 starts at 0');
    assert.strictEqual(starts[302], 4, 'Line 2 starts at 4 (defaulting to 4/4)');
    console.log('✔ Test 4: Numerator null fallback in auto-continuation PASSED');
}

// Test 5: Baris dengan chord di beat > 1 bar
{
    // Misal di 4/4, baris 1 menaruh chord di beat 9 (bar ke-3)
    const sections = [
        {
            sequence: 1,
            lyric_lines: [
                {
                    id: 401,
                    line_number: 1,
                    chord_placements: [
                        { start_beat: 0 },
                        { start_beat: 9 }, // Beat 9 is in bar 3 (beats 8, 9, 10, 11)
                    ]
                },
                {
                    id: 402,
                    line_number: 2,
                    chord_placements: []
                }
            ]
        }
    ];

    const starts = BeatGrid.calculateLineDisplayStarts(sections, 4);
    assert.strictEqual(starts[401], 0, 'Line 1 starts at 0');
    // Line 1 uses beat 9. Next bar boundary: ceil((9 + 1) / 4) * 4 = ceil(2.5) * 4 = 3 * 4 = 12.
    assert.strictEqual(starts[402], 12, 'Line 2 starts at beat 12 because line 1 reached beat 9');

    // Cek juga calculateLineBarCount
    const barCount = BeatGrid.calculateLineBarCount(sections[0].lyric_lines[0], 0, 4);
    assert.strictEqual(barCount, 3, 'Line 1 spans 3 bars (0-11)');
    console.log('✔ Test 5: Chord spanning > 1 bar correctly advances boundary PASSED');
}

// Test 6: Koordinat global <-> grid lokal
{
    // Global beat 10, displayStart 4, 4/4
    // Offset = 6 => barIndex 1 (bar ke-2), colIndex 2 (beat ke-3)
    const local = BeatGrid.toLocalGridCoord(10, 4, 4);
    assert.strictEqual(local.barIndex, 1);
    assert.strictEqual(local.colIndex, 2);

    const global = BeatGrid.toGlobalBeat(4, 1, 2, 4);
    assert.strictEqual(global, 10);
    console.log('✔ Test 6: Local and global beat conversion PASSED');
}

console.log('--- ALL BEATGRID TESTS PASSED! ---');
