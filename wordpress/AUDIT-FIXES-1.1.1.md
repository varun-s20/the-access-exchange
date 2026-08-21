# Audit fixes - plugin 1.1.1

A full read of the plugin PHP, both asset files, every template and every page,
looking for bugs rather than missing features. Fifteen things came out of it.
Twelve were defects, three were limits that would have been reached quietly.

None of it was a launch blocker. Two of them would have failed *silently*, which
is the reason this pass happened before handoff rather than after: a bug the
owner can see is a support message, and a bug they cannot see is a slow leak.

Everything below is in the source. Deploying it is `DEPLOY-1.1.1.md`.

---

## The two that would have failed silently

### 1. Load-more died a day after caching was switched on

`inc/ajax.php`, `inc/shortcodes.php`, `assets/tae-archive.js`

The "Earlier episodes" button and the category chips fetch more interviews over
AJAX. That request carried a nonce, and the nonce reached the browser through
`wp_localize_script` - which prints it inline in the page HTML.

Page HTML gets cached. Nonces expire in 12-24 hours. So every visitor served
from cache after that first day carried a dead nonce, `check_ajax_referer`
rejected them, and the JSON parse failed at the other end. The failure handler
was written for a network blip and did nothing on purpose:

```js
function () { busy = false; }   // "leave what is on screen alone"
```

Right for a blip, wrong for permanent failure. The visitor pressed the button,
nothing moved, and nothing said why. Nobody would have reported it - people
assume the site has no more content.

**Fixed by removing the nonce.** A nonce protects a state change made on
someone's behalf. This endpoint changes nothing, reads nothing private, and
returns the same published tiles the page already rendered to anyone who asked.
Nonce-in-cached-HTML *was* the bug; taking it out removes the whole class of
failure. Every input is still sanitised and the query is still pinned to
published interviews.

The failure branch now also speaks: the button reads "Didn't load - tap to
retry" for four seconds. A button that does nothing reads as a broken site,
which is worse than the failure it hides.

### 2. Chapter jumps inflated the play count

`assets/global.js` §06b and §12

Jumping to a chapter on a video that is already playing has to rebuild the
iframe at the new timestamp, and it does that by clearing the `.on` flag and
re-clicking the poster. But `.on` is the same flag the analytics listener reads
to decide whether a click is a fresh play. Cleared, every chapter jump counted
as another `interview_play`.

One person moving around inside one conversation is not five plays. The number
would have looked healthy and been wrong, which is the worst kind of analytics
error - nobody audits a metric that is flattering.

**Fixed** with a marker §06b sets immediately before it clicks, which §12 reads
and consumes. First play still counts. Rebuilds do not.

---

## The one the owner would have reported as data loss

### 3. Sorting the "Ep" column made interviews disappear

`inc/admin-columns.php`

The interview list has an Ep column, and clicking its header sorted by episode
number. Sorting set `meta_key => tae_episode`, which makes WordPress INNER JOIN
the meta table - so every interview *without* an episode number dropped out of
the list entirely.

The owner clicks a column header and content vanishes. That is a "the site
deleted my interviews" message, at whatever hour they find it.

**Fixed** with a named `meta_query` clause and an `OR NOT EXISTS`. Unnumbered
interviews now gather at one end of the run instead of disappearing.

---

## Correctness

### 4. The video thumbnail for Google could 404

`inc/schema.php`

Every episode emits a `VideoObject` so it can win a video result rather than a
line of grey text. When an episode has no poster image of its own, the schema
fell back to YouTube's `maxresdefault.jpg` - with a comment claiming that one
"always exists".

It does not. YouTube only generates `maxresdefault` for videos uploaded at
1280x720 or better. Below that it 404s, and a broken `thumbnailUrl` can
invalidate the whole rich result - the only reason that schema block is there.

**Fixed** to `hqdefault.jpg`, which is generated for every video that has ever
existed, at 480x360. Comfortably over Google's 60x30 minimum.

### 5. A section with a name pointing at nothing

`templates/interview-cover.php`

The cover section was labelled `aria-labelledby="cover-h"`, but that id lives on
the cover story's heading - and the cover story is the one part of that section
that can be absent while the rest renders. The three slots are independent
checkboxes, so "Most watched" ticked with no "Cover story" ticked is a state an
owner reaches without doing anything strange.

Pointing at an id that is not on the page leaves the section with no accessible
name at all. **Fixed** with a plain `aria-label` fallback.

### 6. Takeaways could not be linked to a draft interview

`inc/meta-boxes.php`

The "Came from which interview" and "Watch next" pickers listed published
interviews only, and the save handler validated against the same list. So the
natural order of work - draft the interview, write its takeaways off the back of
it, publish - lost the link every time, without a word.

**Fixed** by including unpublished interviews, labelled
`(draft - hidden until published)` so the consequence is on screen. They stay
invisible on the front end until they go live, which was already true.

### 7. Filter chips lied about what was on screen

`inc/filter-css.php`, `inc/shortcodes.php`

`[tae_interviews view="archive" category="leadership"]` rendered a
leadership-only grid under a control group insisting nothing was filtered - the
"All" chip was hardcoded as checked. **Fixed** to follow what actually rendered.

### 8. A divider that only appeared under one condition

`inc/shortcodes.php`

