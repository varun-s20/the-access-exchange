# The Access Exchange — content plugin design

Date: 2026-08-05
Status: approved, ready for implementation planning

---

## 1. Problem

The six WordPress page files in `wordpress/` are static HTML pasted into Elementor
HTML widgets. Every interview, every insight tile, every count and every entry in
the site-search index is hardcoded markup. Publishing one interview today means
editing four files by hand:

| File | What has to change |
|---|---|
| `wordpress/index.html` | §04 feature block, §05 wall grid, §08 guest rail, §11 watch poster |
| `wordpress/interview-series.html` | §01 cover + minors + most-watched, §03 archive tiles |
| `wordpress/insights.html` | §02 stream tile, §01 tally, §02 sidebar counts |
| `wordpress/assets/global.js` | `SITE_INDEX` array at line 100 |

That last one is the sharpest edge: site search is driven by a JavaScript literal
that already contains eight interview titles. Nobody will remember to update it,
and there is no failure signal when they do not — search just quietly goes stale.

The client needs to publish content without touching code.

## 2. Goals

Build a self-contained WordPress plugin that:

1. Registers content types for interviews and insights with a hand-rolled admin UI
   (no ACF, no other paid dependency).
2. Exposes that content through shortcodes, pasted into the existing Elementor
   HTML widgets in place of the hardcoded blocks.
3. Emits markup byte-identical to the current prototype, so `global.css` never
   changes and the design cannot regress.
4. Derives every count, tally and search-index row from real queries.

## 3. Non-goals

Explicitly out of scope, decided during design:

- **Static prose stays in Elementor.** Home §01–§03, §06, §07, §09, §10;
  interview-series §02; all of About; partnerships §01–§06; contact panel intros.
  Roughly 120 fields of copy that changes once a year, and editing long-form prose
  in a `<textarea>` is worse than editing it where it already lives.
- **FAQ stays hand-written.** The partnerships accordion (§07, six items) and the
  inline `FAQPage` JSON-LD on both partnerships and interview-series remain manual.
  Accepted risk: the accordion and its schema block live in the same file but are
  edited separately, so they will eventually drift.
- **Interviews get no single page.** Interview cards keep their existing fixed
  destination, `/interview-series/#episodes`, and the video plays inline through
  the existing click-to-load YouTube handler (`global.js` §06).
- **No Elementor widgets, no Gutenberg blocks, no REST endpoints, no theme-side
  template overrides.** Shortcodes only.

---

## 4. Data model

Two custom post types, two taxonomies. Both CPTs are registered by the plugin, so
deactivating it hides the content without destroying it.

### 4.1 `tae_interview`

```php
'public'              => false,
'show_ui'             => true,
'publicly_queryable'  => false,
'has_archive'         => false,
'menu_icon'           => 'dashicons-video-alt3',
'supports'            => [ 'title', 'thumbnail', 'page-attributes' ],
```

`page-attributes` is what exposes the Order field, which drives every curated
sequence in this spec.

| Meta key | Type | Renders as |
|---|---|---|
| `tae_guest_name` | text | `.who`, `.epc-who`, `.g`, `.face h3`, `.byline` |
| `tae_guest_role` | text | appended after the name; alone in `.role` |
| `tae_episode` | int | `Ep 41` in `.epc-meta`, `Episode 42` in the cover |
| `tae_duration` | text, `MM:SS` | `.vid-dur`, `.dur`, cover meta line |
| `tae_youtube` | text, video ID | `data-yt` attribute |
| `tae_standfirst` | textarea | `.stand` on the cover and the home feature |
| `tae_rail_line` | text | the one-line summary in `.face p` (home §08) |
| `tae_chapters` | repeating rows: `time` + `label` | `.chapters ul` (home §04) |
| `tae_slot` | select: `—`, `cover`, `minor`, `feature`, `watch` | which hero slot this occupies |
| `tae_most_watched` | checkbox | interview-series §01 right rail |

Derived, never entered:

- date → `post_date`, printed through `<time datetime="Y-m-d">`
- sequence → `menu_order`
- poster → featured image
- `alt` text → the attachment's alt field

**`tae_slot` semantics.** At most one interview may hold `cover`, `feature` and
`watch`; the template takes the first by `menu_order` and ignores the rest. `minor`
is the pair in the interview-series left rail — the template takes the first two.
An interview with slot `—` still appears in the wall, archive and stream.

### 4.2 `tae_insight`

```php
'public'   => true,
'rewrite'  => [ 'slug' => 'insights', 'with_front' => false ],
'has_archive' => false,
'supports' => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ],
```

