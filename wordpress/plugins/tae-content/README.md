# The Access Exchange — Content plugin

Turns the interviews, the insights stream and the interview blocks on the home page
into editable content, without touching the design.

The markup it emits is the prototype's markup. `global.css` is unchanged, and
`global.js` needed exactly one line.

---

## 1. Install

1. Copy the `tae-content` folder into `wp-content/plugins/`.
2. **Plugins → Activate.** This registers the post types, seeds the five categories
   and three topics, and flushes rewrite rules.
3. **Settings → Permalinks → Save** once, as a belt-and-braces flush.
4. Apply the one-line edit to `global.js` — §4 below.
5. **Tools → Import TAE content** to create the 13 interviews and 11 insights that
   are currently hardcoded in the page files. Optional, but it saves typing 24
   records by hand.
6. Paste the shortcodes into the page files — already done in
   `wordpress/index.html`, `interview-series.html` and `insights.html`.

Elementor HTML widgets do not run shortcodes on their own. The plugin adds the
`elementor/widget/render_content` filter itself, which is the same filter
`CF7-SMTP.md` §3 requires for the six Contact Form 7 forms. If that filter is
already in a snippet plugin, leave it — running `do_shortcode` twice is harmless.

---

## 2. Shortcodes

```
[tae_interviews view="wall|archive|cover|featured|rail|watch" count="12" category="" heading=""]
[tae_insights   view="stream|start" count="14" heading=""]
```

| Shortcode | Where it goes | What it replaces |
|---|---|---|
| `[tae_interviews view="featured"]` | Home §04 | the whole `.feature` section |
| `[tae_interviews view="wall" count="12"]` | Home §05, inside `.shell` | `.filters` + `.grid` + `.wall-more` |
| `[tae_interviews view="rail" count="5"]` | Home §08, inside the second `.shell` | `.rail` |
| `[tae_interviews view="watch"]` | Home §11, inside `.shell` | `.watch-grid` + `.watch-cta` |
| `[tae_interviews view="cover"]` | Interview Series §01 | the whole `.cover` section |
| `[tae_interviews view="archive" heading="…"]` | Interview Series §03 | the whole `.index` section |
| `[tae_insights view="stream" heading="Insights"]` | Insights §01+§02 | `.top` + `.body` |
| `[tae_insights view="start" count="5"]` | Insights §03, inside `.shell` | `.five` |

Headings and body copy that are not content — the thesis, the format essay, the
creed, the partnership guide, the contact panel intros — stay in the Elementor
widget. They are edited where they already live.

`count="-1"` is not accepted. The stream defaults to **60**, because `global.js`
§09 filters and counts from the DOM — so whatever renders is exactly what search,
the topic filter and the sidebar counters can see, and anything past the cap would
be invisible to all three. Raise the number in the shortcode when the archive
outgrows it.

---

## 3. The fields

### Interviews

| Field | Notes |
|---|---|
| Title | the headline used everywhere |
| Featured image | one upload; WordPress makes the nine crops the design needs |
| Category | Engineering / Product / Data & AI / Design / Breaking in |
| Guest name, Guest role | printed as "Amara Osei, Staff Engineer" |
| Episode number | prints as "Ep 42" |
| Duration | `MM:SS` |
| YouTube video ID | the ID only. Nothing loads from youtube.com until play is pressed |
| Standfirst | the sentence under the headline |
| Guest rail line | the short line under the home-page portrait |
| Chapters | one per line, `03:10\|The bootcamp year` |
| Slot × 4 | Cover story, Left rail, Home feature, Home watch — independent, because the cover story is usually also the home feature |
| Show in "Most watched" | the numbered list on the Interview Series page |
| Order | rank within Most watched, and which of two claimants wins a slot |

### Insights

Title, body, featured image (optional), Topic, Dek, Byline, Onward link, Show in
"Start here", Use as lead tile, Order.

Two layout variants are worked out from the content, not chosen:

* no featured image → the text-only tile (`.it--text`)
* first in the stream → the lead tile (`.it--lead`)

**Use as lead tile** overrides that second one. Tick it on an insight or an
interview and it takes the wide slot at the top of the stream. Leave everything
unticked and the newest item leads, which is what the prototype did. Two ticked,
lower Order wins.

---

## 3b. Where tiles go

**Interview tiles** → `/interview-series/#episodes`. Interviews have no page of
their own, by design; the video plays inline on the archive.

**Insight tiles** → `/insights/<slug>/`, always. Every insight is a real place on
the site, whether or not an article has been written for it yet.

**Onward link** is what the reader finds when they get there — a button on the
insight's page, labelled to match where it goes. It does not divert the tile. An
earlier version sent the tile straight to it, which dated from when a bodiless
insight had nothing worth visiting; the effect was that no insight page was
reachable from the site that owned it.

To go back to tile-skips-to-link, return `$link` from `tae_destination()` in
`inc/render.php` when one is set. One line, and the test named "an onward link
does not divert the tile" will tell you you have done it.

### Pointer or article — decided per piece

**Pointer.** Leave the body empty, fill in the Onward link. The page shows the
headline, dateline, dek and image, then the button onward.

**Article.** Write the body. The page shows the article. Keep or clear the Onward
link as you like — a finished piece can still point somewhere next.

The importer seeds most insights as pointers, aimed where the prototype pointed.

