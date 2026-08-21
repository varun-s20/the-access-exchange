# The Access Exchange - Content plugin

Turns the interviews, the takeaways stream and the interview blocks on the home
page into editable content, without touching the design.

The markup it emits is the prototype's markup. `global.css` is unchanged, and
`global.js` needed two edits, both listed in §4.

---

## 1. Install

1. Copy the `tae-content` folder into `wp-content/plugins/`.
2. **Plugins → Activate.** This registers the post types, seeds the five
   categories and four topics, and flushes rewrite rules.
3. **Settings → Permalinks → Save** once, as a belt-and-braces flush.
4. Apply the edits to `global.js` - §4 below.
5. **Interviews → Links**, if the YouTube channel or the Join CTA has moved from
   its default. Both work untouched.
6. Paste the shortcodes into the page files - already done in
   `wordpress/index.html` and `wordpress/interview-series.html`.

There is no import step. `inc/importer.php` and `data/seed.php` transcribed the
prototype's placeholder records and are deleted: that copy is not the launch
content, and a Tools-menu button one click from putting demo interviews on a live
site is not worth keeping for an import that runs once. The launch state is an
empty library that fills in as the owner publishes.

Elementor HTML widgets do not run shortcodes on their own. The plugin adds the
`elementor/widget/render_content` filter itself, which is the same filter
`CF7-SMTP.md` §3 requires for the Contact Form 7 forms. If that filter is already
in a snippet plugin, leave it - running `do_shortcode` twice is harmless.

---

## 2. Shortcodes

```
[tae_interviews  view="archive|cover|featured" count="12" category="" heading="" divider=""]
[tae_insights    view="stream" count="60" heading="" search="yes|no" divider="" lede=""]
[tae_coming_soon until="feature|any" line="" body="" cta="" href=""]
```

| Shortcode                              | Where it goes        | What it replaces               |
| -------------------------------------- | -------------------- | ------------------------------ |
| `[tae_coming_soon until="feature"]`    | Home §03             | the launch-state card          |
| `[tae_interviews view="featured"]`     | Home, after §03      | the whole `.feature` section   |
| `[tae_coming_soon until="any"]`        | Interview Series §01 | the launch-state card          |
| `[tae_interviews view="cover"]`        | Interview Series §02 | the whole `.cover` section     |
| `[tae_interviews view="archive"]`      | Interview Series §04 | the whole `.index` section     |
| `[tae_insights view="stream"]`         | Interview Series §05 | the takeaways stream           |

`wall`, `rail` and `watch` went in Phase 7 with their CSS and templates - no page
in the approved architecture used them. `[tae_insights view="start"]` went the
same way, later and for a worse reason: it was never placed on a page at all, so
"Show in Start here" was a checkbox the owner could tick to no effect anywhere.

Headings and body copy that are not content - the thesis, the format essay, the
creed, the partnership guide, the contact panel intros - stay in the Elementor
widget. They are edited where they already live.

`count="-1"` is not accepted. The stream defaults to **60**, because `global.js`
§09 filters and counts from the DOM - so whatever renders is exactly what search,
the topic filter and the sidebar counters can see, and anything past the cap would
be invisible to all three. Raise the number in the shortcode when the archive
outgrows it.

---

## 3. The fields

### Interviews

| Field                     | Notes                                                                                            |
| ------------------------- | ------------------------------------------------------------------------------------------------ |
| Title                     | the headline used everywhere                                                                     |
| Featured image            | one upload; WordPress makes the six crops the design needs                                       |
| Category                  | Leadership / Founders & Builders / Industry & Craft / Career & Transitions / On Campus           |
| Guest name, title, org    | printed as "Amara Osei, Staff Engineer, Northwind"                                               |
| Episode number            | prints as "Ep 42"                                                                                |
| Duration                  | `MM:SS`, or `H:MM:SS`. Also becomes the VideoObject duration                                     |
| YouTube video ID or URL   | paste either. Whatever you paste is reduced to the bare ID on save                               |
| Standfirst                | the sentence under the headline. Doubles as the meta description                                 |
| Guest rail line           | the short line under a portrait                                                                  |
| Key takeaways             | one per line, numbered on the episode page                                                       |
| Chapters                  | one per line, `03:10\|The bootcamp year`                                                         |
| Slot × 3                  | Cover story, Left rail, Home feature - independent, because the cover story is usually also the home feature |
| Show in "Most watched"    | the numbered list on the Interview Series page                                                   |
| Watch next                | which interview leads "Carry on from here". Empty = the three most recent                        |
| Order                     | rank within Most watched, and which of two claimants wins a slot                                 |

