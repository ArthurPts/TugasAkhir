<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('me.songs') }}" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-bold text-2xl text-gray-900 leading-tight">
                            {{ $song->title }}
                        </h1>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded {{ $song->visibility === 'private' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                            {{ ucfirst($song->visibility) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $song->artist?->name ?? 'Artis Tidak Diketahui' }} &bull; {{ $song->bpm }} BPM &bull; Birama {{ $song->time_signature_numerator ?? 4 }}/{{ $song->time_signature_denominator ?? 4 }} &bull; Key: {{ $song->default_key ?? 'C' }}
                    </p>
                </div>
            </div>

            <!-- Navigation to Chord Editors & Player -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('songs.editor.simple', $song) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 rounded-lg text-xs font-bold transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span>Editor Sederhana</span>
                </a>
                <a href="{{ route('songs.editor.beat', $song) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 rounded-lg text-xs font-bold transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    <span>Editor Mode Beat</span>
                </a>
                <a href="{{ route('songs.player', $song) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-900 text-white hover:bg-gray-800 rounded-lg text-xs font-bold transition shadow-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                    <span>Buka Player</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Alert Banner -->
            <div id="statusAlert" class="hidden p-4 rounded-xl text-sm font-medium"></div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                <!-- Left 2 Cols: Struktur Lagu (Sections & Lines) -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Struktur Bagian & Lirik</h2>
                                <p class="text-xs text-gray-500 mt-0.5">Kelola section lagu (Verse, Chorus, dll.) dan baris lirik di bawahnya</p>
                            </div>
                            <button type="button" onclick="openAddSectionModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                <span>Tambah Section</span>
                            </button>
                        </div>

                        <!-- Sections Container -->
                        <div id="sectionsContainer" class="mt-6 space-y-6">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>

                <!-- Right 1 Col: Backing Track (M6) & Metadata Quick Edit -->
                <div class="space-y-6">
                    <!-- Backing Track Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 rounded-lg bg-indigo-50 text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Audio Referensi (Backing Track)</h2>
                                <span class="text-xs text-gray-500">M6 Backing Track Upload</span>
                            </div>
                        </div>

                        <!-- §5.4 Mandatory Instruction -->
                        <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-900 leading-relaxed mb-4">
                            <strong>Instruksi Sinkronisasi:</strong> Pastikan file dimulai tepat di ketukan pertama, tanpa jeda/intro tambahan — sistem akan otomatis memberi hitungan mundur 1 birama sebelum lagu dan audio referensi mulai bersamaan.
                        </div>

                        <!-- Upload limits info -->
                        <p class="text-xs text-gray-500 mb-4">
                            Format didukung: <strong>{{ $allowedAudioFormats }}</strong> &bull; Ukuran maksimal: <strong>{{ $maxAudioSizeMb }} MB</strong>.
                        </p>

                        <!-- Audio player or Upload form -->
                        <div id="audioSectionContent">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- Metadata Edit Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-base font-bold text-gray-900 mb-4">Ubah Metadata Lagu</h2>
                        <form id="editMetadataForm" onsubmit="event.preventDefault(); updateMetadata();" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Judul Lagu</label>
                                <input type="text" name="title" value="{{ $song->title }}" required maxlength="75"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Tempo (BPM)</label>
                                    <input type="number" name="bpm" value="{{ $song->bpm }}" required min="20" max="300"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Default Key</label>
                                    <input type="text" name="default_key" value="{{ $song->default_key }}" maxlength="10"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Time Signature</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="number" name="time_signature_numerator" value="{{ $song->time_signature_numerator ?? 4 }}" min="1" max="32"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <input type="number" name="time_signature_denominator" value="{{ $song->time_signature_denominator ?? 4 }}" min="1" max="32"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Format umum: 4/4, 3/4, 6/8. Numerator dipakai untuk hitungan birama dan count-in.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Visibilitas</label>
                                <select name="visibility" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="public" {{ $song->visibility === 'public' ? 'selected' : '' }}>Public (Semua Pengguna)</option>
                                    <option value="private" {{ $song->visibility === 'private' ? 'selected' : '' }}>Private (Hanya Saya & Admin)</option>
                                </select>
                            </div>

                            <button type="submit" id="btnSaveMetadata" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                                Simpan Perubahan Metadata
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit Section -->
    <div id="sectionModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 text-left">
            <h3 id="sectionModalTitle" class="text-lg font-bold text-gray-900 mb-4">Tambah Section Baru</h3>
            <form id="sectionForm" onsubmit="event.preventDefault(); submitSectionForm();">
                <input type="hidden" id="sectionEditId" value="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nama Section <span class="text-red-500">*</span></label>
                        <input type="text" id="sectionNameInput" required maxlength="45" placeholder="Contoh: Verse 1, Chorus, Intro, Bridge"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeSectionModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="submit" id="btnSubmitSection" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold">Simpan Section</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah/Edit Lyric Line -->
    <div id="lineModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 text-left">
            <h3 id="lineModalTitle" class="text-lg font-bold text-gray-900 mb-4">Tambah Baris Lirik</h3>
            <form id="lineForm" onsubmit="event.preventDefault(); submitLineForm();">
                <input type="hidden" id="lineSectionId" value="">
                <input type="hidden" id="lineEditId" value="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Teks Lirik <span class="text-red-500">*</span></label>
                        <input type="text" id="lineContentInput" required placeholder="Ketik teks baris lirik di sini..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeLineModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="submit" id="btnSubmitLine" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold">Simpan Baris</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        const songId = {{ $song->id }};
        let currentSong = null;

        async function initEditPage() {
            await loadSongData();
        }

        async function loadSongData() {
            try {
                currentSong = await ApiClient.get(`/api/songs/${songId}`);
                renderSections();
                renderAudioSection();
            } catch (err) {
                showAlert('Gagal memuat data lagu: ' + err.message, 'error');
            }
        }

        function showAlert(message, type = 'success') {
            const el = document.getElementById('statusAlert');
            el.className = `p-4 rounded-xl text-sm font-medium ${type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'}`;
            el.textContent = message;
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 4000);
        }

        function renderSections() {
            const container = document.getElementById('sectionsContainer');
            const sections = currentSong.sections || [];

            if (sections.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                        <p class="text-sm text-gray-500">Belum ada section. Klik "Tambah Section" di atas untuk menambahkan bagian (Verse, Chorus, dsb.).</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = sections.map((sec, secIdx) => {
                const lines = sec.lyric_lines || sec.lyricLines || [];
                return `
                    <div class="border border-gray-200 rounded-xl overflow-hidden bg-gray-50/50">
                        <!-- Section Header -->
                        <div class="bg-gray-100 px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-700">Urutan ${sec.sequence}</span>
                                <h3 class="font-bold text-sm text-gray-900">${escapeHtml(sec.name)}</h3>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="openEditSectionModal(${sec.id}, '${escapeHtml(sec.name.replace(/'/g, "\\'"))}')" class="p-1.5 text-gray-500 hover:text-gray-700 rounded hover:bg-white" title="Ubah Nama Section">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button type="button" onclick="deleteSection(${sec.id})" class="p-1.5 text-red-500 hover:text-red-700 rounded hover:bg-white" title="Hapus Section">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Lines List -->
                        <div class="p-4 space-y-2">
                            ${lines.length === 0 ? `<p class="text-xs text-gray-400 italic py-2">Belum ada baris lirik pada section ini.</p>` : ''}
                            ${lines.map(line => `
                                <div class="bg-white p-3 rounded-lg border border-gray-200 flex items-center justify-between gap-3 shadow-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-semibold text-gray-400">#${line.line_number}</span>
                                        <span class="font-mono text-sm text-gray-800">${escapeHtml(line.content)}</span>
                                        <span class="text-xs text-indigo-500 font-medium">(${(line.chord_placements || line.chordPlacements || []).length} chord)</span>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" onclick="openEditLineModal(${line.id}, '${escapeHtml(line.content.replace(/'/g, "\\'"))}')" class="p-1 text-gray-400 hover:text-gray-600 rounded">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        <button type="button" onclick="deleteLine(${line.id})" class="p-1 text-red-400 hover:text-red-600 rounded">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            `).join('')}

                            <div class="pt-2">
                                <button type="button" onclick="openAddLineModal(${sec.id})" class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-semibold p-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    <span>+ Tambah Baris Lirik</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderAudioSection() {
            const container = document.getElementById('audioSectionContent');
            const audioUrl = currentSong.audio_url || currentSong.reference_audio_url;

            if (audioUrl) {
                container.innerHTML = `
                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                                Audio Referensi Aktif
                            </span>
                            <button type="button" onclick="deleteAudio()" class="text-xs text-red-600 hover:text-red-800 font-semibold">
                                Hapus Audio
                            </button>
                        </div>
                        <audio controls class="w-full" src="${audioUrl}"></audio>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <form id="uploadAudioForm" onsubmit="event.preventDefault(); uploadAudio();" class="space-y-3">
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-7 h-7 mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                    <p class="text-xs text-gray-600"><span class="font-semibold">Pilih file audio</span> untuk diunggah</p>
                                    <p class="text-2xs text-gray-400 mt-1">MP3, WAV, atau OGG (Maks 15MB)</p>
                                </div>
                                <input type="file" id="audioFileInput" name="audio" accept=".mp3,.wav,.ogg" required class="hidden" onchange="handleFileChosen(this)">
                            </label>
                        </div>
                        <div id="chosenFileName" class="hidden text-xs text-gray-700 font-medium"></div>
                        <button type="submit" id="btnUploadAudio" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                            Unggah Audio Referensi
                        </button>
                    </form>
                `;
            }
        }

        function handleFileChosen(input) {
            const label = document.getElementById('chosenFileName');
            if (input.files && input.files[0]) {
                label.textContent = `File dipilih: ${input.files[0].name} (${(input.files[0].size / 1024 / 1024).toFixed(2)} MB)`;
                label.classList.remove('hidden');
            }
        }

        async function uploadAudio() {
            const input = document.getElementById('audioFileInput');
            if (!input.files || !input.files[0]) return;

            const btn = document.getElementById('btnUploadAudio');
            btn.disabled = true;
            btn.textContent = 'Mengunggah...';

            const formData = new FormData();
            formData.append('audio', input.files[0]);

            try {
                await ApiClient.post(`/api/songs/${songId}/audio`, formData);
                showAlert('Audio referensi berhasil diunggah!');
                await loadSongData();
            } catch (err) {
                showAlert(err.message || 'Gagal mengunggah audio.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Unggah Audio Referensi';
            }
        }

        async function deleteAudio() {
            if (!confirm('Apakah Anda yakin ingin menghapus audio referensi backing track ini?')) return;
            try {
                await ApiClient.delete(`/api/songs/${songId}/audio`);
                showAlert('Audio referensi berhasil dihapus.');
                await loadSongData();
            } catch (err) {
                showAlert(err.message || 'Gagal menghapus audio.', 'error');
            }
        }

        async function updateMetadata() {
            const form = document.getElementById('editMetadataForm');
            const btn = document.getElementById('btnSaveMetadata');
            btn.disabled = true;

            const payload = {
                title: form.querySelector('[name="title"]').value.trim(),
                bpm: parseInt(form.querySelector('[name="bpm"]').value, 10),
                default_key: form.querySelector('[name="default_key"]').value.trim(),
                time_signature_numerator: parseInt(form.querySelector('[name="time_signature_numerator"]').value, 10) || null,
                time_signature_denominator: parseInt(form.querySelector('[name="time_signature_denominator"]').value, 10) || null,
                visibility: form.querySelector('[name="visibility"]').value,
            };

            try {
                await ApiClient.put(`/api/songs/${songId}`, payload);
                showAlert('Metadata lagu berhasil diperbarui.');
                await loadSongData();
            } catch (err) {
                showAlert(err.message || 'Gagal memperbarui metadata.', 'error');
            } finally {
                btn.disabled = false;
            }
        }

        // Section Modals & Actions
        function openAddSectionModal() {
            document.getElementById('sectionEditId').value = '';
            document.getElementById('sectionNameInput').value = '';
            document.getElementById('sectionModalTitle').textContent = 'Tambah Section Baru';
            document.getElementById('sectionModal').classList.remove('hidden');
        }

        function openEditSectionModal(id, name) {
            document.getElementById('sectionEditId').value = id;
            document.getElementById('sectionNameInput').value = name;
            document.getElementById('sectionModalTitle').textContent = 'Ubah Nama Section';
            document.getElementById('sectionModal').classList.remove('hidden');
        }

        function closeSectionModal() {
            document.getElementById('sectionModal').classList.add('hidden');
        }

        async function submitSectionForm() {
            const id = document.getElementById('sectionEditId').value;
            const name = document.getElementById('sectionNameInput').value.trim();
            const btn = document.getElementById('btnSubmitSection');

            btn.disabled = true;
            try {
                if (id) {
                    await ApiClient.patch(`/api/sections/${id}`, { name });
                } else {
                    await ApiClient.post(`/api/songs/${songId}/sections`, { name });
                }
                closeSectionModal();
                showAlert('Section berhasil disimpan.');
                await loadSongData();
            } catch (err) {
                alert(err.message || 'Gagal menyimpan section.');
            } finally {
                btn.disabled = false;
            }
        }

        async function deleteSection(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus section ini beserta semua baris lirik di dalamnya?')) return;
            try {
                await ApiClient.delete(`/api/sections/${id}`);
                showAlert('Section berhasil dihapus.');
                await loadSongData();
            } catch (err) {
                alert(err.message || 'Gagal menghapus section.');
            }
        }

        // Lyric Line Modals & Actions
        function openAddLineModal(sectionId) {
            document.getElementById('lineSectionId').value = sectionId;
            document.getElementById('lineEditId').value = '';
            document.getElementById('lineContentInput').value = '';
            document.getElementById('lineModalTitle').textContent = 'Tambah Baris Lirik';
            document.getElementById('lineModal').classList.remove('hidden');
        }

        function openEditLineModal(lineId, content) {
            document.getElementById('lineSectionId').value = '';
            document.getElementById('lineEditId').value = lineId;
            document.getElementById('lineContentInput').value = content;
            document.getElementById('lineModalTitle').textContent = 'Ubah Baris Lirik';
            document.getElementById('lineModal').classList.remove('hidden');
        }

        function closeLineModal() {
            document.getElementById('lineModal').classList.add('hidden');
        }

        async function submitLineForm() {
            const sectionId = document.getElementById('lineSectionId').value;
            const lineId = document.getElementById('lineEditId').value;
            const content = document.getElementById('lineContentInput').value.trim();
            const btn = document.getElementById('btnSubmitLine');

            btn.disabled = true;
            try {
                if (lineId) {
                    await ApiClient.patch(`/api/lines/${lineId}`, { content });
                } else {
                    await ApiClient.post(`/api/sections/${sectionId}/lines`, { content });
                }
                closeLineModal();
                showAlert('Baris lirik berhasil disimpan.');
                await loadSongData();
            } catch (err) {
                alert(err.message || 'Gagal menyimpan baris lirik.');
            } finally {
                btn.disabled = false;
            }
        }

        async function deleteLine(id) {
            if (!confirm('Hapus baris lirik ini? Chord yang terpasang pada baris ini juga akan terhapus.')) return;
            try {
                await ApiClient.delete(`/api/lines/${id}`);
                showAlert('Baris lirik berhasil dihapus.');
                await loadSongData();
            } catch (err) {
                alert(err.message || 'Gagal menghapus baris lirik.');
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', initEditPage);
    </script>
    @endpush
</x-app-layout>
