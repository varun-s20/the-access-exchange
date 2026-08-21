# Migrating the live WordPress site from v1 to the current build

The live site is the **six-page v1** (`f928119`). This repo is the **eight-page
architecture** the client's handoff asks for (`e47ff04`) plus the plugin work
that followed. This is what it takes to get from one to the other.

Read §0 before doing anything. Two things in this migration fail *silently* -
they leave a site that looks fine and is wrong.

---

## 0. Before you touch the live site

**Work on staging.** Every step below is reversible except the two data
migrations in §3, and those are reversible only from a backup.

**Take a backup and confirm you can restore it.** Not "the host says it has
backups" - actually restore one somewhere.

**Inventory what is there.** Run this first, because it decides how much of §3
applies:

```
wp post list --post_type=tae_interview --format=count
wp post list --post_type=tae_insight   --format=count
wp term list tae_category --fields=slug,name,count
```

No WP-CLI? **Interviews** and **Takeaways** in wp-admin, and
**Interviews → Categories**, tell you the same thing.

Three possible answers:

| What you find                              | What it means                                           |
| ------------------------------------------ | ------------------------------------------------------- |
| 0 interviews, 0 insights                    | §3 is a no-op. Easiest case.                            |
| 13 interviews, 11 insights, old categories  | The v1 importer was run. This is **demo copy** - delete it, see §3c. |
| Real interviews the owner published         | §3a and §3b both matter and must not be skipped.        |

---

## 1. Order of operations

Do these in order. The order is not arbitrary:

1. Assets (§2) - the new pages reference classes that only exist in the new CSS.
2. Plugin (§3) - the pages contain shortcodes the new plugin renders.
3. Data migration (§3a-c) - before anyone sees the new pages.
4. Pages (§4) - create, update, retire.
5. Forms (§5) - the pages carry `REPLACE_ID_*` placeholders until this is done.
6. Redirects (§6).
7. Settings and SEO (§7).
8. Verify (§8).

---

## 2. Assets

`assets/global.css` and `assets/global.js` both changed since v1 - the redesign
touched both, and this session changed `global.js` again.

Replace both in the child theme (`README-WORDPRESS.md` §1, Option A) and **bump
the version string** in `wp_enqueue_style`/`wp_enqueue_script`. Without the bump,
returning visitors keep the cached v1 files and see the new markup styled by the
old stylesheet.

Then purge every cache layer: the caching plugin, the host's cache, and
Cloudflare if it is in front.

### The one edit `global.js` still needs

`README-WORDPRESS.md` §9 describes it and it is still required - line 100:

```js
var SITE_INDEX = window.TAE_INDEX || [ … existing literal, unchanged … ];
```

Already applied in this repo's copy. If you paste this repo's `global.js`
wholesale, it is done.

---

## 3. The plugin, and the two silent failures

Plugin goes from **1.0.0 → 1.1.0**.

```
Plugins → The Access Exchange - Content → Deactivate
```

Replace `wp-content/plugins/tae-content/` with this repo's copy (or upload
`plugins/tae-content.zip`), then **Activate**, then **Settings → Permalinks →
Save**.

Deactivating does not delete content. Post types, terms and meta all survive.

> `inc/importer.php` and `data/seed.php` are gone in 1.1.0. If you were relying
> on **Tools → Import TAE content**, that menu item disappears - deliberately.
> See §3c.

### 3a. Category slugs changed - this one is visible and silent

v1 seeded a technology set. The redesign replaced it with the client's:

| v1            | Now                        |
| ------------- | -------------------------- |
| `engineering` | `leadership`               |
| `product`     | `founders`                 |
| `data`        | `industry`                 |
| `design`      | `career`                   |
| `breaking-in` | `campus`                   |

**Activation only adds terms that do not exist. It never removes any.** So a v1
site ends up with **ten** category terms. The filter chips on the Interview
Series page are generated from the live taxonomy with `hide_empty => false`, so
all ten render - five of them chips that match nothing and show an empty archive
when clicked.

Reassign, then delete the old five:

