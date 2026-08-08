"""Download every remote image the build references, and point the pages at it.

    python tools/fetch-images.py            # show what would change
    python tools/fetch-images.py --write    # download and rewrite

WHY THIS IS A SCRIPT AND NOT A DONE JOB
The build hotlinks Pexels for its temporary imagery. That has to stop before
launch - a hotlinked hero is someone else's uptime, someone else's bandwidth
policy, and an extra DNS lookup and TLS handshake in front of the LCP element.

But the handoff also says original Access Exchange photography replaces the
temporary imagery after the first interview is produced. Committing 13 stock
photographs now, to delete them in a few weeks, is work done twice. So this is
the tool rather than the result: run it when the launch imagery is settled,
whether that is the stock set or the real one.

WHAT IT DOES
  · finds every remote <img src> across wordpress/*.html
  · downloads each once, into wordpress/assets/images/
  · names files after the page and section they serve, not the photo ID
  · rewrites the sources to the local path
  · leaves width/height query strings behind - the downloaded file IS the size
    that was requested, so asking for it again means nothing

AFTER RUNNING IT
  1. Compress. This does not: use Squoosh, ImageOptim, or a WordPress plugin
     that generates WebP/AVIF. The hero should land well under 200KB.
  2. Upload wordpress/assets/images/ to the Media Library, or ship it with the
     child theme.
  3. Drop the images.pexels.com preconnect from the theme head - it is a wasted
     connection once nothing is hotlinked.
  4. Re-run tools/build-static.py so the preview matches.
"""

import argparse
import re
import sys
from collections import Counter
from pathlib import Path
from urllib.request import urlopen, Request

ROOT = Path(__file__).resolve().parent.parent
WP = ROOT / "wordpress"
OUT = WP / "assets" / "images"

IMG = re.compile(r'<img\s[^>]*src="(https?://[^"]+)"[^>]*>', re.I)
ALT = re.compile(r'alt="([^"]*)"', re.I)


def slug(text, limit=48):
    text = re.sub(r"[^a-z0-9]+", "-", text.lower()).strip("-")
    return text[:limit].rstrip("-") or "image"


def collect():
    """Every remote image, with the page and alt text that give it its name."""
    found = []
    for page in sorted(WP.glob("*.html")):
        for tag in IMG.finditer(page.read_text(encoding="utf-8")):
            url = tag.group(1)
            alt = ALT.search(tag.group(0))
            found.append((page, url, alt.group(1) if alt else ""))
    return found


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--write", action="store_true",
                    help="download and rewrite; without it, only report")
    args = ap.parse_args()

    found = collect()
    if not found:
        print("No remote images left - nothing to do.")
        return 0

    # one local name per distinct URL, even where two pages share a photograph
    names, seen = {}, Counter()
    for page, url, alt in found:
        if url in names:
            continue
        stem = f"{page.stem}-{slug(alt)}"
        seen[stem] += 1
        if seen[stem] > 1:
            stem = f"{stem}-{seen[stem]}"
        ext = ".jpg" if ".png" not in url.lower() else ".png"
        names[url] = stem + ext

    print(f"{len(found)} references to {len(names)} distinct images\n")
    for url, name in names.items():
        print(f"  assets/images/{name}\n    <- {url[:96]}")

    if not args.write:
        print("\nDry run. Re-run with --write to download and rewrite.")
        return 0

    OUT.mkdir(parents=True, exist_ok=True)
    for url, name in names.items():
        target = OUT / name
        if target.exists():
            print(f"  have  {name}")
            continue
        try:
            req = Request(url, headers={"User-Agent": "Mozilla/5.0"})
            with urlopen(req, timeout=30) as r:
                target.write_bytes(r.read())
            print(f"  saved {name}  ({target.stat().st_size // 1024}KB)")
        except Exception as e:                       # noqa: BLE001
            print(f"  FAILED {name}: {e}", file=sys.stderr)
            return 1

    for page in sorted({p for p, _, _ in found}):
        text = page.read_text(encoding="utf-8")
        for url, name in names.items():
            text = text.replace(url, f"/wp-content/uploads/tae/{name}")
        page.write_text(text, encoding="utf-8")
        print(f"  rewrote {page.name}")

    print("\nNow compress them, upload assets/images/ to the Media Library, drop "
          "the pexels preconnect, and re-run tools/build-static.py.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
