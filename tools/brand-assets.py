"""Cut every logo, icon and favicon the site uses from the two master files.

    python tools/brand-assets.py

THE TWO MASTERS (supplied by the client, 2026-10-30)
  · "Geometric Access Exchange Logo (2).png" - the full lockup, A-X mark over
    THE ACCESS EXCHANGE. Desktop logo.
  · "Geometric A-X Logo with Golden Accent.png" - the A-X mark alone. The main
    logo: mobile masthead, compact masthead, favicon and site icon.
Both live in assets/images/logos/. Nothing here edits them; when the client
sends a new version, overwrite the master and run this again.

WHY THE LOCKUP IS UNMIXED, NOT JUST CUT OUT
The lockup arrives flattened onto cream (#FDFCF9), not transparent. A plain
colour-to-alpha turns the gold into a translucent brown that only looks right
on that exact cream. Instead every pixel is explained as ONE flat ink - black
or gold - laid over the cream at some coverage, and that coverage becomes the
alpha. The result is flat colour with clean anti-aliased edges, it sits on any
background, and recolouring it for the dark footer is a swap of two inks.

WHAT IT WRITES  (wordpress/assets/images/ → Media Library, /uploads/2026/10/)
  tae-logo.png            full lockup, for paper          masthead + menu ≥760px
  tae-logo-reverse.png    full lockup, for ink            footer ≥760px
  tae-mark.png            A-X mark, for paper             masthead <760px + compact
  tae-mark-reverse.png    A-X mark, for ink               footer <760px
  tae-site-icon.png       1024² on paper                  WP Site Icon + Yoast logo
  og-home.jpg             1200x630, lockup on paper       link previews (Yoast Social tab)
  favicon.ico             16/32/48 from the site icon     static preview only
  apple-touch-icon.png    180² from the site icon         static preview only

The site icon sits on solid paper rather than transparency on purpose: iOS
fills a transparent touch icon with black, which would swallow the black A.
"""

import sys
from pathlib import Path

import numpy as np
from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "assets" / "images" / "logos"
OUT = ROOT / "wordpress" / "assets" / "images"

LOCKUP = SRC / "Geometric Access Exchange Logo (2).png"
MARK = SRC / "Geometric A-X Logo with Golden Accent.png"

PAPER = (250, 249, 246)          # --paper
INK = (10, 10, 10)               # --ink


def rgba(arr):
    return Image.fromarray(np.clip(arr, 0, 255).round().astype(np.uint8), "RGBA")


def trim(img, pad=0.03):
    """Crop to what is actually drawn, keeping a small even margin."""
    l, t, r, b = img.getchannel("A").point(lambda a: 255 if a > 6 else 0).getbbox()
    p = round(max(r - l, b - t) * pad)
    return img.crop((max(l - p, 0), max(t - p, 0),
                     min(r + p, img.width), min(b + p, img.height)))


def fit_height(img, h):
    w = round(img.width * h / img.height)
    return img.resize((w, h), Image.LANCZOS)


def save_png(img, name):
    # three inks plus their edge blends: a 256-colour palette is visually
    # lossless here and a third to a quarter of the size
    img.quantize(256, method=Image.FASTOCTREE, dither=Image.Dither.NONE).save(
        OUT / name, optimize=True)
    print(f"  {name:24} {img.width}x{img.height}  {(OUT / name).stat().st_size // 1024}KB")


# ── the lockup ─────────────────────────────────────────────────────────────

def unmix_lockup():
    """Per pixel: how much ink covers the cream, and whether that ink is gold.

    The black and gold never touch in this artwork, so one ink per pixel is
    always enough to explain it."""
    px = np.asarray(Image.open(LOCKUP).convert("RGB")).astype(float)
    bg = np.median(px[:80, :80].reshape(-1, 3), axis=0)

    def cover(ink):
        d = np.array(ink, float) - bg
        a = np.clip(((px - bg) @ d) / (d @ d), 0, 1)
        return a, np.linalg.norm(px - (bg + a[..., None] * d), axis=-1)

    a_black, err_black = cover((7, 7, 7))
    a_gold, err_gold = cover((199, 158, 95))
    is_gold = err_gold < err_black
    a = np.where(is_gold, a_gold, a_black)
    a[a < 0.04] = 0                              # paper grain, not drawing
    return a, is_gold