```
# One line per interview that used an old term. Check the mapping is what you
# want first - "Design" interviews are not automatically "Career & Transitions".
wp post term set <id> tae_category leadership --by=slug

# Only once every interview has a new term:
wp term delete tae_category engineering product data design breaking-in --by=slug
```

Manually: **Interviews → Categories**, open each old term, note which interviews
use it, reassign them on each interview, then delete the term.

An interview left with no category still renders everywhere and is hidden by
every filter except **All**. That is not a crash, but it is not what anyone
intended either.

### 3b. Takeaway URLs changed

The insight post type moved from `/insights/{slug}/` to `/takeaways/{slug}/`,
because the handoff folded the Insights page into the Interview Series ecosystem.

Any insight the v1 site published is now at a new address. `SEO-YOAST.md` §3.13
covers the three *page* redirects; it does not cover these, because they did not
exist when it was written. Add a wildcard 301:

```
/insights/(.*)  →  /takeaways/$1     [301, regex]
```

Redirection and Yoast Premium both do regex redirects. Do this **before** the
page-level `/insights/ → /interview-series/#takeaways` rule from §6, or the page
rule swallows the post URLs.

### 3c. Delete v1 demo content, if the importer was run

13 interviews and 11 insights transcribed from the prototype. It is placeholder
copy about fictional people, and it is not the launch content:

```
wp post delete $(wp post list --post_type=tae_interview --format=ids) --force
wp post delete $(wp post list --post_type=tae_insight   --format=ids) --force
```

**Only run that if §0 told you the content is demo.** It deletes everything of
both types. If real interviews are mixed in, delete by hand.

With the library empty, the site returns to its launch state on its own: the
"First interview coming soon" blocks reappear on both the home page and the
Interview Series page, because they are conditional on there being nothing to
show. Nothing to edit, nothing to switch back on.

### 3d. Orphaned meta - ignore it

Two fields were removed: `tae_slot_watch` (its section was deleted in Phase 7)
and `tae_start_here` (its shortcode was never placed on a page). Rows for both
survive in `wp_postmeta` and are read by nothing. Harmless. Clean them if you
like:

```
wp post meta delete --all tae_slot_watch
wp post meta delete --all tae_start_here
```

---

## 4. Pages

v1 has six. The architecture has ten. Four v1 pages carry over with new content,
two retire, six are new.

| Page                     | Slug                 | Action                             |
| ------------------------ | -------------------- | ---------------------------------- |
| Home                     | _(front page)_       | **Replace** the HTML widget        |
| Interview Series         | `interview-series`   | **Replace** the HTML widget        |
| About                    | `about`              | **Replace** the HTML widget        |
| Guests & Partners        | `guests-partners`    | **New**                            |
| Universities             | `universities`       | **New** (replaces `university-partnerships`) |
| Experiences              | `experiences`        | **New**                            |
| Coaching                 | `coaching`           | **New**                            |
| Get Involved             | `get-involved`       | **New** (replaces `contact`)       |
| Privacy Policy           | `privacy`            | **New**                            |
| Terms & Conditions       | `terms`              | **New**                            |
| ~~University Partnerships~~ | `university-partnerships` | **Retire** after §6        |
| ~~Insights~~             | `insights`           | **Retire** after §6                |
| ~~Contact~~              | `contact`            | **Retire** after §6                |

Each page: **Pages → Add New** → set the slug → **Edit with Elementor** → Page
Settings → **Page Layout: Elementor Full Width** → one Container (padding `0`,
gap `0`, HTML Tag `main`) → one **HTML** widget → paste the matching
`wordpress/*.html` file whole.

**Retire, do not delete, until the redirects in §6 are live and tested.** Set the
three old pages to Draft once traffic is landing correctly.

Header and footer are Theme Builder templates and both changed - the nav is now
seven items plus the persistent GET INVOLVED CTA. Update both from
`header.html` and `footer.html`.

### A leftover shortcode will not warn you

`view="wall"`, `view="rail"`, `view="watch"` and `[tae_insights view="start"]`
were all removed. The shortcode returns an empty string for an unknown view
rather than an error - so a v1 page left un-updated loses that section quietly
and stays otherwise intact. If a block is missing after migration, this is why:
the page was not replaced.