### Every insight has a page, written or not

`/insights/<slug>/` renders for everybody, logged in or out. What varies is the
reading column:

| Body | The page shows |
|---|---|
| Written | headline, dateline, the article, image, read-next, get-involved links |
| Not yet | headline, dateline, the dek, image, the onward button, read-next, get-involved links |

Nothing is invented to fill the gap and the page never dead-ends. The onward
button names its destination rather than saying "Continue reading" over a link to
a video — "Watch the interview", "Read the partnership guide", "Read it in full".

**"Read next"** is three tiles: insights sharing this one's topic first, topped up
with recent interviews. It reuses the stream tile, so it needs no new CSS.

The layout is the About broadsheet — `.sheet`, `.spread`, `.copy`, `.plate`,
`.ends` — with two rules walked back inline: `.copy` drops from two columns to
one, and `.spread` releases the 232px margin column it reserves for editorial
notes this page does not have.

### What is different for search engines

Until a body exists, the page is assembled mostly from fields that also appear on
the Insights stream. So while it is unwritten (`inc/thin-pages.php`):

* it carries `noindex`
* it stays out of the sitemap, core's and Yoast's

Both lift on their own the moment somebody writes a body. There is no switch to
remember. If you would rather they were indexed from day one, delete the two
sitemap filters and the robots filter — the pages themselves do not change.

---

## 4. The one edit to `global.js`

Already applied. Line 100 was a JavaScript literal holding the site-search index,
including eight interview titles — so publishing an interview meant editing a `.js`
file, with nothing to tell you when somebody forgot.

```js
var SITE_INDEX = window.TAE_INDEX || [ … existing literal, unchanged … ];
```

The plugin publishes `window.TAE_INDEX` from real posts. The literal stays as the
fallback, so search still works if the plugin is deactivated.

Nothing else in `global.js` changed — masthead, menu, search overlay, scroll
reveals, click-to-load YouTube, the insights FLIP filter, contact segments and the
partnership rails are all untouched.

---

## 5. Two things worth understanding before editing

### The filter CSS is generated

The category filter is pure CSS — `:has()` plus a sibling combinator, no JS. That
needs one rule per term, so the rules cannot be hardcoded once categories are
editable. `inc/filter-css.php` writes them from the live taxonomy.

The hand-written originals in `global.css:677-681` stay where they are. For the
five seeded slugs they produce identical output — `tests/test-tae.php` asserts that
character for character — and they keep the filter working if the plugin is
deactivated.

**A bug this fixed.** `global.css:949` writes:

```css
.tae-interviews .sorts:has(#f-eng:checked) ~ .eps li
```

`.sorts` is inside `.index-head`, and `.eps` is a sibling of `.index-head`, not of
`.sorts`. The combinator never matches, so the Interview Series filter has never
worked. The generated rules scope to `.index-head` instead. The home page was fine
— `.filters` and `.grid` really are siblings there.

### Filtering refetches

"Earlier episodes" pages twelve at a time, and a CSS filter can only hide what is
in the DOM. Filtering to Design after one page would have shown two results when
seven exist, with nothing to say the answer was partial.

So a chip click refetches page one for that category server-side. Load-more then
pages within whatever category is active. The CSS rules still fire first, which is
what stops the grid flashing wrong tiles mid-request — and with JavaScript off they
are the whole filter, over the first twelve.

The home wall's search field is wired to the same endpoint. In the prototype it
was decorative: `global.js` §09 guards on `#stream`, which the home page does not
have, so it had never done anything.

---

## 6. Rendering a shortcode twice on one page

`#q`, `#c-all`, `#f-all`, `#stream`, `#tally` and friends are global IDs in the
prototype's markup. The first instance of a view on a page keeps them verbatim, so
`global.js` binds exactly as it always has. Later instances get suffixed IDs, their
own radio-group name and their own scoping class — no wrapper element, because a
wrapper would break the sibling combinator the filter depends on.

---

## 7. Tests

```
php tests/test-tae.php
```

44 checks, no framework, no WordPress: generated CSS against the stylesheet's own
rules, instance ID collisions, chapter parsing, link resolution, the guest line,
the reveal stagger, body detection, lead-tile selection and onward-link labelling.
Exit code 1 on failure.

---

## 8. Troubleshooting

**The page prints `[tae_interviews view="wall"]` as text.** The Elementor shortcode
filter is not running. Check the plugin is active.

**`/insights/<slug>/` 404s.** Settings → Permalinks → Save. If it still 404s, a page
with the slug `insights` is shadowing the post type's rewrite — the workaround is to
give the CPT a different rewrite slug in `inc/post-types.php`.

**The importer created posts with no images.** Sideloading needs outbound HTTP from
the host. The importer counts image failures separately and says so; it does not
report a clean run when the pictures did not arrive.

**Load-more does nothing.** `tae-archive.js` is being minified, combined or
deferred by an optimiser. `README-WORDPRESS.md` §7b lists the exclusion setting for
each of the usual plugins — `tae-archive.js` needs the same treatment as
`global.js`.

**A category chip hides everything.** The interview has no category term. It renders
in every unfiltered listing and is hidden by every filter except All. Assign a
category.

---

## 9. Delete after use

`inc/importer.php` and `data/seed.php` exist to run once. Delete both once the
import has happened.