**One image, deliberately.** Handoff §9 names "Guest portrait / episode cover
image" in one bullet, but every place the design puts an interview image is
landscape - 1400×875, 640×360, 560×420, 1800×1013. There is no portrait slot to
fill, so there is no second upload field. `tae-card`, `tae-rail` and `tae-watch`
were registered for sections deleted in Phase 7 and generated on every upload for
two phases without ever being printed; they are gone. Add a size and the template
that prints it in the same change, or not at all.

### Takeaways

Title, body, featured image (optional), Topic, **Came from which interview**,
Dek, Byline, Onward link, Use as lead tile, Order.

Two layout variants are worked out from the content, not chosen:

- no featured image → the text-only tile (`.it--text`)
- first in the stream → the lead tile (`.it--lead`)

**Use as lead tile** overrides that second one. Tick it on a takeaway or an
interview and it takes the wide slot at the top of the stream. Leave everything
unticked and the newest item leads, which is what the prototype did. Two ticked,
lower Order wins.

---

## 3b. Where things point

**Interview tiles** → `/interviews/{slug}/`, the episode page.
**Takeaway tiles** → `/takeaways/{slug}/`, always.

Neither post type has a WordPress archive: `/interview-series/` composes the
cover, the archive and the stream, which a generated archive could not do. The
bare rewrite bases redirect there rather than 404 (`inc/post-types.php`,
`tae_redirect_bare_bases`).

**Onward link** is what the reader finds when they arrive - a button on the
takeaway's page, labelled to match where it goes. It does not divert the tile. An
earlier version sent the tile straight to it, which dated from when a bodiless
takeaway had nothing worth visiting; the effect was that no takeaway page was
reachable from the site that owned it.

### Came from which interview

The field the whole "takeaways live inside the Interview Series ecosystem"
instruction (handoff §6) rests on. Setting it does three things:

- the episode page grows a **From this interview** section listing every piece
  cut out of that conversation, ranked by Order
- the takeaway's own page gets a **Watch the full interview** button
- "Read next" on the takeaway page leads with the interview rather than a
  loosely-related sibling

It was saved and read nowhere until Phase 9, which meant the line printed on the
Interview Series page - "each one linked back to the interview it came from" -
was true of nothing on the site.

### Pointer or article - decided per piece

**Pointer.** Leave the body empty, fill in the Onward link. The page shows the
headline, dateline, dek and image, then the button onward.

**Article.** Write the body. The page shows the article. Keep or clear the Onward
link as you like - a finished piece can still point somewhere next.

### Every takeaway has a page, written or not

`/takeaways/<slug>/` renders for everybody, logged in or out. What varies is the
reading column:

| Body    | The page shows                                                                       |
| ------- | ------------------------------------------------------------------------------------ |
| Written | headline, dateline, the article, image, read-next, get-involved links                |
| Not yet | headline, dateline, the dek, image, the onward button, read-next, get-involved links |

Nothing is invented to fill the gap and the page never dead-ends.

**What is different for search engines.** Until a body exists the page is
assembled mostly from fields that also appear on the stream, so while it is
unwritten (`inc/thin-pages.php`) it carries `noindex` and stays out of the
sitemap, core's and Yoast's. Both lift on their own the moment somebody writes a
body. There is no switch to remember.

---

## 4. The two edits to `global.js`

Both already applied.

**One - the search index.** Line 100 was a JavaScript literal holding the
site-search index, including eight interview titles, so publishing an interview
meant editing a `.js` file with nothing to tell you when somebody forgot.

```js
var SITE_INDEX = window.TAE_INDEX || [ … existing literal, unchanged … ];
```

The plugin publishes `window.TAE_INDEX` from real posts. The literal stays as the
fallback, so search still works if the plugin is deactivated.

**Two - `interview_play` never fired.** §12 bound the analytics listener to each
`[data-yt]` element and guarded on `.on` to avoid counting a replay. But §06 sets
`.on` inside its own click handler, and `boot()` runs `lazyVideo` before
`analytics`, so §06's listener always ran first and the guard was true by the time
the analytics one read it. Not under-counted - never sent, on any video, since the
guard was written. It is now one listener on `document` in the **capture** phase,
which reads the flag before §06 writes it and also covers tiles appended from
admin-ajax that a per-element loop never saw.

Nothing else changed - masthead, menu, search overlay, scroll reveals,
click-to-load YouTube, the FLIP filter, contact segments and the partnership rails
are untouched.

---

## 5. Two things worth understanding before editing

### The filter CSS is generated