`has_archive` is false because `/insights/` is an Elementor page, not an archive.

| Meta key | Type | Renders as |
|---|---|---|
| `tae_dek` | textarea | `.sub` on the lead tile only |
| `tae_byline` | text | `.who` |
| `tae_link` | URL, optional | the tile's `href` |
| `tae_start_here` | checkbox | insights §03 |

Two layout variants are derived, not entered:

- `it--text` — applied when the post has no featured image
- `it--lead` — applied to the first item in the stream

**Link resolution.** `tae_link` set → the tile links there. Empty → the tile links
to the insight's own permalink, `/insights/<slug>/`.

### 4.3 Taxonomies

**`tae_category`** on `tae_interview`. Terms seeded on activation, with slugs that
must match the existing `data-cat` values in the markup:

| Slug | Name |
|---|---|
| `engineering` | Engineering |
| `product` | Product |
| `data` | Data & AI |
| `design` | Design |
| `breaking-in` | Breaking in |

Names are freely editable in wp-admin. Slugs are load-bearing — see §6.1.

**`tae_topic`** on `tae_insight`. Terms: `breaking-in` (Breaking in),
`levelling-up` (Levelling up), `on-campus` (On campus).

The insights sidebar has five buttons — All, Interviews, Breaking in, Levelling up,
On campus. "Interviews" is not a term; it is `post_type = tae_interview`. The stream
template stamps `data-topic="interview"` on every interview tile so `global.js` §09
filters it without knowing the difference.

### 4.4 Image sizes

One featured image per post. The plugin registers the crops the markup needs and
WordPress generates them:

```php
add_image_size( 'tae-tile',   640,  360, true );   // archive tile, watch poster
add_image_size( 'tae-cover', 1400,  875, true );   // interview-series cover story
add_image_size( 'tae-minor',  560,  420, true );   // interview-series left rail
add_image_size( 'tae-card',   620,  775, true );   // home wall card
add_image_size( 'tae-rail',   700,  930, true );   // home guest rail portrait
add_image_size( 'tae-stream', 700,  440, true );   // insights stream tile
add_image_size( 'tae-lead',  1400,  620, true );   // insights lead tile
```

All hard crops, centred. The 16:9 → 3:4 jump for `tae-rail` is the one that can cut
off a head; the fix is WordPress's built-in featured-image crop editor, per image,
not another upload field.

Templates emit `wp_get_attachment_image()` so `srcset` and `sizes` come for free —
an improvement on the current fixed-width Pexels URLs.

---

## 5. Shortcodes

```
[tae_interviews view="wall|archive|cover|featured|rail|watch" count="12" category=""]
[tae_insights   view="stream|start" count="14"]
```

| Shortcode | Replaces |
|---|---|
| `[tae_interviews view="featured"]` | `index.html` §04, lines 125–150 |
| `[tae_interviews view="wall"]` | `index.html` §05, lines 153–254 |
| `[tae_interviews view="rail"]` | `index.html` §08, lines 314–363 |
| `[tae_interviews view="watch"]` | `index.html` §11, lines 399–415 |
| `[tae_interviews view="cover"]` | `interview-series.html` §01, lines 75–126 |
| `[tae_interviews view="archive"]` | `interview-series.html` §03, lines 160–362 |
| `[tae_insights view="stream"]` | `insights.html` §01 tally + §02, lines 46–208 |
| `[tae_insights view="start"]` | `insights.html` §03, lines 211–225 |

Attributes:

- `count` — posts per page. Default 12 for interview views, 14 for the stream.
- `category` — restrict to one `tae_category` slug. Empty means all.

Elementor HTML widgets do not run shortcodes by default. The
`elementor/widget/render_content` filter documented in `CF7-SMTP.md` §3 is already
required for the six Contact Form 7 forms, so this adds no new setup step.

### 5.1 Per-instance IDs

`#q`, `#c-all`…`#c-brk`, `#f-all`…`#f-brk`, `#stream`, `#tally`, `#tallyWord`,
`#empty`, `#topics`, `#hunt`, `#clear` are all global IDs in the current markup.
Rendering a shortcode twice on one page would duplicate them and break both the
CSS filter and `global.js` §09.

Rule: the plugin keeps a static render counter. The first instance of a given view
on a page emits the legacy IDs verbatim, so `global.js` binds exactly as it does
today. Every later instance suffixes `-2`, `-3`, and gets its own generated CSS
filter rules.

`README-WORDPRESS.md` §8 already flags that home and insights both use `#q` on
different pages. That stays true and stays fine.

