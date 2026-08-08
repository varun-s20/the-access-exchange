# The Access Exchange — WordPress / Elementor build

Everything in this folder is **new**. Nothing outside `wordpress/` was touched,
and the `20vc/` folder was left alone entirely.

```
wordpress/
├── assets/
│   ├── global.css                 all CSS for all six pages, one file
│   └── global.js                  all JS for all six pages, one file
├── header.html                    Theme Builder → Header
├── footer.html                    Theme Builder → Footer
├── index.html                     page body only  →  /
├── interview-series.html          page body only  →  /interview-series/
├── university-partnerships.html   page body only  →  /university-partnerships/
├── insights.html                  page body only  →  /insights/
├── about.html                     page body only  →  /about/
└── contact.html                   page body only  →  /contact/
```

The six page files contain **no** `<style>`, **no** `<script>` (except JSON-LD),
**no** header and **no** footer. They are body content only, as asked.

---

## 1. Install the global assets

### Option A — child theme (recommended: cached, minified, versioned)

Copy `assets/global.css` and `assets/global.js` into your child theme, then:

```php
// child-theme/functions.php
add_action( 'wp_enqueue_scripts', function () {
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();

    wp_enqueue_style(
        'tae-global',
        $uri . '/assets/global.css',
        [ 'elementor-frontend' ],                    // load AFTER Elementor
        filemtime( $dir . '/assets/global.css' )     // cache-bust on save
    );

    wp_enqueue_script(
        'tae-global',
        $uri . '/assets/global.js',
        [],
        filemtime( $dir . '/assets/global.js' ),
        true                                         // in the footer
    );
    wp_script_add_data( 'tae-global', 'defer', true );
}, 20 );

// Google Fonts — one stylesheet, preconnected
add_action( 'wp_head', function () {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link rel="preconnect" href="https://images.pexels.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">';
    echo '<meta name="theme-color" content="#F8F8F3">';
}, 1 );
```

### Option B — no code (Elementor Pro only)

* CSS → **Elementor → Site Settings → Custom CSS**, paste `global.css`.
  Put it *there*, not in a Custom Code snippet: Elementor appends Site Settings
  Custom CSS to the end of the kit stylesheet, so it loads after Elementor's
  own Theme Style. A `<head>` snippet loads *before* the theme and loses ties.
* JS → **Elementor → Custom Code → Add New**, Location **Body – End**, paste
  `global.js` wrapped in `<script>…</script>`, Display Conditions **Entire Site**.
* Fonts → see the next section. `global.css` already carries an `@import`, so
  this works with no extra step, but there is a faster route.

Option B ships the CSS inline on every request and it is not cached separately.
Move to A before launch.

---

## 1b. Fonts

The design names `Skandia` and `Leif` first and falls back to **Figtree**, the
free stand-in. If nothing loads Figtree the pages render in Arial and look
wrong — that is the "missing font integration" symptom.

Pick one route.

**Route A — `@import` (already done, works on any install).**
Line 1 of `global.css` is:

```css
@import url('https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap');
```

Nothing else to do. It must stay the first statement in the file — only
comments may precede it, and any rule above it makes browsers drop it silently.
Cost: one extra round-trip, because `@import` is invisible to the browser's
preload scanner.

**Route B — `<head>` link (faster, Elementor Pro).**
**Elementor → Custom Code → Add New**, Location **`<head>`**, Display
Conditions **Entire Site**:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://images.pexels.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
<meta name="theme-color" content="#F8F8F3">
```

Then **delete the `@import` line** from `global.css` so the font is not
requested twice.

**Route C — self-host (fastest, and GDPR-clean).**
Download the Figtree variable `woff2` from
[fontsource.org/fonts/figtree](https://fontsource.org/fonts/figtree), upload it
(WordPress blocks `.woff2` uploads by default — use FTP or a File Manager
plugin into `/wp-content/uploads/fonts/`), delete the `@import`, and put this
at the top of `global.css` instead:

```css
@font-face{
  font-family:"Figtree";
  src:url("/wp-content/uploads/fonts/figtree-variable.woff2") format("woff2-variations");
  font-weight:300 900;
  font-style:normal;
  font-display:swap;
}
```

Repeat with the italic file and `font-style:italic` — the design uses italics
in the wordmark, the thesis headline and several pull quotes, so without it the
browser fakes them by slanting the roman.

**Then, on any route, turn Elementor's own fonts off** — see the next section.
Otherwise Elementor writes `.elementor-kit-123 { font-family: Roboto }` onto
`<body>`, which out-specifies a plain `body` rule and puts every paragraph back
in the wrong face.

---

## 1c. Killing the pink — Hello Elementor's theme styles

The pink borders and pink hovers are **Hello Elementor's `theme.css`**. It
paints links and buttons in `#C36`:

```css
a { color: #c36; }
button, input[type="button"], input[type="submit"] {
  border: 1px solid #c36; background: #c36; color: #fff;
}
```

Those are bare element selectors, so a plain `button { border: 0 }` in our
reset only *ties* with them — and ties are broken by load order, which you do
not control inside Elementor. That is why it leaks through.

Three admin toggles, then a safety net that is already in the CSS.

**1. Elementor → Site Settings → Layout**
   * **Default Colors** → **Disable**
   * **Default Fonts** → **Disable**

   This stops Elementor writing its kit typography and palette onto `<body>`.
   Do this even if you think you never set them — the defaults ship enabled.

**2. Turn off Hello Elementor's theme style.**
   Recent Hello versions expose this as a toggle in the theme's own settings
   screen in wp-admin (the menu label has moved between versions — look for
   **Hello Elementor → Settings**, or a **Theme Style** switch under
   **Appearance**). If your version has it, switch **Theme Style** off. If it
   does not, skip it: step 3 covers you.

**3. Hide the theme's page title.** Hello prints its own `<h1 class="entry-title">`
   above the content. In each page: Elementor → **Page Settings → Hide Title**.
   Otherwise every page ships two `<h1>`s, which costs you on SEO.

**The safety net (already in `global.css`, §02b).**
Whatever the load order ends up being, the stylesheet restates the reset one
specificity step higher, scoped to our own containers:

```css
.tae a, .mast a, .veil a, .find a, .foot a,
.tae a:hover, … { color: inherit; text-decoration: none; box-shadow: none }

.tae button, .mast button, .veil button, .find button, .foot button, … {
  font: inherit; color: inherit; background: none;
  border: 0; border-radius: 0; box-shadow: none; padding: 0; …
}

:root body { font-family: var(--leif); … }   /* beats .elementor-kit-123 */
```

`.tae button` is `(0,1,1)`; Hello's `button` is `(0,0,1)`. Ours wins outright,
in every load order, with no `!important`. `:root body` is `(0,1,1)` against
Elementor's `.elementor-kit-123` at `(0,1,0)` — same trick.

The block sits deliberately *before* the chrome and the page blocks, so every
rule further down the file still overrides it and our own buttons keep their
black fills, radii and hover states.

**If pink still appears somewhere**, it is an element outside our five
containers. Inspect it — if it is an Elementor widget you added yourself,
restyle it in Elementor; if it is inside our markup, tell me the selector and
the container list at the top of §02b needs one more entry.

---

## 2. Theme Builder — header and footer

**Templates → Theme Builder → Header → Add New**

1. One Container: **Full Width**, content width Full, padding `0`, gap `0`,
   HTML Tag → `header`.
2. Drop in a single **HTML** widget. Paste all of `header.html`.
3. **Display Conditions → Entire Site** → Publish.
4. Do **not** apply Elementor's Sticky or Motion Effects to this container.
   The masthead does its own `position:fixed` and collapse, and a `transform`
   on any ancestor would break both it and the partnerships outline rail.

**Templates → Theme Builder → Footer → Add New** — same recipe, HTML tag
`footer`, paste `footer.html`, Entire Site, Publish.

The active menu item is set at runtime by `global.js` (it compares each nav
`href` to `location.pathname`), so the header file stays identical site-wide.

---

## 3. The six pages

For each page: **Pages → Add New** → set the slug → **Edit with Elementor** →
Page Settings → **Page Layout: Elementor Full Width** → one Container
(padding `0`, gap `0`, HTML Tag `main`) → one **HTML** widget → paste the file.

| File | Page title | Slug |
|---|---|---|
| `index.html` | Home | *(set as the static front page)* |
| `interview-series.html` | The Interview Series | `interview-series` |
| `university-partnerships.html` | University Partnerships | `university-partnerships` |
| `insights.html` | Insights | `insights` |
| `about.html` | About | `about` |
| `contact.html` | Contact | `contact` |

Then **Settings → Reading → Your homepage displays → A static page → Home**.

