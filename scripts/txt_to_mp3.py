#!/usr/bin/env python3
"""Convert lesson narration .txt files to .mp3 using Microsoft Edge's free
neural text-to-speech voices (edge-tts, pip package `edge-tts`).

Usage:
    # single file -> sibling .mp3 next to it
    python3 scripts/txt_to_mp3.py data/stat/statisztika-kurzus/pages/1a-esemenyek-es-valoszinusegek/oldal.txt

    # explicit output path
    python3 scripts/txt_to_mp3.py oldal.txt -o oldal.mp3

    # batch mode: converts every *.txt under a raw lesson folder that has
    # no matching .mp3 yet (or is newer than its .mp3), writing the .mp3
    # next to each .txt file
    python3 scripts/txt_to_mp3.py data/stat/statisztika-kurzus

    # pick a different voice / force re-conversion of everything
    python3 scripts/txt_to_mp3.py data/stat/statisztika-kurzus --voice hu-HU-TamasNeural --force

List available Hungarian voices with:
    edge-tts --list-voices | grep hu-HU
"""

from __future__ import annotations

import argparse
import asyncio
import sys
from pathlib import Path

import edge_tts

DEFAULT_VOICE = "hu-HU-NoemiNeural"

# Microsoft's edge-tts websocket endpoint occasionally rejects the handshake
# (HTTP 403) under normal, transient conditions; a short retry clears it up.
MAX_ATTEMPTS = 3
RETRY_DELAY_SECONDS = 2


async def convert_file(txt_path: Path, mp3_path: Path, voice: str, rate: str, pitch: str) -> None:
    text = txt_path.read_text(encoding="utf-8").strip()

    if not text:
        print(f"  skip (üres szöveg): {txt_path}")
        return

    mp3_path.parent.mkdir(parents=True, exist_ok=True)

    for attempt in range(1, MAX_ATTEMPTS + 1):
        try:
            communicate = edge_tts.Communicate(text, voice, rate=rate, pitch=pitch)
            await communicate.save(str(mp3_path))
            break
        except Exception as exc:  # noqa: BLE001 - retry on any transient edge-tts/network failure
            if attempt == MAX_ATTEMPTS:
                raise
            print(f"  hiba ({attempt}/{MAX_ATTEMPTS}), újrapróbálás: {exc}")
            await asyncio.sleep(RETRY_DELAY_SECONDS * attempt)

    print(f"  kész: {mp3_path}")


def needs_conversion(txt_path: Path, mp3_path: Path, force: bool) -> bool:
    if force or not mp3_path.exists():
        return True

    return txt_path.stat().st_mtime > mp3_path.stat().st_mtime


async def run(args: argparse.Namespace) -> None:
    source = Path(args.input)

    if not source.exists():
        sys.exit(f"Nem létezik: {source}")

    if source.is_file():
        output = Path(args.output) if args.output else source.with_suffix(".mp3")

        if not needs_conversion(source, output, args.force):
            print(f"már naprakész, kihagyva: {output}")
            return

        await convert_file(source, output, args.voice, args.rate, args.pitch)
        return

    if args.output:
        sys.exit("--output csak egyetlen fájl konvertálásakor adható meg, mappánál nem.")

    txt_files = sorted(source.rglob("*.txt"))

    if not txt_files:
        print(f"Nincs .txt fájl itt: {source}")
        return

    for txt_path in txt_files:
        mp3_path = txt_path.with_suffix(".mp3")

        if not needs_conversion(txt_path, mp3_path, args.force):
            print(f"már naprakész, kihagyva: {mp3_path}")
            continue

        print(f"konvertálás: {txt_path}")
        await convert_file(txt_path, mp3_path, args.voice, args.rate, args.pitch)


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("input", help="egy .txt fájl, vagy egy mappa (rekurzívan megkeresi az összes .txt fájlt benne)")
    parser.add_argument("-o", "--output", help="kimeneti .mp3 útvonal (csak egyetlen fájl esetén)")
    parser.add_argument("--voice", default=DEFAULT_VOICE, help=f"edge-tts hang neve (alap: {DEFAULT_VOICE})")
    parser.add_argument("--rate", default="+0%", help="beszédsebesség módosítása, pl. +10%% vagy -10%%")
    parser.add_argument("--pitch", default="+0Hz", help="hangmagasság módosítása, pl. +5Hz vagy -5Hz")
    parser.add_argument("--force", action="store_true", help="akkor is konvertál, ha a .mp3 már naprakésznek tűnik")

    args = parser.parse_args()
    asyncio.run(run(args))


if __name__ == "__main__":
    main()