---

## 6. The two mechanisms that need care

### 6.1 Generated filter CSS

`global.css:677–681` and `:949–953` hardcode the mapping from radio ID to category
slug:

```css
.tae-home .filters:has(#c-eng:checked) ~ .grid .card:not([data-cat="engineering"]),
```

Editable categories break this. The plugin generates the equivalent block from the
live `tae_category` terms and prints it once per page, inside the first shortcode's
output:

```php
foreach ( $terms as $term ) {
    $rules[] = sprintf(
        '%s:has(#%s:checked) ~ %s:not([data-cat="%s"])',
        $scope_filters, $radio_id( $term ), $scope_grid, $term->slug
    );
}
printf( '<style>%s{display:none}</style>', implode( ',', $rules ) );
```

`$scope_filters` / `$scope_grid` are per-view: `.tae-home .filters` / `.grid .card`
for the wall, `.tae-interviews .sorts` / `.eps li` for the archive.

`$radio_id()` resolves through the same instance counter as §5.1. On the first
instance it returns the legacy ID for a seeded slug — `engineering` → `c-eng` on the
wall, `f-eng` on the archive — so the generated rules are character-for-character
identical to the stylesheet's own. A term the map does not know, or any instance
after the first, gets a generated ID instead.

The stylesheet's own hardcoded rules stay where they are. They are harmless — they
target the same slugs and generate identical behaviour for the five seeded terms —
and they keep working if the plugin is ever deactivated.

### 6.2 Load-more, and why the filter had to change

The archive pager loads 12 at a time. A CSS-only filter can only hide what is in the
DOM, so filtering to "Design" after one page load would show 2 results when 7 exist.

Resolution, agreed during design:

- Clicking a category chip fires an AJAX request for page 1 of that category and
  replaces the grid contents. Counts and pagination are then always correct.
- "Earlier episodes" fires the same endpoint for page 2, 3… of whatever category is
  active.
- The button hides itself when `has_more` comes back false.
- The generated CSS rules stay in place as the no-JS fallback. Without JavaScript a
  visitor still gets a working filter over the first 12 tiles — degraded, not broken.

Endpoint: `wp_ajax_tae_load_interviews` and `wp_ajax_nopriv_tae_load_interviews`.
Returns `{ html: '<li>…</li>…', has_more: bool, found: int }`. Nonce passed through
`wp_localize_script`. Newly appended tiles are handed to the existing
`IntersectionObserver` so scroll reveals still fire on them.

**Home wall search box.** `index.html:171` has an `#q` search input that binds to
nothing — `global.js` §09 guards on `#stream`, which the home page does not have. It
has never worked. Since the AJAX endpoint exists anyway, the wall template wires it
to the same endpoint with a `s` parameter and a 110 ms debounce, matching the
insights search feel. Assumption, not a request; say so if you would rather it be
removed than fixed.

### 6.3 Site search index

`inc/site-index.php` hooks `wp_enqueue_scripts` at priority 21, after the child
theme registers `tae-global` at priority 20:

```php
wp_add_inline_script( 'tae-global', 'window.TAE_INDEX=' . wp_json_encode( $rows ) . ';', 'before' );
```

`$rows` = the eleven static page/anchor rows, still hardcoded in PHP, plus one row
per published interview and insight, built from title, guest name and role.

This requires the **only** edit to `global.js`. Line 100 becomes:

```js
var SITE_INDEX = window.TAE_INDEX || [ /* …existing literal, unchanged… */ ];
```

Keeping the literal as the fallback means the site search still works if the plugin
is deactivated. Everything else in `global.js` — masthead, menu, search overlay,
scroll reveals, click-to-load YouTube, the insights FLIP filter, contact segments,
partnership rails — is untouched.

Load-more and the wall search live in the plugin's own `assets/tae-archive.js`,
enqueued with `tae-global` as a dependency.

---

## 7. The insight single template

Choosing "link field, falling back to a single page" means insights need an article
template the prototype never designed.

It already exists. `global.css:1035` onward defines the About broadsheet —
`.sheet`, `.sheet-head`, `.spread`, `.copy`, `.margin`, `.dateline`.
`single-insight.php` reuses that markup:

```
<div class="tae tae-about">          ← the wrapper the CSS is scoped to
  <section class="sheet">
    <div class="shell">
      <div class="sheet-head">
        <h1><?php the_title(); ?></h1>
        <p class="dateline lab">…byline · date…</p>
      </div>
      <div class="spread">
        <div class="copy"><?php the_content(); ?></div>
      </div>
    </div>
  </section>
</div>
```

