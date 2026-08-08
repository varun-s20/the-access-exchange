# The Access Exchange - build plan to launch

Source of truth: `Final The_Access_Exchange_Website_Developer_Handoff_Revised.pdf`
Current build: `wordpress/` (6 pages, Elementor + `tae-content` plugin)
Target: 8 core pages + Privacy + Terms, on the temporary WordPress domain.
Hosting/DNS for theaccessexchange.com is **out of scope for now** - build and
review on the temp domain, migrate later.

## Previewing the build

WordPress page bodies are full of shortcodes, so the real build cannot be looked
at outside WordPress. The repo root holds a browsable copy generated from the
same sources:

```
python tools/build-static.py        # regenerate the preview
python wordpress/tests/check.py     # 378 assertions over both
```

Open `index.html` from the repo root. Any static server works too, but plain
`file://` is enough - every link is relative.

**The direction is one-way.** `wordpress/` is the source of truth; the preview is
derived from it. Never fix something in the root files - the next build
overwrites them. If the preview looks wrong, the WordPress source is wrong.

What the generator guarantees, and what `check.py` asserts:

- `assets/global.css` and `assets/global.js` are byte-identical copies
- the chrome is `header.html` / `footer.html` verbatim, differing only in
  relative links and `aria-current` (which WordPress applies at runtime)
- page bodies come from `wordpress/<page>.html` with shortcodes expanded by a
  registry that mirrors each PHP template
- an unhandled shortcode is a hard error, never a silent gap

**Every page is now generated from the WordPress source** - the `body="root"`
fallback is unused, and Privacy and Terms are the only stubs left (Phase 7).

From Phase 2 on, every phase updates the WordPress source and the preview
together: add the module's static renderer to the shortcode registry in the same
change that adds its PHP template.

## Decisions taken

| Question           | Decision                                                                                                              |
| ------------------ | --------------------------------------------------------------------------------------------------------------------- |
| Hosting            | Deferred. Build on existing temp WordPress domain.                                                                    |
| `/insights/` page  | Delete. Stream folds into Interview Series as "Takeaways". `tae_insight` CPT survives, gains a parent-interview link. |
| Interview episodes | Real pages at `/interviews/{slug}/`. CPT goes public, single template added.                                          |
| Navigation         | Visible 7-item nav on desktop + persistent GET INVOLVED. Hamburger on mobile only.                                    |

---

## Phase 0 · Foundation - DONE

Verify with `python wordpress/tests/check.py` - 378 assertions:
palette tokens, measured contrast, mobile masthead geometry, nav architecture,
retired slugs, the handoff's own copy verbatim, and every class on a page having
a rule behind it. Re-run after every phase; add a block per phase.

Landed:

- Palette swapped to the handoff four, with `--paper-3`, `--ink-2`, `--ink-3`
  derived. `--ink-3` was darkened to `#736F68` so body-sized secondary text
  clears AA - the old `#83837A` never did.
- `.tae-interviews` was re-declaring `--paper-2`, `--paper-3` and `--ink-3` with
  the old cool greys, which would have left that page on the original palette
  after the swap. Warm equivalents in, the `--ink-3` override deleted.
- Primary nav strip in the masthead from 1080px up, collapsing into the menu
  panel below. GET INVOLVED is persistent at every width and scroll position.
- Mobile wordmark moved off centre to sit beside the hamburger, because the CTA
  now stays on mobile and the two collided at 360px.
- Footer rebuilt around the eight pages, Houston listed, one gold divider.
- Site-search index updated in both places: the `global.js` literal and
  `inc/site-index.php`, which overrides it when the plugin is active.

Deferred on purpose: `.tae-partnerships` → `.tae-universities` rename and the
empty `.tae-guests` / `.tae-experiences` / `.tae-coaching` scopes. Both are
Phase 4/5/6 work - renaming ~300 lines now, or scaffolding empty blocks for
pages that do not exist, buys nothing today.

**Four nav links 404 until their phase lands**: `/guests-partners/`,
`/universities/`, `/experiences/`, `/coaching/`, plus `/get-involved/`,
`/privacy/` and `/terms/`. Expected mid-build - the alternative was wiring the
header to old slugs and editing it twice.

---

## Phase 1 · Home - DONE

All nine sections of handoff §8, in its order. Sections 7 and 8 of the brief
(Live Experiences, Coaching) share the two-panel split component, so the page
carries eight blocks.

