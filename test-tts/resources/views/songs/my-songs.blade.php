<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Lagu Saya') }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">Kelola repertoar, struktur lagu, backing track, dan chord placement Anda</p>
            </div>
            <div id="createSongAction">
                <a id="btnCreateSong" href="{{ route('songs.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    {{ __('Buat Lagu Baru') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Quota Status Banner -->
            <div id="quotaContainer" class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Status Kuota Unggah Lagu</h2>
                        <div class="flex items-baseline gap-2">
                            <span id="quotaCountText" class="text-2xl font-black text-gray-900">-</span>
                            <span id="quotaRoleText" class="text-xs font-semibold px-2 py-0.5 rounded bg-gray-100 text-gray-600">User</span>
                        </div>
                        <p id="quotaDescText" class="text-xs text-gray-500 mt-1">Memuat informasi kuota...</p>
                    </div>
                    <div class="w-full sm:w-64">
                        <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                            <div id="quotaProgressBar" class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div id="quotaWarningAlert" class="hidden mt-4 p-3 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span><strong>Batas Kuota Tercapai:</strong> Anda telah mencapai batas maksimal 10 lagu aktif. Hapus lagu lama untuk menambah slot lagu baru.</span>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="py-16 text-center">
                <div class="inline-flex items-center gap-3 px-4 py-2 rounded-lg bg-white shadow-sm border border-gray-100 text-gray-600">
                    <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memuat lagu Anda...</span>
                </div>
            </div>

            <!-- Songs List Table / Cards -->
            <div id="songsListContainer" class="hidden">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Lagu & Artis</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tempo & Key</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Visibilitas</th>
                                    <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="songsTableBody" class="bg-white divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="hidden py-16 text-center bg-white rounded-xl shadow-sm border border-gray-100">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
                <h3 class="mt-2 text-base font-semibold text-gray-900">Belum ada lagu yang dibuat</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai buat lagu pertama Anda untuk menambahkan chord dan backing track.</p>
                <div class="mt-6">
                    <a href="{{ route('songs.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        Buat Lagu Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 text-left">
            <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">Konfirmasi Hapus Lagu</h3>
            <p class="text-sm text-gray-600 mb-6">Apakah Anda yakin ingin menghapus lagu <strong id="deleteSongTitle"></strong>? Semua section, baris lirik, chord, dan audio referensi akan dihapus secara permanen.</p>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Batal
                </button>
                <button type="button" id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                    Hapus Permanen
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let songToDelete = null;

        async function initPage() {
            await Promise.all([loadQuota(), loadMySongs()]);
        }

        async function loadQuota() {
            try {
                const quota = await ApiClient.get('/api/me/quota');
                const isUnlimited = quota.quota === null;
                const used = quota.used_songs || 0;
                const max = quota.quota || 10;
                const remaining = isUnlimited ? Infinity : (quota.remaining_quota ?? (max - used));

                document.getElementById('quotaRoleText').textContent = (quota.role || 'user').toUpperCase();
                
                if (isUnlimited) {
                    document.getElementById('quotaCountText').textContent = `${used} Lagu Aktif (Unlimited)`;
                    document.getElementById('quotaDescText').textContent = 'Sebagai admin, Anda tidak dibatasi kuota upload lagu.';
                    document.getElementById('quotaProgressBar').style.width = '100%';
                    document.getElementById('quotaProgressBar').className = 'bg-emerald-500 h-2.5 rounded-full';
                } else {
                    document.getElementById('quotaCountText').textContent = `${used} / ${max} Lagu`;
                    document.getElementById('quotaDescText').textContent = `Tersisa ${remaining} slot upload lagu aktif.`;
                    const percent = Math.min(100, Math.round((used / max) * 100));
                    const bar = document.getElementById('quotaProgressBar');
                    bar.style.width = `${percent}%`;
                    
                    if (percent >= 100) {
                        bar.className = 'bg-red-500 h-2.5 rounded-full';
                        document.getElementById('quotaWarningAlert').classList.remove('hidden');
                        
                        // Disable Create Song button
                        const btnCreate = document.getElementById('btnCreateSong');
                        if (btnCreate) {
                            btnCreate.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
                            btnCreate.title = 'Kuota lagu habis. Hapus lagu lama untuk menambah slot.';
                        }
                    } else if (percent >= 80) {
                        bar.className = 'bg-amber-500 h-2.5 rounded-full';
                    } else {
                        bar.className = 'bg-indigo-600 h-2.5 rounded-full';
                    }
                }
            } catch (err) {
                console.error('Gagal memuat kuota:', err);
                document.getElementById('quotaDescText').textContent = 'Gagal memuat status kuota.';
            }
        }

        async function loadMySongs() {
            const loading = document.getElementById('loadingIndicator');
            const container = document.getElementById('songsListContainer');
            const tbody = document.getElementById('songsTableBody');
            const empty = document.getElementById('emptyState');

            loading.classList.remove('hidden');
            container.classList.add('hidden');
            empty.classList.add('hidden');
            tbody.innerHTML = '';

            try {
                const response = await ApiClient.get('/api/songs', { mine: 1, per_page: 100 });
                loading.classList.add('hidden');

                const songs = response.data || [];
                if (songs.length === 0) {
                    empty.classList.remove('hidden');
                    return;
                }

                container.classList.remove('hidden');
                tbody.innerHTML = songs.map(song => {
                    const artistName = song.artist?.name || 'Artis Tidak Diketahui';
                    const timeSig = `${song.time_signature_numerator || 4}/${song.time_signature_denominator || 4}`;
                    const key = song.default_key || '-';
                    const isPrivate = song.visibility === 'private';
                    const visBadge = isPrivate 
                        ? `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>Private</span>`
                        : `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Public</span>`;

                    return `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">${escapeHtml(song.title)}</div>
                                <div class="text-xs text-gray-500">${escapeHtml(artistName)}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                <div><span class="font-semibold">BPM:</span> ${song.bpm} | <span class="font-semibold">Birama:</span> ${timeSig}</div>
                                <div><span class="font-semibold">Key:</span> ${escapeHtml(key)}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                ${visBadge}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="/songs/${song.id}/player" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-md text-xs font-semibold transition">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                    Player
                                </a>
                                <a href="/songs/${song.id}/editor/simple" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-md text-xs font-semibold transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Edit
                                </a>
                                <button type="button" onclick="confirmDelete(${song.id}, '${escapeHtml(song.title.replace(/'/g, "\\'"))}')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-md text-xs font-semibold transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (err) {
                loading.classList.add('hidden');
                console.error('Gagal memuat daftar lagu:', err);
                empty.classList.remove('hidden');
                empty.querySelector('h3').textContent = 'Gagal memuat lagu';
                empty.querySelector('p').textContent = err.message || 'Terjadi kesalahan jaringan.';
            }
        }

        function confirmDelete(songId, songTitle) {
            songToDelete = songId;
            document.getElementById('deleteSongTitle').textContent = `"${songTitle}"`;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            songToDelete = null;
            document.getElementById('deleteModal').classList.add('hidden');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
            if (!songToDelete) return;
            const btn = document.getElementById('confirmDeleteBtn');
            btn.disabled = true;
            btn.textContent = 'Menghapus...';

            try {
                await ApiClient.delete(`/api/songs/${songToDelete}`);
                closeDeleteModal();
                await initPage();
            } catch (err) {
                alert(err.message || 'Gagal menghapus lagu.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Hapus Permanen';
            }
        });

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', initPage);
    </script>
    @endpush
</x-app-layout>
