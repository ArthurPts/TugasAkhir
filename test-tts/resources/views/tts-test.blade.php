<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>TTS Test</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 60px auto;
            padding: 20px;
        }

        h1 {
            margin-bottom: 30px;
        }

        select,
        button {
            padding: 10px;
            margin-top: 10px;
            width: 100%;
        }

        button {
            cursor: pointer;
        }

        #status {
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <h1>TTS Test</h1>

    <label>
        Chord
    </label>

    <select id="chord">
        <option value="C major">C Major</option>
        <option value="D major">D Major</option>
        <option value="E major">E Major</option>
        <option value="F major">F Major</option>
        <option value="G major">G Major</option>
        <option value="A minor">A Minor</option>
        <option value="B minor">B Minor</option>
    </select>

    <button onclick="playChord()">
        ▶ Play
    </button>

    <div id="status"></div>

    <script>
        async function playChord() {

            const chord = document.getElementById('chord').value;
            const status = document.getElementById('status');

            status.textContent = 'Generating audio...';

            try {

                const response = await fetch('/tts-test/speak', {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },

                    body: JSON.stringify({
                        text: chord,
                        voice: 'en-US-AriaNeural'
                    })
                });

                if (!response.ok) {
                    const error = await response.text();

                    console.error(error);

                    status.textContent = 'TTS failed';

                    return;
                }

                const blob = await response.blob();

                const audioUrl = URL.createObjectURL(blob);

                const audio = new Audio(audioUrl);

                audio.onended = () => {
                    URL.revokeObjectURL(audioUrl);
                    status.textContent = 'Finished';
                };

                await audio.play();

                status.textContent = 'Playing...';

            } catch (error) {

                console.error(error);

                status.textContent = 'Error: ' + error.message;
            }
        }
    </script>

</body>
</html>