| §   | Section                         | Built from                                                           |
| --- | ------------------------------- | -------------------------------------------------------------------- |
| 01  | Hero + cinematic band           | `.thesis`, new `.hero-visual`                                        |
| 02  | Why The Exchange                | `.gap`                                                               |
| 03  | Interview Series                | `.series` + `[tae_coming_soon]` / `[tae_interviews view="featured"]` |
| 04  | What Moves Through The Exchange | `.standard .tenets`                                                  |
| 05  | Universities                    | `.campus`, steps repurposed as the four engagement formats           |
| 06  | Leaders & Corporate Partners    | `.doors`                                                             |
| 07  | Live Experiences + Coaching     | `.exchange .split`                                                   |
| 08  | Email capture                   | `.signal` + CF7                                                      |

**Launch state retires itself.** New `[tae_coming_soon until="feature|any"]`
shortcode prints the pre-launch card and returns nothing once an interview holds
the Featured slot - at which point the featured module underneath takes the same
position. Exactly one of the two ever renders. The handoff requires the owner to
move past the coming-soon state without a developer; publishing the interview is
now the entire action. `until="any"` is there for the Interview Series page in
Phase 2.

Also landed: hero band sized by `aspect-ratio` so swapping the `<img>` for
production footage cannot shift the fold; a `.tae-home` CF7 submit rule (the
page had none, so the opt-in button would have rendered as a raw form control);
`.sub` renamed to `.form--join` since CF7 now supplies that markup.

Dropped from home: the interview wall, the guest rail, the founder quote and the
watch poster. None appear in the handoff's homepage, and three of the four would
render empty at launch. Their CSS is parked, not deleted - see the PARKED note
at the foot of the home section in `global.css`. Phase 2 decides whether the
wall and rail move to the Interview Series page; Phase 7 deletes whatever is
still unused.

Left as placeholders, by design: `REPLACE_ID_JOIN` for the opt-in form (Phase 7
creates the forms) and Pexels hotlinks for imagery (Phase 7 self-hosts).

---

## Phase 2 · Interview Series - DONE

**Interviews are pages now.** `tae_interview` went `public`, gained `editor` and
`excerpt`, and lives at `/interviews/{slug}/` behind a new
`templates/single-tae_interview.php`. It carries every field the handoff's
episode template lists: guest name, professional title, organization, portrait,
title, hook, YouTube embed, key takeaways, chapters, related clips and written
takeaways, social sharing, the related interview and the Join CTA.
`tae_destination()` now returns the permalink, so every card, rail and feature
across the site links at the conversation instead of an archive anchor.

New fields: `tae_org`, `tae_takeaways` (one per line, with a parser that
tolerates pasted bullets), and `tae_parent` on insights - a `post_select` that
only stores an id that really is a published interview. There is deliberately no
"related interview" field: `tae_related()` already existed and picks by shared
topic, so the admin has nothing to maintain.

**Chapter jumps without breaking the facade.** A chapter sets the start time and
then presses play, so the rule that nothing is requested from youtube.com until
a deliberate click still holds. `&start=` is the only change to the facade.

**Everything retires its own launch state.** The page is built for a site with
zero interviews and turns itself on as content arrives:

| Module                            | Appears when                            |
| --------------------------------- | --------------------------------------- |
| `[tae_coming_soon until="any"]`   | gone once _any_ interview is published  |
| `[tae_interviews view="cover"]`   | an interview holds the Cover story slot |
| `[tae_interviews view="archive"]` | the first interview is published        |
| `[tae_insights view="stream"]`    | the first takeaway is published         |

Section furniture - the "The archive" divider, the Takeaways heading and lede -
moved out of the page and into shortcode attributes, because a divider reading
"The archive" above nothing is worse than no archive. `tae_divider()` and
`tae_stream_head()` print them, so they vanish with the module they introduce.

**Insights folded in.** `insights.html` deleted, the `.tae-insights` page scope
retargeted to a `.tae-takeaways` section scope (88 selectors, no rules moved),
and the CPT rewritten to `/takeaways/`. The stream gained `search="no"` so an
embedded copy does not bring a second `<h1>` and a second search field with it.

**Taxonomy swapped** to `leadership · founders · industry · career · campus`
across all six places that carried the old slugs, including the PHP test
harness's stub and the hand-written CSS fallback. Owners can still rename or add
freely in wp-admin - that was never the developer-dependent part.

