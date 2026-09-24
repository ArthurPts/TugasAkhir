import sys
import asyncio
import edge_tts


async def main():
    if len(sys.argv) < 2:
        print("Text is required", file=sys.stderr)
        sys.exit(1)

    text = sys.argv[1]

    voice = sys.argv[2] if len(sys.argv) >= 3 else "en-US-AriaNeural"

    communicate = edge_tts.Communicate(
        text,
        voice
    )

    async for chunk in communicate.stream():
        if chunk["type"] == "audio":
            sys.stdout.buffer.write(chunk["data"])
            sys.stdout.buffer.flush()


if __name__ == "__main__":
    asyncio.run(main())