---

## 5. Forms

Seven Contact Form 7 forms, per `CF7-SMTP.md`. v1 had fewer - the redesign added
the coaching, coach-training, university and general forms.

Create each one, then replace its placeholder in the page HTML with the real ID:

| Placeholder             | Page                | `html_class`      |
| ----------------------- | ------------------- | ----------------- |
| `REPLACE_ID_JOIN`       | `index.html`        | `form--join`      |
| `REPLACE_ID_GUEST`      | `guests-partners`   | `form--guest`     |
| `REPLACE_ID_CORPORATE`  | `guests-partners`   | `form--corporate` |
| `REPLACE_ID_UNIVERSITY` | `universities`      | `form--university`|
| `REPLACE_ID_COACHING`   | `coaching`          | `form--coaching`  |
| `REPLACE_ID_TRAINING`   | `coaching`          | `form--training`  |
| `REPLACE_ID_GENERAL`    | `get-involved`      | `form--general`   |

**Do not change the `html_class`.** Conversion tracking keys off it, not the form
ID, precisely because IDs change whenever someone rebuilds a form.

The opt-in needs Mail(2) enabled - the handoff asks for a welcome autoresponder
on that one specifically. SMTP must be configured or forms submit and no email
arrives, silently.

---

## 6. Redirects

From `SEO-YOAST.md` §3.13, plus the CPT rule from §3b:

| Old                          | New                            | Type   |
| ---------------------------- | ------------------------------ | ------ |
| `/insights/(.*)`             | `/takeaways/$1`                | regex  |
| `/university-partnerships/`  | `/universities/`               | exact  |
| `/contact/`                  | `/get-involved/`               | exact  |
| `/insights/`                 | `/interview-series/#takeaways` | exact  |

Regex rule first. `/interviews/` and `/takeaways/` themselves are handled by the
plugin (`tae_redirect_bare_bases`) and need no rule.

---

## 7. Settings and SEO

- **Interviews → Links** - new in 1.1.0. Set the YouTube channel if `@theaccessexchange`
  is not final, and the Join CTA target if the opt-in is not the home page band.
  Both fall back to what the templates used to hardcode, so an untouched install
  renders exactly as before.
- **Settings → Reading** - confirm Home is still the static front page.
- **Yoast** - `SEO-YOAST.md` has titles and descriptions for all ten pages,
  already written. §1.4 Content types: interviews are indexable, and the takeaway
  CPT should be too (the plugin already noindexes individual unwritten ones).
- **Episodes need no meta by hand.** 1.1.0 generates an SEO title
  (`Episode - Guest | The Access Exchange`) and takes the description from the
  standfirst. Both defer to anything typed into Yoast.
- Resubmit the sitemap in Search Console once the redirects are live.

---

## 8. Verify

```
python tools/build-static.py && python wordpress/tests/check.py    # 414 checks
php wordpress/plugins/tae-content/tests/test-tae.php                # 70 checks
```

Those cover the source. On the live site, check by hand:

- [ ] Every nav item resolves; no 404s
- [ ] All four old URLs from §6 land on the right page
- [ ] Interview Series shows the launch state, or the real library - not a
      half-rendered mix
- [ ] Category chips: **five**, not ten (§3a)
- [ ] Publish one test interview - paste a full YouTube URL, confirm the ID is
      extracted on save, the poster renders, and play loads the video
- [ ] The coming-soon blocks vanish from both pages when it publishes, and come
      back when you unpublish it
- [ ] Submit one form; confirm it arrives, with the right subject prefix
- [ ] `TAE` in the browser console is an object, not `undefined`
- [ ] Delete the test interview

---

## 9. Known stale docs

`README-WORDPRESS.md` §3 still lists the **six** v1 pages, including
`university-partnerships`, `insights` and `contact`, and §9 documents shortcode
views that no longer exist. Whoever does this migration should follow **this**
file for the page list, not that one. Worth rewriting §3 and §9 afterwards so the
next person is not misled.