Also: `.soon` was promoted from `.tae-home` to a shared `.tae` component now
that two pages print it, and the guest form left this page - Guests & Partners
owns it in Phase 3, so the section links there instead of carrying a second copy.

### Not verified

The populated states are unverified. I cannot run PHP or WordPress here, so
cover / archive / takeaways / episode pages are correct by construction and
assertion, not by having been rendered. Publish one interview on the temp domain
and check that page first.

### Original scope notes

#### Page

- Hero: "Interviews worth carrying forward." + coming-soon + JOIN THE EXCHANGE
- Cover story / rail / wall modules kept, copy rewritten
- Insights stream module lands here as **Takeaways**
- Editorial + cinematic: guest imagery, episode titles, concise descriptions

### Plugin - `wordpress/plugins/tae-content/`

`inc/post-types.php`

- `tae_interview`: `public => true`, `publicly_queryable => true`,
  `rewrite => [ 'slug' => 'interviews' ]`, `supports` gains `editor` + `excerpt`
- `tae_insight`: keep, drop the top-level page assumption

`inc/meta-boxes.php` - new fields on `tae_interview`

- `tae_org` - Organization
- `tae_takeaways` - Key takeaways, one per line (same textarea pattern as chapters)
- `tae_related` - Related / next interview (post ID select)
- on `tae_insight`: `tae_parent_interview` - the interview this takeaway came from

New `templates/single-tae_interview.php`

- cover image, guest name / title / organization, YouTube embed (facade until
  play - the existing no-request-until-play behaviour stays), hook, key
  takeaways, chapters, related clips, social sharing, related/next interview,
  Join The Exchange CTA

### Taxonomy

Categories are **owner-editable in wp-admin, no developer needed**.
`inc/filter-css.php:104` reads live terms via `get_terms()` and generates the
chips and the `:has()` filter rules per request; an unknown slug falls back to an
md5-derived radio ID at `:119`. Add, rename, re-slug or delete freely.

`TAE_CATEGORIES` (`inc/post-types.php:15`) is only the **activation seed** - it
creates terms that do not exist yet and leaves existing ones alone.

Changing the seeded defaults off the tech set is a one-time edit in five places:

| File                                    | What                                                             |
| --------------------------------------- | ---------------------------------------------------------------- |
| `inc/post-types.php:15`                 | the seed list                                                    |
| `assets/global.css:677-681`             | hardcoded wall filter - plugin-deactivated fallback              |
| `assets/global.css:949-953`             | same, archive filter                                             |
| `inc/filter-css.php:35-55`              | pretty radio IDs (`c-eng`, `f-eng`) keeping the fallback in sync |
| `tests/test-tae.php:105-109`            | asserts the generated stylesheet exactly                         |
| `data/seed.php`, `inc/importer.php:252` | demo rows                                                        |

Proposed defaults: `leadership` · `founders` · `industry` · `career` · `campus`
(Leadership · Founders & Builders · Industry & Craft · Career & Transitions ·
On Campus).

### Delete

`wordpress/insights.html`. Add a 301 to `/interview-series/` at migration time.

---

## Phase 3 · Guests & Partners - DONE

New page at `/guests-partners/`, `.tae-guests`. Two audiences, one page: the hero
routes a leader to `#guest` and an organisation to `#corporate`, so neither has
to read the other's section. Every line the handoff dictated is verbatim,
including the key line and all four guest values.

**Two forms, two notification categories.** `CF7-SMTP.md` §4.8 and §4.9, with
every field the brief lists. Subjects are prefixed `GUEST -` and `CORPORATE -`,
which is what makes "separate notification/category" a single inbox filter rather
than a wish. Both have Mail(2) on - a guest who has just written several
paragraphs about themselves is the clearest case for the confirmation the brief
asks for. §4.6 "Be a guest" is marked RETIRED: its route left Interview Series,
which now links here instead of carrying a second copy.

**The form CSS was shared instead of copied.** `.tae-contact`, `.tae-interviews`
and `.tae-partnerships` each carry a near-identical form block differing only in
gap, fill, border, radius and textarea height. Rather than add a fourth, those
five became tokens on a `.tae .form` base and the new page sets them. The three
legacy copies are untouched and still win on their own pages - consolidating them
would mean re-verifying three pages I cannot render, whereas consolidating at the
point of the fourth copy costs nothing. Phases 4 and 6 use the base; Phase 7
deletes the legacy three.