The `.margin` aside is omitted — it has no source field, and the layout holds
without it. Zero new CSS.

The template must render inside the theme's `get_header()` / `get_footer()`, so the
Elementor Theme Builder header and footer still apply.

---

## 8. Seed importer

`inc/importer.php` adds **Tools → Import TAE demo content**. One button, guarded by
a `tae_seeded` option so it cannot run twice.

Source data is `data/seed.php`, returning a PHP array of 26 records transcribed from
the current markup — 12 interviews and 14 insights, every field filled. A PHP array,
not an HTML parser: the markup it would parse is the markup this plugin is about to
replace.

Images are pulled from their Pexels URLs with `media_sideload_image()` and attached
as featured images.

**Failure reporting.** Sideloading needs outbound HTTP from the host. On a locked-
down host every image silently fails and the importer would otherwise report 26
successful imports with no pictures. It must count image failures separately and
print them: `26 posts created, 26 images failed — check outbound HTTP`.

The file is disposable. Delete `inc/importer.php` and `data/seed.php` after the run.

---

## 9. File layout

```
tae-content/
├── tae-content.php              header, constants, requires, activation hook
├── inc/
│   ├── post-types.php           CPTs, taxonomies, term seeding, image sizes
│   ├── meta-boxes.php           metaboxes, sanitisation, save handlers
│   ├── shortcodes.php           registration, attribute parsing, view dispatch
│   ├── query.php                the shared WP_Query builder
│   ├── ajax.php                 tae_load_interviews
│   ├── filter-css.php           :has() rule generation
│   ├── site-index.php           window.TAE_INDEX
│   └── importer.php             Tools page (disposable)
├── data/seed.php                26 records (disposable)
├── assets/tae-archive.js        load-more, chip refetch, wall search
└── templates/
    ├── interview-wall.php
    ├── interview-archive.php
    ├── interview-archive-item.php   ← shared by the template and the AJAX handler
    ├── interview-cover.php
    ├── interview-featured.php
    ├── interview-rail.php
    ├── interview-watch.php
    ├── insights-stream.php
    ├── insights-start.php
    └── single-insight.php
```

`interview-archive-item.php` is split out deliberately: the initial render and the
AJAX response must produce identical markup, and one file is the only way to
guarantee that.

`query.php` exists for the same reason — the wall, archive, cover, stream and AJAX
handler all build nearly the same query, and the ordering and `tae_slot` exclusion
rules should be written once.

---

## 10. Verification

Non-trivial logic, so it leaves runnable checks behind:

1. **Markup fidelity.** A script renders each shortcode against the seeded content
   and diffs the output against the corresponding block extracted from the current
   `wordpress/*.html`, ignoring whitespace and image URLs. This is the check that
   protects the design, and it is the one that matters most.
2. **Filter CSS generation.** Given a fixed term list, assert the generated selector
   string matches the hand-written rules at `global.css:677–681`.
3. **ID suffixing.** Render `[tae_interviews view="wall"]` twice; assert the second
   emits no ID that appears in the first.
4. **Link resolution.** Insight with `tae_link` set → that URL. Empty → permalink.

## 11. Risks

1. **`/insights/` page versus the CPT rewrite slug.** A page at `/insights/` and a
   CPT rewriting to `insights/<slug>/` do coexist in WordPress, but this is the
   classic place it goes wrong. Verify immediately after the CPT registers and
   before any template work — if it conflicts, the whole `tae_link` fallback design
   changes and it is cheaper to know on day one.
2. **`media_sideload_image()` outbound HTTP** — see §8.
3. **Elementor shortcode filter.** Without the `elementor/widget/render_content`
   filter the pages print `[tae_interviews …]` as literal text. Already required for
   the CF7 forms, but it is the first thing to check if a page renders shortcode
   source.
4. **Caching.** The load-more endpoint goes through `admin-ajax.php`, which most
   page caches leave alone, but aggressive optimisers combine and defer
   `tae-archive.js`. `README-WORDPRESS.md` §7b already documents the exclusion list
   for `global.js`; `tae-archive.js` needs the same treatment.

## 12. Open assumptions

Stated here rather than blocking; flag any that are wrong:

- The home wall search box gets wired up rather than removed (§6.2).
- The interview-series "Subscribe" button and the YouTube channel URL stay
  hardcoded — they are chrome, not content.
- The insights `.band` newsletter element referenced at `global.js:520` does not
  exist in the current markup and is not being added.
- Interviews with no `tae_category` term still render in the wall and archive, and
  are hidden by every category filter except "All".
