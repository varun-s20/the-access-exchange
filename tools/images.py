"""The image manifest — every photograph on the site, in one place.

    python tools/images.py            # report what is placed vs what is here
    python tools/images.py --write    # write these sources into the pages

WHY A MANIFEST
Images were scattered across ten HTML files, so changing the art direction meant
hunting through all of them and hoping none was missed. This is the one list.
Change a URL here, run --write, and the page follows.

ART DIRECTION — handoff §17 and §19
The primary visual reference is Diary of a CEO / FlightStory: black-and-white
confidence, oversized typography, cinematic imagery. The brief's own language is
"close editorial portraits, real conversation, architectural and campus
environments, audiences listening, subtle production details".

So the rule applied here: dark, real rooms with real people in them. No stock
handshakes, no smiling-at-a-laptop, no glass-tower boardrooms. Where a shot had
to be lit and warm, it is a room full of people rather than a posed pair.

Every one of these was viewed as a contact sheet before it was chosen, and every
one is a free Unsplash photo — no Unsplash+ premium in the list.

REPLACING THEM WITH REAL PHOTOGRAPHY
The handoff says original Access Exchange photography replaces this set after
the first production, and §17 has the shot list. When those arrive, change the
`url` values here — the `shape` and `alt` stay, because they describe the slot
rather than the picture.
"""

import argparse
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
WP = ROOT / "wordpress"
UN = "https://images.unsplash.com/"


def u(photo, w, h):
    return f"{UN}{photo}?auto=format&fit=crop&w={w}&h={h}&q=72"


# page → list of slots, in the order the <img> tags appear in that file.
#   shape  the crop the layout needs
#   url    the source
#   alt    what the picture shows, for a screen reader
MANIFEST = {
    "index": [
        dict(shape=(2100, 900), photo="photo-1587825140708-dfaf72ae4b04",
             alt="A speaker alone on a lit stage in a darkened auditorium",
             note="hero band. The most cinematic frame on the site — it sets the tone before a word is read."),
        dict(shape=(900, 1200), photo="photo-1663162550938-60f70fab5d31",
             alt="Students walking together across a university campus",
             note="universities block. Real students, mid-stride, not posed."),
        dict(shape=(1200, 900), photo="photo-1544531586-fde5298cdd40",
             alt="A speaker facing a full auditorium",
             note="live experiences panel. Black-and-white confidence, straight from the FlightStory reference."),
        dict(shape=(1200, 900), photo="photo-1573496546038-82f9c39f6365",
             alt="Two people in a quiet one-to-one conversation by a window",
             note="coaching panel. Low light, two people, no laptop — coaching is a conversation."),
    ],
    "guests-partners": [
        dict(shape=(2400, 1600), photo="photo-1559523161-0fc0d8b38a7a",
             alt="",
             note="hero. An interview actually being recorded — microphones, headphones, "
                  "two people mid-conversation. This is the thing a prospective guest is "
                  "being asked to take part in, so the hero shows it rather than a metaphor. "
                  "Decorative: the heading carries the meaning, so alt is empty."),
    ],
    "universities": [
        dict(shape=(1600, 686), photo="photo-1540575467063-178a50c2df87",
             alt="A speaker addressing a full lecture hall",
             note="the format plate. A leadership talk as it actually looks from the back of the room."),
        dict(shape=(800, 600), photo="photo-1763890763432-17c9a529da20",
             alt="Two students walking through campus",
             note="plate pair, student organisations."),
        dict(shape=(800, 600), photo="photo-1670528148572-9270351b95bd",
             alt="A university building",
             note="plate pair, institutional. The brief asks for architectural environments."),
    ],
    "experiences": [
        dict(shape=(2100, 900), photo="photo-1475721027785-f74eccf877e2",
             alt="A microphone in front of a blurred audience",
             note="hero band. Dark, cinematic, and the audience is the subject — which is the page's argument."),
        dict(shape=(1200, 900), photo="photo-1594122230689-45899d9e6f69",
             alt="Two people in a moderated discussion on stage",
             note="Leadership discussions."),
        dict(shape=(1200, 900), photo="photo-1560439514-4e9645039924",
             alt="A large audience gathered at an industry event",
             note="Industry panels. Scale is the point here, so the room is the subject."),
        dict(shape=(1200, 900), photo="photo-1517048676732-d65bc937f952",
             alt="Hands taking notes around a shared table",
             note="Learning experiences. Close, warm, working — the one non-auditorium frame."),
        dict(shape=(1200, 900), photo="photo-1515187029135-18ee286d815b",
             alt="A speaker with a small seated group in a warm room",
             note="Access Exchange events. The gathering, not the stage."),
        dict(shape=(1000, 750), photo="photo-1524178232363-1fb2b075b655",
             alt="An audience listening closely during a session",
             note="room pair — 'the listening, which is most of it'."),
        dict(shape=(1000, 750), photo="photo-1653566031535-bcf33e1c2893",
             alt="People talking together after a session",
             note="room pair — 'and the twenty minutes afterwards'."),
    ],
    "about": [
        dict(shape=(1800, 760), photo="photo-1478737270239-2f02b77fc618",
             alt="A microphone in a recording studio",
             note="broadsheet plate. A production detail rather than a scene — §17 asks for those."),
        dict(shape=(620, 775), photo="photo-1745060594679-61578eb592f7",
             alt="Portrait placeholder - replace with the founder's photograph",
             note="FOUNDER PLACEHOLDER. Dark, editorial, executive register — the right shape "
                  "for the real portrait to drop into. Must be replaced before launch."),
        dict(shape=(800, 560), photo="photo-1641160616553-a9d21a846e49",
             alt="A university campus building",
             note="colophon pair, institutions."),
        dict(shape=(800, 560), photo="photo-1675609459162-41621fb7bee3",
             alt="Two people in conversation",
             note="colophon pair, rooms."),
    ],
}

IMG = re.compile(r"<img\s[^>]*>")


def apply(write):
    total = ok = 0
    for stem, slots in MANIFEST.items():
        f = WP / f"{stem}.html"
        text = f.read_text(encoding="utf-8")
        tags = IMG.findall(text)
        total += len(tags)
        if len(tags) != len(slots):
            print(f"  !! {stem}: page has {len(tags)} images, manifest has {len(slots)}")
            continue
        for tag, slot in zip(tags, slots):
            w, h = slot["shape"]
            new = re.sub(r'src="[^"]*"', f'src="{u(slot["photo"], w, h)}"', tag)
            new = re.sub(r'alt="[^"]*"', f'alt="{slot["alt"]}"', new)
            text = text.replace(tag, new, 1)
            ok += 1
        if write:
            f.write_text(text, encoding="utf-8")
        print(f"  {'wrote' if write else 'would write'} {stem}.html — {len(slots)} images")
    print(f"\n{ok}/{total} slots matched")
    return 0 if ok == total else 1


if __name__ == "__main__":
    ap = argparse.ArgumentParser()
    ap.add_argument("--write", action="store_true")
    a = ap.parse_args()
    rc = apply(a.write)
    if a.write:
        print("Now run: python tools/build-static.py")
    sys.exit(rc)