The preview forms are inert - no action, no method. A preview form that silently
posted nowhere would be worse than one that plainly does not submit.

### Original scope notes

- Hero: "Bring a perspective worth hearing."
- Key line: "Your title tells us where you are. We're interested in what you know now."
- Guest value: Thought Leadership · Legacy · Reach · Impact
- Corporate section + options: Sponsor an Interview · Nominate a Leader · Build a Partnership
- **Guest form**: name, title, organization, email, LinkedIn, location, areas of
  expertise, what perspective would you bring
- **Corporate form**: name, title, organization, work email, company website,
  partnership interest, leader being nominated, what would you like to explore

The `REPLACE_ID_BE_A_GUEST` placeholder in `interview-series.html` retires here.

---

## Phase 4 · Universities & Institutions - DONE

`/university-partnerships/` → `/universities/`, scope `.tae-partnerships` →
`.tae-universities` (125 selectors - the rename Phase 0 deferred to the phase
that rewrote the page). **Add a 301 from the old slug at migration.**

The editorial "working guide" structure survives - outline rail, numbered
sections, FAQ accordion, dark conversion band. The content is handoff §11: the
four engagement formats replace the old six-item pick-list, and the
twelve-week term gantt is gone along with the cohort model it illustrated.
Its JS is guarded (`if (track && …)`), so the outline rail is unaffected.

**The university form has the six fields the handoff names** (§16): institution,
audience, requested format, preferred timing, expected attendance, goals.
Timing and attendance are optional - the brief says "if known", and demanding a
date from someone still deciding whether to ask is how an inquiry form loses an
inquiry. `CF7-SMTP.md` §4.10, notification category `UNIVERSITY -`. §4.5 marked
RETIRED.

**The inverse form joined the shared base.** This page's form sits on a dark
band, which was the argument for its own copy. Adding `--field-label`,
`--field-ph` and `--field-ink` to the base made it four token values instead -
so the legacy set is down from three copies to two.

Two bugs the checks caught:

- The line-based deletion of the term-gantt CSS cut opening lines and left 19
  lines of orphaned continuations. The brace-balance assertion failed
  immediately; a stylesheet that fails to parse loses everything after it.
- `.tae-universities .btn` was styled for the dark band - paper fill on paper
  text. The new light hero would have rendered two invisible buttons. The
  inverse rule is now scoped to `.cta-band`.

New assertion worth keeping: the FAQ schema questions and the accordion
questions must match. The page comment promised they stay in sync; that promise
is now enforced rather than hoped for.

### Original scope notes

Slug `/university-partnerships/` → `/universities/`.

Structure largely reusable, copy replaced. "Pick the parts that fit" becomes the
four formats: Leadership Talk + Q&A · Moderated Leadership Discussion · Industry
Session · Custom Campus Experience. Twelve-week-programme and cohort framing goes.

University CF7 form gains: institution, audience, requested format, preferred
timing, expected attendance, goals/topics.

---

## Phase 5 · Experiences - DONE

New page at `/experiences/`, `.tae-experiences`. Handoff §12 is the shortest
section in the brief and asks for one thing specifically: "sophisticated event
and room imagery, compelling section headlines and clear inquiry pathways". So
this is the most image-led page on the site - six photographs, type carrying the
argument and the photography carrying the feeling.

Four sections: hero + cinematic band, the four kinds of experience as large
scrimmed image cards, an editorial statement on why the room matters, and three
inquiry pathways.

**Two deliberate absences**, both worth stating because they look like gaps:

- **No form of its own.** §16 lists seven forms and none is an events form. The
  three pathways route to `/universities/#enquire`, `/guests-partners/#corporate`
  and `/get-involved/` rather than inventing an eighth. The checks assert those
  anchors exist on the pages they point at.
- **No events CMS.** The brief describes the _kinds_ of experience on offer, not
  a calendar of dated events, and §20 names only the Interview Series CMS as a
  requirement. A listings page later is a new post type, not a rework of this
  one. Also why there is no Event schema - Event markup without a date or a
  location is invalid.

`.hero-visual` was promoted from `.tae-home` to a shared `.tae` component, the
same move `.soon` got in Phase 2, now that a second page opens with one.

### Original scope notes

Hero: "The Exchange goes beyond the screen." Event and room imagery, section
headlines, inquiry pathway into the Get Involved routing.

---

## Phase 6 · Coaching, About, Get Involved - DONE