Permalinks must be **Post name** (`Settings → Permalinks`). Every internal link
in these files is a root-relative pretty URL (`/about/`, `/interview-series/#episodes`),
so nothing needs rewriting when the domain changes or the site moves from
staging to live.

---

## 4. How the one-stylesheet trick works

The six prototypes were independent single-file pages, and several of them
defined the **same class name differently** — `.btn`, `.card`, `.form`,
`.field`, `.assure`, `.pull`, `.plate`, `.who`, `.chip`, `.body`. Merging them
naively would have broken four pages out of six.

So each page body opens with a wrapper:

```html
<div class="tae tae-home">        <!-- or tae-interviews, tae-partnerships,
                                       tae-insights, tae-about, tae-contact -->
```

and every page-specific rule in `global.css` is prefixed with it. The custom
properties that differ per page (`--shell`, `--pad`, `--sec`, `--gut`, `--r`,
and the interviews page's slightly warmer paper tones) are re-declared on that
same wrapper, so they cascade into the page and never reach the masthead or the
footer — which is why the chrome stays byte-identical everywhere.

**If you add a new page**, give it its own `tae-{name}` wrapper and add a new
numbered block at the bottom of `global.css`. Never add an unprefixed rule.

`global.js` follows the same idea: one file, one IIFE, and every page module
starts with a DOM guard (`if (!document.getElementById('stream')) return;`), so
a page that lacks a component simply skips it.

---

## 5. Per-page SEO

**→ Full Yoast walkthrough, with every field value: [`SEO-YOAST.md`](SEO-YOAST.md)**

Meta tags cannot live inside an Elementor HTML widget — they would land in the
`<body>`. So each page file carries a **comment block** at the top with the
title/description/keyphrase values, and those get typed into Yoast.

Schema is split, because Yoast emits its own and shipping both means duplicate
entities:

| Node | Emitted by |
|---|---|
| `Organization`, `WebSite`, `WebPage`, `BreadcrumbList` | **Yoast** |
| `CollectionPage` / `AboutPage` / `ContactPage` | **Yoast**, per page in its Schema tab |
| `Service` (partnerships) | inline JSON-LD — Yoast cannot express it |
| `FAQPage` + `Question` ×10 | inline JSON-LD — Yoast needs its Gutenberg FAQ block, unavailable in an Elementor HTML widget |

Four page files (`index`, `about`, `contact`, `insights`) therefore carry **no**
JSON-LD at all. Only `interview-series.html` and `university-partnerships.html`
still do, and their `@id` values match Yoast's node ids so the two graphs stitch
together.

**Domain**: `https://theaccessexchange.com` appears in two places — inside those
two JSON-LD blocks (functional, must be correct) and inside the instruction
comments at the top of all six files (cosmetic). Find-and-replace across all six
before launch anyway.

**Optional**: those instruction comments are served to the browser on every
request — roughly 1–2 KB per page. Keep them while you are still configuring;
strip them from the pasted version once the site is live if you want the source
clean. The build notes live in this repo either way.

Other SEO/a11y work already done here versus the prototypes:

* **About** had no `<h1>` at all — it does now (same size, same rule).
* Every section has an `aria-label` or `aria-labelledby`; heading order is
  `h1 → h2 → h3` on all six pages.
* Decorative `alt=""` replaced with real descriptions on the guest portraits.
* Dates wrapped in `<time datetime="…">`.
* Placeholder `href="#"` links now point at real destinations.

---

## 6. Performance

Already in place:

* **Click-to-load YouTube.** Nothing is requested from youtube.com until a
  play button is pressed (`global.js` §06). This is the single biggest reason
  these pages hit the 80+ PageSpeed target.
* **One eager image per page**, marked `fetchpriority="high"`; every other
  photograph is `loading="lazy" decoding="async"`.
* **No layout shift**: every photo sits in a container with a CSS
  `aspect-ratio`, so images reserve their box before they load.
* **Compositor-only animation**: the insights filter uses FLIP with the Web
  Animations API (transform only); the scroll reveals are `opacity`+`transform`.
* `prefers-reduced-motion` is honoured on all six pages.

Still to do before launch:

1. **Move the images into the Media Library.** They currently point at
   `images.pexels.com`. Uploading them lets WordPress serve WebP/AVIF and
   `srcset`, and removes a third-party origin from the critical path.
2. **Buy or self-host the real type.** The CSS names `Skandia` and `Leif`
   first and falls back to Figtree, so a purchased licence drops straight in —
   add `@font-face` blocks and nothing else changes.
3. **Disable Elementor's default fonts/colours** (Site Settings → Layout →
   Disable Default Colors *and* Default Fonts) so its globals do not fight
   `global.css`.