def lockup(black, gold):
    a, is_gold = unmix_lockup()
    col = np.where(is_gold[..., None], np.array(gold, float), np.array(black, float))
    return trim(rgba(np.dstack([col, a * 255])))


# ── the mark ───────────────────────────────────────────────────────────────

def mark_reverse(img):
    """Black A → paper, cream X → ink, gold stays. Barycentric over the three."""
    px = np.asarray(img).astype(float)
    src = np.array([[13.0, 13, 13], [248, 244, 233], [202, 160, 84]])   # A, X, gold
    dst = np.array([PAPER, INK, (202, 160, 84)], float)
    A = np.vstack([src.T, np.ones(3)])                                  # 4x3
    rhs = np.vstack([px[..., :3].reshape(-1, 3).T, np.ones(px.shape[0] * px.shape[1])])
    w = np.linalg.lstsq(A, rhs, rcond=None)[0].T.clip(0)
    w /= w.sum(1, keepdims=True).clip(1e-6)
    col = (w @ dst).reshape(px.shape[0], px.shape[1], 3)
    return rgba(np.dstack([col, px[..., 3]]))


def site_icon(mark, size=1024, margin=0.06):
    tile = Image.new("RGBA", (size, size), PAPER + (255,))
    inner = round(size * (1 - 2 * margin))
    m = mark.resize((inner, round(mark.height * inner / mark.width)), Image.LANCZOS)
    tile.alpha_composite(m, ((size - m.width) // 2, (size - m.height) // 2))
    return tile.convert("RGB")


def share_image(lock, size=(1200, 630), h=330):
    """Link-preview card. The lockup stays inside the centre 630px square,
    because WhatsApp and Messenger crop small previews to a centred square."""
    card = Image.new("RGBA", size, PAPER + (255,))
    m = fit_height(lock, h)
    card.alpha_composite(m, ((size[0] - m.width) // 2, (size[1] - m.height) // 2))
    return card.convert("RGB")


def main():
    for f in (LOCKUP, MARK):
        if not f.exists():
            raise SystemExit(f"missing master: {f.relative_to(ROOT)}")
    OUT.mkdir(parents=True, exist_ok=True)
    print(f"writing {OUT.relative_to(ROOT)}/")

    # 256px tall covers the masthead's largest lockup (128px) at 2x; the footer
    # shows it up to 250px, so the reverse keeps the master's full resolution.
    lock = lockup(INK, (199, 158, 95))
    save_png(fit_height(lock, 256), "tae-logo.png")
    og = share_image(lock)
    og.save(OUT / "og-home.jpg", quality=90, optimize=True, progressive=True)
    print(f"  {'og-home.jpg':24} {og.width}x{og.height}  {(OUT / 'og-home.jpg').stat().st_size // 1024}KB")
    save_png(lockup(PAPER, (199, 158, 95)), "tae-logo-reverse.png")

    mark = trim(Image.open(MARK).convert("RGBA"), pad=0.02)
    save_png(fit_height(mark, 160), "tae-mark.png")
    save_png(fit_height(mark_reverse(mark), 240), "tae-mark-reverse.png")

    icon = site_icon(mark)
    save_png(icon, "tae-site-icon.png")
    icon.save(OUT / "favicon.ico", sizes=[(16, 16), (32, 32), (48, 48)])
    print(f"  {'favicon.ico':24} 16/32/48")
    save_png(icon.resize((180, 180), Image.LANCZOS), "apple-touch-icon.png")
    return 0


if __name__ == "__main__":
    sys.exit(main())