**Coaching** (`/coaching/`, new). Two offerings with identical shape - a
statement and the form that answers it - so the page is almost entirely the
shared panel pair. All six focus areas from §13 verbatim. Anchors `#coaching`
and `#training`, two separate inquiry paths as the brief asks by name.

**About** (`/about/`, rewritten). §14 copy: the three "right \_\_\_" sentences, the
philosophy line, and the four directions experience travels, set in the existing
alternating broadsheet layout. **Its class vocabulary is load-bearing** -
`single-insight.php` renders takeaway pages inside `.tae-about` and borrows
`.sheet`, `.spread`, `.copy`, `.plate`, `.facts`, `.ends`. Add freely, rename
nothing; the checks now assert those survive.

**Get Involved** (`/get-involved/`, replaces `/contact/`). A routing hub, not a
contact page. Five of the six routes lead to the form that already owns that
conversation; only General Inquiry has nowhere else to go, so it is the only form
on the page. Six equal cards and one form is "clean, visual and easy to scan";
six accordions of forms is not. **301 from `/contact/` at migration.**

**All seven forms now exist** in `CF7-SMTP.md`: §4.8–4.13 plus the home opt-in.
§4.1–4.6 are marked RETIRED. Every subject carries a category prefix
(`GUEST -`, `CORPORATE -`, `UNIVERSITY -`, `COACHING -`, `COACH TRAINING -`,
`GENERAL -`) so "separate notification/category" is one inbox filter each.

Two more components were promoted on their second user, the rule this build has
followed since Phase 2: the say/form panel pair (Guests → Coaching) and the
route grid (Experiences → Get Involved).

### A bug that had been live for two phases

Phase 2 renamed `.tae-insights` → `.tae-takeaways`, and
`templates/single-insight.php` kept referencing the old name. Every takeaway page
would have rendered its "Read next" strip unstyled. The Phase 2 checks only
looked at CSS, not at what templates reference.

There is now an assertion that every `tae-*` scope used in any page **or
template** has CSS behind it - counting inline `<style>` in templates, which is
where `.tae-single` legitimately lives.

### Blocked on client content, flagged in the markup

- Founder name, biography and portrait - `about.html` §03 carries
  `[FOUNDER BIOGRAPHY …]` and a placeholder portrait.
- Business email - `get-involved.html` §04 carries a `BLOCKED:` comment above a
  placeholder `mailto:`.

Both are asserted, so the flags cannot be quietly lost before someone supplies
the real thing.

### Original scope notes

**Coaching** (new): hero "Turn access into action.", Professional Coaching block
with six focus areas + REQUEST COACHING, Coach Training & Certification block +
EXPLORE COACH TRAINING.

**About** (rewrite): hero "Access changes what becomes possible.", philosophy,
founder bio + portrait. Company reads larger than one person.

**Get Involved / Contact** (rework `contact.html`): routing hub, six routes -
Share Your Perspective · Partner With The Exchange · Bring The Exchange To
Campus · Explore Coaching · Explore Coach Training · General Inquiry. Existing
page has four (Guest / University / Press / Other); Press is not in the handoff.

---

## Phase 7 · Polish, legal, tracking, handoff - DONE

**Legal drafts.** `privacy.html` and `terms.html`, new `.tae-legal` scope. The
privacy policy describes what this site actually does - the seven forms and
their fields, the opt-in, GA4, the click-to-load YouTube embed, Google Fonts -
which is what §21 asks for and what separates it from a template. Both are
marked **draft for owner review, not legal advice**, and both need a qualified
read before launch. Two clauses in the Terms need a business decision, not just
a legal one: guest approval over their own cut, and what coach certification
actually certifies.

**Analytics.** global.js §12 fires all eight conversions from §20 plus a ninth
for general inquiries. It keys off each form's `html_class`, not the CF7 ID -
IDs change whenever a form is rebuilt, and there are seven of them. Nothing is
hard-coded: no GA snippet ships here, and the module is inert until `gtag` or
`dataLayer` exists, so it is safe to install analytics with a consent tool
afterwards and change nothing.

**SEO.** `SEO-YOAST.md` §3 rewritten for ten pages plus the two generated types.
Episode and takeaway pages use content-type templates rather than hand-written
fields, so a growing library needs no SEO work per item. The three redirects are
written down: `/university-partnerships/`, `/contact/`, `/insights/`.