The category filter is pure CSS - `:has()` plus a sibling combinator, no JS. That
needs one rule per term, so the rules cannot be hardcoded once categories are
editable. `inc/filter-css.php` writes them from the live taxonomy.

The hand-written originals in `global.css:677-681` stay where they are. For the
five seeded slugs they produce identical output - `tests/test-tae.php` asserts
that character for character - and they keep the filter working if the plugin is
deactivated. That assertion is why the `wall` entry survives in
`tae_filter_config()` after the wall itself was deleted.

**A bug this fixed.** `global.css:949` writes:

```css
.tae-interviews .sorts:has(#f-eng:checked) ~ .eps li
```

`.sorts` is inside `.index-head`, and `.eps` is a sibling of `.index-head`, not of
`.sorts`. The combinator never matches, so the Interview Series filter has never
worked. The generated rules scope to `.index-head` instead.

### Filtering refetches

"Earlier episodes" pages twelve at a time, and a CSS filter can only hide what is
in the DOM. Filtering to a category after one page would have shown two results
when seven exist, with nothing to say the answer was partial.

So a chip click refetches page one for that category server-side. Load-more then
pages within whatever category is active. The CSS rules still fire first, which is
what stops the grid flashing wrong tiles mid-request - and with JavaScript off
they are the whole filter, over the first twelve.

---

## 6. Rendering a shortcode twice on one page

`#q`, `#c-all`, `#f-all`, `#stream`, `#tally` and friends are global IDs in the
prototype's markup. The first instance of a view on a page keeps them verbatim, so
`global.js` binds exactly as it always has. Later instances get suffixed IDs, their
own radio-group name and their own scoping class - no wrapper element, because a
wrapper would break the sibling combinator the filter depends on.

---

## 7. Owner-editable links

**Interviews → Links.** Two values the templates used to hardcode:

| Setting          | Default                                     | Used by                        |
| ---------------- | ------------------------------------------- | ------------------------------ |
| YouTube channel  | `https://www.youtube.com/@theaccessexchange` | Subscribe, under the archive   |
| Join The Access Exchange | `/#join`                                    | the CTA at the foot of every episode |

Handoff §23.5 says social links "will be supplied or confirmed as accounts are
finalized", so the one address guaranteed to change was the one written into a PHP
template. Empty field = the shipped default, so a fresh install renders exactly
what the prototype rendered.

---

## 8. What search engines get

`inc/schema.php`, all of it invisible on the page:

- **VideoObject** per episode with a YouTube ID - name, standfirst, upload date,
  embed URL, poster, ISO duration, and the guest as `actor` with their role and
  organisation. Yoast does not know an interview is a video, and the video result
  is the organic surface that matters for an interview series.
- **A default SEO title** - `Episode title - Guest | The Access Exchange` - and a
  **default meta description** taken from the standfirst.

Both defaults defer to anything typed into Yoast by hand. Nothing here overwrites
a real answer; it means the owner only writes one when the generated one is not
good enough, rather than on every episode forever.

---

## 9. Tests

```
php tests/test-tae.php
```

No framework, no WordPress. Generated CSS against the stylesheet's own rules,
instance ID collisions, chapter parsing, link resolution, the guest line, the
reveal stagger, body detection, lead-tile selection, onward-link labelling,
YouTube ID extraction, ISO durations, parent resolution and the link settings'
fallbacks. Exit code 1 on failure.

The static preview has its own suite, which covers the pages this plugin renders
into:

```
python tools/build-static.py && python wordpress/tests/check.py
```

---

## 10. Troubleshooting

**The page prints `[tae_interviews view="archive"]` as text.** The Elementor
shortcode filter is not running. Check the plugin is active.

**`/takeaways/<slug>/` 404s.** Settings → Permalinks → Save. If it still 404s, a
page with the slug `takeaways` is shadowing the post type's rewrite - give the CPT
a different rewrite slug in `inc/post-types.php`.

**The video shows a poster but plays nothing.** Almost always a video ID that is
not a video ID. Since Phase 9 the field takes a pasted URL and extracts the ID, and
saves empty rather than saving something broken - so an empty field after saving
means nothing in what you pasted looked like a YouTube ID.

**Load-more does nothing.** `tae-archive.js` is being minified, combined or
deferred by an optimiser. `README-WORDPRESS.md` §7b lists the exclusion setting for
each of the usual plugins - `tae-archive.js` needs the same treatment as
`global.js`.

**A category chip hides everything.** The interview has no category term. It
renders in every unfiltered listing and is hidden by every filter except All.
Assign a category.

**"From this interview" is not showing.** The takeaways have no parent set. Open
each one and pick the interview under "Came from which interview" - the section
only prints when at least one piece points at that episode.