`[tae_insights divider="Takeaways"]` printed nothing unless `search="no"` was
also set. The divider is section furniture and belongs to the section whatever
it looks like; only the heading and lede need to be conditional, and only
because the stream prints its own `h1` when the search header is on. **Fixed.**

### 9. Two pager buttons, one destination

`templates/interview-archive.php`

The "Earlier episodes" link anchored to a bare `#episodes`, ignoring the
instance suffix, so a second archive on one page would send its pager to the
first one's grid. Not reachable today. Fixed while it was cheap.

### 10. A helper that would fatal on its fifth caller

`inc/render.php`

`tae_vid()` read `$post->ID` directly while every sibling helper in that file
accepts `int|WP_Post`. All four callers happen to pass objects, so it worked.
**Normalised**, so the fifth one cannot break it.

### 11. Generated pages had no `main` landmark

`templates/single-tae_interview.php`, `templates/single-insight.php`

Every Elementor page sets its container's HTML tag to `main`. The two generated
page types - episodes and takeaways - opened with a plain `div`, so they were the
odd ones out for anyone navigating by landmark. **Both now use `<main>`.** The
class does the styling either way, so nothing moved.

On the takeaway page the "Read next" strip stays outside `main`, which is right -
it is complementary navigation, not the piece you came to read.

### 12. Ninety-nine lines of dead JavaScript

`assets/global.js`

Two modules ran on every page load and could never do anything:

- **§07 prototype forms** bound to `form[data-validate]`. Contact Form 7 took the
  forms over and nothing has carried that attribute since. It also reached for
  `.panel` and `#well`, both of which went with the contact page.
- **§08 home guest rail** bound to `#rail`, which went with the rail in Phase 7.

Neither had a hook left in any page or template. **Deleted** rather than left as
a comment - git has them if the rail is ever designed back in. The file went
1263 → 1164 lines.

---

## Limits that would have been reached quietly

Four numbers in the code that said "stop after this many", none of which warned
when they were hit. Each was raised.

| What | Was | Now | What happened past the old limit |
| ---- | --- | --- | -------------------------------- |
| Header search index | 100 | 500 | Oldest interviews and takeaways stopped being findable |
| Noindex scan for unwritten takeaways | 500 | 2000 | Unwritten ones started appearing in Google as near-empty pages |
| Takeaways stream on Interview Series | 60 | 150 | Item 61 existed at its own URL but never appeared in the section, and the filters and counters could not see it |
| Interview picker in the meta box | 200 | 500 | Older interviews stopped being selectable |

The stream cap was the one worth thinking about, because that section has no
paging - it renders everything at once, since the search box and topic filters
work by hiding what is already on the page. So the cap is also what keeps the
page light.

At roughly two takeaways per interview and one interview a week, 60 is about
seven months. Close enough to be a launch-year problem. 150 is a few years, and
the tiles are small. **Past a few hundred the answer is paging that section, not
another raise** - at that point page weight matters more than completeness does.

---

## Deliberately not changed

**The editor.** Interviews and takeaways open in the classic editor because
`show_in_rest` is not set. Switching to the block editor would demote the
"Interview details" box - guest name, YouTube link, takeaways, chapters, slot
checkboxes - to a collapsed panel below the block canvas. Those fields are the
whole job on that screen and the body is optional. Classic is the better screen
here, and the owner is being trained on it.

**Root-relative links** (`/interview-series/` and friends) throughout the
templates and pages. Correct for a root install, which is what this is. They
would break under a subdirectory install - worth knowing before anyone tries
staging under `/new/`.

**The unreachable `select` case** in the meta-box save handler. It pairs with
`post_select`, and removing half a symmetric pattern costs more in confusion
than a dead switch branch costs at runtime.

**Yoast as a dependency** for episode SEO titles and descriptions. `inc/schema.php`
generates them through `wpseo_title` and `wpseo_metadesc`, so no Yoast means no
generated episode titles. Real, undeclared, and fine - Yoast is in the plan.

---

## Verification

Everything above was checked before it was called done:

```
php tests/test-tae.php            -> all passed
php -l  (25 plugin files)         -> clean
node --check  (both JS files)     -> OK
python tools/build-static.py      -> rebuilt
python wordpress/tests/check.py   -> all checks passed
```

The zip was rebuilt afterwards and byte-compared against the source tree: 25
files, none missing, none differing.

`tae-archive.js` changed, and `TAE_VER` is its cache-buster, so the plugin version
went 1.1.0 → 1.1.1 in both places.

---

## Also in this release, not from the audit

**Mobile footer.** `.foot-cols` and `.foot-bot` were flex rows that wrapped. With
a 144px minimum per column a phone fits two, so four columns became two centred
rows and the legal line stayed inline. Both now stack and left-align below 559px.

**Misaligned assurance points.** `.assure div:first-child{padding-left:0}` sat
outside its media query, so it applied at every width. Once the row collapses to
one column the first point was flush left and the rest sat indented. The side
padding moved inside the media query, where it belongs - it exists to hold copy
off the dividing rules, which only exist in the row. Fixed on the Interview
Series page and on Universities, which had the identical fault.

**Build credit** in the footer bottom row - a bare `<a href>` with no `rel`,
which is dofollow by default. No `target="_blank"`, so no `noopener` needed.