**Owner handoff.** `wordpress/OWNER-HANDOFF.md` - the nine tasks §24 lists, in
the order they will actually be needed, plus the dependency table and a
quarterly "submit one form and confirm it arrives" check. Silent email failure
is the most common way a site like this loses business without anyone noticing.

**Images.** `tools/fetch-images.py` downloads all 18 hotlinked photographs,
names them after the page and alt text, and rewrites the sources.
Deliberately not run: the handoff says original photography replaces the
temporary imagery after the first production, and committing 18 stock photos to
delete them in three weeks is work done twice. Run it once the launch imagery is
settled.

### Cleanup - the deletions earlier phases promised

- `.tae-contact` scope, `contactDesk()` and the contact CF7 rule - 196 lines
- The four parked home components - 93 lines
- `wall`, `rail` and `watch` views, their four templates and the ajax branch
- **The form CSS consolidation finished.** There is now one `.form`
  implementation on the site; every page drives it with tokens. Asserted.

`global.css` went 2455 → 2183 lines and `global.js` 897 → 840 while gaining
analytics, legal pages and two new page scopes.

### A real accessibility bug

`interview-cover.php` still used `<h1>` for the cover story. It was the page's
only heading before Phase 2 gave the page a hero - since then, a populated
Interview Series page has had **two `<h1>`s**. Demoted to `<h2>`. Found by the
new one-h1-per-page assertion.

### Launch blockers - asserted, so they cannot ship silently

| Blocker                      | Where it is flagged                               |
| ---------------------------- | ------------------------------------------------- |
| Business email               | `privacy.html`, `terms.html`, `get-involved.html` |
| Postal address (CAN-SPAM)    | `privacy.html`                                    |
| Governing law / legal entity | `terms.html`                                      |
| Founder bio and portrait     | `about.html`                                      |
| Seven CF7 form IDs           | every page carrying a form                        |

Each is a `[PLACEHOLDER]` with a check asserting the marker is still present.
Removing a marker without supplying the real value fails the build.

### Original scope notes

**Forms** - seven total, each with spam protection, validation, success message,
notification to the business inbox, autoresponder where appropriate:
guest, corporate, university, coaching, coach training, general, email opt-in.
SMTP procedure already documented in `wordpress/CF7-SMTP.md`.

**Legal** - draft Privacy Policy and Terms for owner review. Privacy must
describe what the site actually does: inquiry forms, email signup,
analytics/cookies, YouTube embeds, Google Fonts.

**SEO** - `SEO-YOAST.md` covers six pages; redo for ten plus episode singles.
Clean URLs, titles/descriptions, heading hierarchy, XML sitemap.

**Analytics** - GA4 plus eight conversion events: email signup, guest
submission, corporate inquiry, university inquiry, coaching inquiry, coach
training inquiry, interview/video play, outbound social clicks. Hooks go in
`global.js` on CF7 `wpcf7mailsent` and on the video facade click.

**Images** - heroes currently hotlink Pexels (`preconnect images.pexels.com`).
Download, compress, self-host before launch.

**Accessibility** - contrast pass after the gold swap, form labels, keyboard nav.

**Admin handoff** - short written + recorded walkthrough: publish an interview,
edit a guest, add takeaways/clips, change the homepage featured interview,
replace images and thumbnails, review form submissions, edit SEO fields, make
routine copy changes, confirm backups.

---

## Blocked - need from client

1. Temp WordPress domain URL + wp-admin credentials
2. Designated business email for public contact and form notifications
3. Founder name, bio, portrait
4. Social links (build hardcodes YouTube `@theaccessexchange` - confirm it exists)
5. Final logo ETA - text treatment ships until then
6. Font licence: build names Skandia + Leif, falls back to Figtree. Buying, or Figtree ships?
7. ~~Interview category list~~ - RESOLVED. Owner-editable in wp-admin; we only
   swap the activation defaults once. See Phase 2 · Taxonomy.
8. Whether theaccessexchange.com currently serves anything live (decides whether 301s matter)

## Note on scope vs deadline

1.5 weeks covers four new pages, two legal drafts, a full copy rewrite, a
palette change, a CMS architecture change, seven forms and analytics. Phases 0–3
are the launch-critical set per the handoff's own priority order. If time
compresses, Phases 5 and 6 ship as single-section pages with a live inquiry
route and get filled out post-launch - that is the client's call, not ours.

## Security

The handoff PDF carries the Namecheap password in plaintext and sits in the
project folder. Keep it out of git. Rotate that password after handoff.