4. Turn on a caching plugin, but **exclude nothing** — there is no
   personalised content here.

---

## 7. Forms

**→ Full walkthrough: [`CF7-SMTP.md`](CF7-SMTP.md)**

The six forms (four on Contact, one on The Interview Series, one on University
Partnerships) are **Contact Form 7 shortcodes**. The page files ship with
`REPLACE_ID_…` placeholders — the site does not send anything until you create
the six forms and paste their real ids in.

Three things are easy to miss, all covered in `CF7-SMTP.md`:

1. **SMTP first.** PHP `mail()` is unsigned and gets dropped silently. Install
   **WP Mail SMTP**, force the From address to `@theaccessexchange.com`, and add
   SPF + DKIM + DMARC. Every form uses `wp_mail()`, so this one fix covers all
   six. Note the free tier has no email log — see `CF7-SMTP.md` §1.5.
2. **Elementor HTML widgets do not run shortcodes.** One `elementor/widget/
   render_content` filter fixes it, otherwise the page prints the shortcode as
   text.
3. **Turn off CF7's autop** (`wpcf7_autop_or_not` → false), or CF7's injected
   `<p>` tags land as stray items in the form's CSS grid.

The visitor's address goes in **Reply-To**, never in **From** — that is the
single most common cause of form mail going to spam.

Each form ships a **branded HTML notification email** built from the same
palette as the site (`#F8F8F3` card, `#14140F` header bar, small-caps labels,
one-click reply button). `CF7-SMTP.md` §4.1–§4.6 give each one complete: form
fields, mail settings, email body, thank-you copy, and the exact line in the
exact page file its shortcode replaces.

`global.js` §07 (the prototype submit handler) is now inert: it binds to
`form[data-validate]` and no form carries that attribute any more.

---

## 7b. Troubleshooting

### "Clicking the menu does nothing"

**Start here. One command, and it splits the problem cleanly in half.**

Open DevTools → Console, type `TAE`, press Enter.

Do **not** judge this by whether log messages appear. Chrome's console has a
log-level filter that hides `console.log`/`console.info` by default in some
configurations, so "nothing in the console" is not evidence either way. A
global object cannot be filtered.

---

#### `TAE` is `undefined` → the script never executed

This is a delivery problem, not a code problem. In order of likelihood:

1. **The JS was pasted without `<script>` tags.** Elementor Custom Code and
   HTML widgets output what you give them *verbatim*. Raw JavaScript with no
   wrapper is emitted as text, not executed. Check: View Page Source and
   search for `THE ACCESS EXCHANGE — GLOBAL SCRIPT`. If you find it but the
   line above it is not `<script>`, that is your bug.
2. **The Custom Code snippet is not live.** Elementor Custom Code has its own
   publish flow: it must be **Published** (not Draft) *and* have Display
   Conditions set to **Entire Site**. Both, not either.
3. **Custom Code is not available.** It is an Elementor **Pro** feature. On
   free Elementor the menu exists in some versions but does nothing.
4. **A cache is serving a copy from before you added it** → purge the plugin
   cache *and* Cloudflare/host cache, then retest in a fresh window.

**The reliable fix for all four:** ship the JS in the footer widget instead.
`footer.html` has a marked slot at the bottom with instructions. That widget is
already rendering on every page, so there is nothing left to go wrong.
**Use one route only** — if you paste it into the footer, delete the Custom
Code snippet. Two copies bind every click handler twice, so the menu opens and
instantly closes, which looks identical to nothing happening. (`global.js`
detects this and warns, but fix the cause.)

---

#### `TAE` is an object → the script ran; read the fields

| Field | Meaning if wrong |
|---|---|
| `ready: false` | A module threw before boot finished — check `TAE.errors` |
| `errors: [...]` | Paste these to me |
| `menuBtn: false` | The header template is not rendering. Check its Display Conditions |
| `veil: false` | Same — the menu panel markup is missing from the page |
| `veilOnBody: false` | The panel could not be hoisted; unusual, tell me |

Then run **`TAE.openMenu()`** in the console.

