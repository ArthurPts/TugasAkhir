<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>TTS Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: sans-serif;
            max-width: 480px;
            margin: 40px auto;
        }

        select,
        button {
            font-size: 16px;
            padding: 8px;
            margin-top: 8px;
            width: 100%;
        }

        #status {
            margin-top: 12px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>

<body>
    <h3>Edge TTS Test</h3>

    <label for="chord">Pilih chord:</label>
    <select id="chord">
        @foreach ($chords as $chord)
            <option value="{{ $chord->root }} {{ $chord->typePronunciation->pronunciation ?? $chord->type }}">
                {{ $chord->name }}
            </option>
        @endforeach
    </select>

    <button id="play">🔊 Ucapkan</button>
    <div id="status"></div>

    <audio id="player" hidden></audio>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const btn = document.getElementById('play');
        const status = document.getElementById('status');
        const player = document.getElementById('player');

        btn.addEventListener('click', async () => {
            const text = document.getElementById('chord').value;
            btn.disabled = true;
            status.textContent = 'Generating audio...';

            try {
                const res = await fetch("{{ route('tts.speak') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        text
                    }),
                });

                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.stderr || err.error || 'Gagal generate audio');
                }
                
                const blob = await res.blob();
                const url = URL.createObjectURL(blob);

                player.src = url;
                player.onended = () => URL.revokeObjectURL(url);
                await player.play();

                status.textContent = 'Playing: ' + text;
            } catch (e) {
                status.textContent = 'Error: ' + e.message;
            } finally {
                btn.disabled = false;
            }
        });
    </script>
</body>

</html>
