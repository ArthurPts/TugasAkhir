<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Katalog Lagu') }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">Jelajahi dan latih lagu favorit Anda dengan audio cue real-time</p>
            </div>
            @auth
                <a href="{{ route('songs.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    {{ __('Buat Lagu Baru') }}
                </a>
            @endauth
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Filter Bar -->
            <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
                <form id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4" onsubmit="event.preventDefault(); applyFilter();">
                    <!-- Search Input -->
                    <div class="md:col-span-2">
                        <label for="searchInput" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Pencarian</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </span>
                            <input type="text" id="searchInput" name="search" placeholder="Cari judul lagu atau nama artis..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label for="categoryFilter" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Kategori</label>
                        <select id="categoryFilter" name="category" onchange="applyFilter()"
                            class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->name }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Artist Filter -->
                    <div>
                        <label for="artistFilter" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Artis</label>
                        <select id="artistFilter" name="artist_id" onchange="applyFilter()"
                            class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out">
                            <option value="">Semua Artis</option>
                            @foreach($artists as $artist)
                                <option value="{{ $artist->id }}">{{ $artist->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <!-- Loading State -->
            <div id="loadingIndicator" class="hidden py-16 text-center">
                <div class="inline-flex items-center gap-3 px-4 py-2 rounded-lg bg-white shadow-sm border border-gray-100 text-gray-600">
                    <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memuat lagu...</span>
                </div>
            </div>

            <!-- Songs Grid -->
            <div id="songsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>

            <!-- Empty State -->
            <div id="emptyState" class="hidden py-16 text-center bg-white rounded-xl shadow-sm border border-gray-100">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
                <h3 class="mt-2 text-base font-semibold text-gray-900">Tidak ada lagu ditemukan</h3>
                <p class="mt-1 text-sm text-gray-500">Coba ubah kata kunci pencarian atau reset filter kategori & artis.</p>
                <div class="mt-6">
                    <button type="button" onclick="resetFilter()" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- Pagination Controls -->
            <div id="paginationContainer" class="mt-8 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 rounded-xl shadow-sm">
                <div class="flex flex-1 justify-between sm:hidden">
                    <button id="btnPrevMobile" onclick="changePage(currentPage - 1)" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Sebelumnya</button>
                    <button id="btnNextMobile" onclick="changePage(currentPage + 1)" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Berikutnya</button>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Menampilkan <span id="pagStart" class="font-medium">0</span> sampai <span id="pagEnd" class="font-medium">0</span> dari <span id="pagTotal" class="font-medium">0</span> lagu
                        </p>
                    </div>
                    <div id="pageNumbers" class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                        <!-- Dynamic page buttons -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let currentPage = 1;
        let searchDebounceTimeout = null;

        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchDebounceTimeout);
            searchDebounceTimeout = setTimeout(() => {
                currentPage = 1;
                loadSongs();
            }, 300);
        });

        function applyFilter() {
            currentPage = 1;
            loadSongs();
        }

        function resetFilter() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('artistFilter').value = '';
            currentPage = 1;
            loadSongs();
        }

        function changePage(page) {
            if (page < 1) return;
            currentPage = page;
            loadSongs();
        }

        async function loadSongs() {
            const loading = document.getElementById('loadingIndicator');
            const grid = document.getElementById('songsGrid');
            const empty = document.getElementById('emptyState');
            const pagination = document.getElementById('paginationContainer');

            loading.classList.remove('hidden');
            grid.innerHTML = '';
            empty.classList.add('hidden');

            const search = document.getElementById('searchInput').value.trim();
            const category = document.getElementById('categoryFilter').value;
            const artistId = document.getElementById('artistFilter').value;

            try {
                const response = await ApiClient.get('/api/songs', {
                    page: currentPage,
                    search: search || null,
                    category: category || null,
                    artist_id: artistId || null,
                });

                loading.classList.add('hidden');

                const songs = response.data || [];
                const total = response.total || 0;

                if (songs.length === 0) {
                    empty.classList.remove('hidden');
                    pagination.classList.add('hidden');
                    return;
                }

                pagination.classList.remove('hidden');
                renderSongs(songs);
                renderPagination(response);
            } catch (err) {
                loading.classList.add('hidden');
                console.error('Gagal memuat katalog:', err);
                grid.innerHTML = `<div class="col-span-full p-4 bg-red-50 text-red-700 rounded-lg text-sm">${err.message || 'Gagal memuat katalog lagu.'}</div>`;
            }
        }

        function renderSongs(songs) {
            const grid = document.getElementById('songsGrid');
            grid.innerHTML = songs.map(song => {
                const artistName = song.artist?.name || 'Artis Tidak Diketahui';
                const timeSig = `${song.time_signature_numerator || 4}/${song.time_signature_denominator || 4}`;
                const key = song.default_key || 'C';
                const categories = (song.categories || []).map(c => `
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700">
                        ${c.name}
                    </span>
                `).join(' ');

                return `
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between hover:shadow-md transition-shadow duration-200">
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <h2 class="text-lg font-bold text-gray-900 line-clamp-1">${escapeHtml(song.title)}</h2>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-gray-100 text-gray-700 shrink-0">
                                    Key: ${escapeHtml(key)}
                                </span>
                            </div>
                            <p class="text-sm font-medium text-gray-600 mb-3">${escapeHtml(artistName)}</p>
                            
                            <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    ${song.bpm} BPM
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                                    ${timeSig}
                                </span>
                            </div>

                            ${categories ? `<div class="flex flex-wrap gap-1.5 mb-5">${categories}</div>` : ''}
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                            <a href="/songs/${song.id}/player" class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition duration-150">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                Buka Player
                            </a>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderPagination(meta) {
            document.getElementById('pagStart').textContent = meta.from || 0;
            document.getElementById('pagEnd').textContent = meta.to || 0;
            document.getElementById('pagTotal').textContent = meta.total || 0;

            const btnPrev = document.getElementById('btnPrevMobile');
            const btnNext = document.getElementById('btnNextMobile');
            if (btnPrev) btnPrev.disabled = !meta.prev_page_url;
            if (btnNext) btnNext.disabled = !meta.next_page_url;

            const pageNumbers = document.getElementById('pageNumbers');
            if (!pageNumbers) return;

            let html = '';
            const lastPage = meta.last_page || 1;

            if (meta.prev_page_url) {
                html += `<button onclick="changePage(${meta.current_page - 1})" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"><span class="sr-only">Previous</span>&lsaquo;</button>`;
            }

            for (let i = 1; i <= lastPage; i++) {
                if (i === meta.current_page) {
                    html += `<span class="relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">${i}</span>`;
                } else if (i === 1 || i === lastPage || (i >= meta.current_page - 1 && i <= meta.current_page + 1)) {
                    html += `<button onclick="changePage(${i})" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">${i}</button>`;
                } else if (i === meta.current_page - 2 || i === meta.current_page + 2) {
                    html += `<span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300">...</span>`;
                }
            }

            if (meta.next_page_url) {
                html += `<button onclick="changePage(${meta.current_page + 1})" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"><span class="sr-only">Next</span>&rsaquo;</button>`;
            }

            pageNumbers.innerHTML = html;
        }

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Initial Load
        document.addEventListener('DOMContentLoaded', () => {
            loadSongs();
        });
    </script>
    @endpush
</x-app-layout>