* **The panel slides in** → the JavaScript and the CSS are both fine. The
  *click* is not reaching the button: something is sitting on top of it.
  Usually Elementor's header container has a `z-index` above the masthead, or
  a sticky/overlay element covers it. Inspect the menu icon, and in DevTools
  check what element is actually at that point.
* **Nothing happens** → the panel is being rendered but not shown. That is
  CSS: check `.veil.open` is in the stylesheet and that no plugin is setting
  `display:none` on `#veil`.

---

#### Related: "works logged in, not in incognito"

Nothing in this codebase branches on login state. Logged-in users bypass page
cache, and most optimisers only minify/defer/combine for guests — so a
guest-only failure is nearly always the optimiser or the cache:

* Add `global.js` to the JS minify/combine **exclusion list** (LiteSpeed,
  WP Rocket, Autoptimize, SiteGround Optimizer, Hummingbird).
* Turn off **Cloudflare → Speed → Optimization → Rocket Loader** — a
  well-known breaker of inline scripts.
* A **cookie-consent plugin** in "block scripts until consent" mode strips
  inline `<script>` for non-consented visitors and usually exempts admins.

Purge every cache layer after any change, and retest in a *fresh* incognito
window — a stale one keeps its own memory cache.

### "Hover makes the text disappear into the button"

Fixed — but if you see it again after editing the CSS, you have almost
certainly added a `:hover` rule to the neutraliser in §02b. Read the comment
block above those rules: `:hover` counts in the **class** column of a
specificity triple, so `.tae button:hover` is `(0,2,1)` and out-ranks
`.tae-contact .btn` at `(0,2,0)` — stripping the button's fill while the text
keeps its light colour. That is why those rules are wrapped in `:where()`,
which contributes zero specificity. Do not remove it.

---

## 8. Known gotchas

* **Do not** wrap the header container in Elementor Sticky / Motion Effects.
  A `transform` on an ancestor breaks `position:fixed` for both the masthead
  and the partnerships outline rail.
* The **SVG sprite** at the top of `header.html` must stay. The page bodies
  reference it (`<use href="#i-arrow">`, `#i-left`, `#i-right`, `#b-youtube`).
* The two resets marked `[SCOPE-IF-NEEDED]` in `global.css` §02 strip heading
  margins and list bullets **globally**, which is correct while all six pages
  are these templates. If you later add stock WordPress content (a blog post,
  a plugin's output) that needs default bullets, prefix those two lines with
  `.tae `.
* The **Insights counters** in the topic sidebar are no longer hard-coded — the
  `tae-content` plugin tallies them from the posts it renders. See §9.
* `id="q"` is used by both the Home archive filter and the Insights search
  field. They are on different pages, so this is fine — and the plugin now
  suffixes the IDs automatically if a view is ever rendered twice on one page.

---

## 9. Editable content — the `tae-content` plugin

**→ Full guide: [`plugins/tae-content/README.md`](plugins/tae-content/README.md)**

The interviews, the insights stream and the interview blocks on the home page are
no longer hardcoded markup. They come from two custom post types, rendered by two
shortcodes that emit the same markup the prototype did — so `global.css` is
unchanged and `global.js` needed exactly one line.

```
[tae_interviews view="wall|archive|cover|featured|rail|watch" count="12"]
[tae_insights   view="stream|start" count="14"]
```

Already pasted into `index.html`, `interview-series.html` and `insights.html`.

Three things this changes that are worth knowing here:

1. **`SITE_INDEX` is generated.** `global.js:100` used to be a JavaScript literal
   holding eight interview titles, so publishing an interview meant editing a `.js`
   file. It now reads `window.TAE_INDEX || [ …the old literal… ]` — the plugin
   supplies the first, and the literal is the fallback if the plugin is off.
2. **The Interview Series category filter works now.** It never did. `global.css`
   §949 writes `.sorts:has(…) ~ .eps li`, but `.sorts` is *inside* `.index-head`
   and `.eps` is a sibling of `.index-head` — so the combinator never matched. The
   generated rules scope to `.index-head`. The home page was always fine.
3. **`tae-archive.js` needs the same optimiser exclusions as `global.js`** — see
   §7b. Load-more and the category refetch both live in it.

Static prose (the thesis, the format essay, the creed, the partnership guide, the
contact panel intros) and the FAQ accordion stay in the Elementor widgets by
decision. They are edited where they already live.
