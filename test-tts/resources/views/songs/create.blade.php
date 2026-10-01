<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('me.songs') }}" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h1 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Buat Lagu Baru') }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">Langkah 1: Masukkan informasi metadata lagu sebelum menyusun bagian lirik dan chord</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden p-6 sm:p-8">
                <form id="createSongForm" onsubmit="event.preventDefault(); submitCreateSong();">
                    <div id="generalErrorAlert" class="hidden mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700"></div>

                    <div class="space-y-6">
                        <!-- Judul Lagu -->
                        <div>
                            <label for="title" class="block text-sm font-semibold text-gray-700 mb-1">
                                Judul Lagu <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="title" name="title" required maxlength="75"
                                placeholder="Contoh: Pelangi di Matamu"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Artis -->
                        <div>
                            <label for="artist_id" class="block text-sm font-semibold text-gray-700 mb-1">
                                Artis / Band <span class="text-red-500">*</span>
                            </label>
                            <select id="artist_id" name="artist_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Pilih Artis --</option>
                                @foreach($artists as $artist)
                                    <option value="{{ $artist->id }}">{{ $artist->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Pilih artis dari daftar repertoar yang terdaftar di sistem.</p>
                        </div>

                        <!-- BPM & Nada Dasar -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="bpm" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Tempo (BPM) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="bpm" name="bpm" required min="20" max="300" value="120"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <p class="text-xs text-gray-500 mt-1">Rentang 20 - 300 BPM (contoh: 120)</p>
                            </div>

                            <div>
                                <label for="default_key" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Nada Dasar (Default Key)
                                </label>
                                <select id="default_key" name="default_key"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="C">C</option>
                                    <option value="C#">C# / Db</option>
                                    <option value="D">D</option>
                                    <option value="D#">D# / Eb</option>
                                    <option value="E">E</option>
                                    <option value="F">F</option>
                                    <option value="F#">F# / Gb</option>
                                    <option value="G">G</option>
                                    <option value="G#">G# / Ab</option>
                                    <option value="A">A</option>
                                    <option value="A#">A# / Bb</option>
                                    <option value="B">B</option>
                                </select>
                            </div>
                        </div>

                        <!-- Time Signature -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="time_signature_numerator" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Ketukan per Birama (Numerator)
                                </label>
                                <input type="number" id="time_signature_numerator" name="time_signature_numerator" min="1" max="16" placeholder="4"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <p class="text-xs text-indigo-600 mt-1">Boleh dikosongkan (default: 4 ketukan)</p>
                            </div>

                            <div>
                                <label for="time_signature_denominator" class="block text-sm font-semibold text-gray-700 mb-1">
                                    Nilai Not per Ketukan (Denominator)
                                </label>
                                <input type="number" id="time_signature_denominator" name="time_signature_denominator" min="1" max="16" placeholder="4"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <p class="text-xs text-indigo-600 mt-1">Boleh dikosongkan (default: 4)</p>
                            </div>
                        </div>

                        <!-- Visibility -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Visibilitas <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                    <input type="radio" name="visibility" value="public" checked class="text-indigo-600 focus:ring-indigo-500">
                                    <div class="ml-3">
                                        <span class="block text-sm font-semibold text-gray-900">Publik</span>
                                        <span class="block text-xs text-gray-500">Dapat dilihat dan dimainkan oleh siapa saja di Katalog</span>
                                    </div>
                                </label>
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                    <input type="radio" name="visibility" value="private" class="text-indigo-600 focus:ring-indigo-500">
                                    <div class="ml-3">
                                        <span class="block text-sm font-semibold text-gray-900">Privat</span>
                                        <span class="block text-xs text-gray-500">Hanya Anda dan admin yang dapat mengakses lagu ini</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-100 flex items-center justify-end gap-3">
                        <a href="{{ route('me.songs') }}" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </a>
                        <button type="submit" id="btnSubmit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                            <span>Lanjut ke Editor Struktur &rarr;</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        async function submitCreateSong() {
            const form = document.getElementById('createSongForm');
            const alertBox = document.getElementById('generalErrorAlert');
            const btn = document.getElementById('btnSubmit');

            alertBox.classList.add('hidden');
            alertBox.textContent = '';

            const numVal = form.querySelector('[name="time_signature_numerator"]').value;
            const denVal = form.querySelector('[name="time_signature_denominator"]').value;

            const payload = {
                title: form.querySelector('[name="title"]').value.trim(),
                artist_id: parseInt(form.querySelector('[name="artist_id"]').value, 10),
                bpm: parseInt(form.querySelector('[name="bpm"]').value, 10),
                default_key: form.querySelector('[name="default_key"]').value || 'C',
                time_signature_numerator: numVal ? parseInt(numVal, 10) : null,
                time_signature_denominator: denVal ? parseInt(denVal, 10) : null,
                visibility: form.querySelector('[name="visibility"]:checked').value,
            };

            btn.disabled = true;
            btn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyimpan...`;

            try {
                const createdSong = await ApiClient.post('/api/songs', payload);
                window.location.href = `/songs/${createdSong.id}/editor/simple`;
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = `<span>Lanjut ke Editor Struktur &rarr;</span>`;

                if (err instanceof ValidationError) {
                    ApiClient.displayValidationErrors(form, err.errors);
                } else {
                    alertBox.textContent = err.message || 'Gagal membuat lagu.';
                    alertBox.classList.remove('hidden');
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
