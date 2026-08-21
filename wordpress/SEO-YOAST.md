# Yoast SEO - full setup for The Access Exchange

Everything you need to type in, page by page. Work through §1 once, then §3
ten times.

Menu paths are Yoast 20+ ("Yoast SEO → Settings" with the left-hand nav). If
your install is older the same options exist under "Search Appearance".

---

## 1. Site-wide setup - do this first

Per-page work inherits from these. Getting them wrong makes every page wrong.

### 1.1 Site representation

**Yoast SEO → Settings → Site representation**

| Field                    | Value                                                                                                                                                                          |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Organization or person   | **Organization**                                                                                                                                                               |
| Organization name        | `The Access Exchange`                                                                                                                                                          |
| Alternate name           | `Access Exchange`                                                                                                                                                              |
| Organization logo        | Square PNG, **at least 512×512**, ideally 1024×1024, transparent or `#F8F8F3` background                                                                                       |
| Organization description | `A tech media and professional-development platform: long-form interviews with people working in tech, and partnership programmes that bring those conversations onto campus.` |
| Email                    | `hello@theaccessexchange.com`                                                                                                                                                  |

This is what produces the `Organization` node the partnerships `Service` schema
points at. If you skip it, that `@id` reference dangles.

### 1.2 Site basics

**Yoast SEO → Settings → Site basics**

| Field           | Value                                             |
| --------------- | ------------------------------------------------- |
| Website name    | `The Access Exchange`                             |
| Alternate name  | `Access Exchange`                                 |
| Site image      | The 1200×630 default OG image (see §4)            |
| Title separator | `-` (em dash - matches the wordmark's typography) |

### 1.3 Social profiles

**Yoast SEO → Settings → Site representation → Other profiles**

Add the YouTube URL. Add X/LinkedIn/Instagram only if they actually exist -
an empty profile in `sameAs` is worse than no profile.

### 1.4 Content types

**Yoast SEO → Settings → Content types → Pages**

| Setting                      | Value                                              |
| ---------------------------- | -------------------------------------------------- |
| Show Pages in search results | **Yes**                                            |
| SEO title template           | `%%title%% %%sep%% %%sitename%%`                   |
| Meta description template    | _leave empty_ - every page gets a hand-written one |
| Social image                 | leave empty - set per page                         |

**Posts**: if you are not blogging yet, set _Show Posts in search results_ →
**No**, so an empty blog does not get indexed.

### 1.5 Kill the empty archives

**Yoast SEO → Settings → Advanced**

| Setting         | Value                                         | Why                                                         |
| --------------- | --------------------------------------------- | ----------------------------------------------------------- |
| Author archives | **Off**                                       | Single-author site - the author archive duplicates the blog |
| Date archives   | **Off**                                       | Never useful, always thin                                   |
| Format archives | **Off**                                       | -                                                           |
| Media pages     | **On** (redirect attachment URLs to the file) | Yoast's default; stops one indexable page per image         |

### 1.6 Breadcrumbs

**Yoast SEO → Settings → Advanced → Breadcrumbs → Enable**

Separator `›`, "Anchor text for the homepage" `Home`, prefix empty.

You do **not** have to render the breadcrumb trail visually - the design has no
place for it. Enabling it is what makes Yoast emit `BreadcrumbList` schema,
which is what Google uses for the breadcrumb line in the result snippet.

### 1.7 Sitemaps

**Yoast SEO → Settings → Site features → XML sitemaps → On.**
Check `https://theaccessexchange.com/sitemap_index.xml` loads before launch.

---

## 2. Who owns the schema

Both Yoast and the page files were emitting `Organization`, `WebSite`,
`WebPage` and `BreadcrumbList`. That is a duplicate-entity problem, so the page
files have been **trimmed**. Current split:

| Node                                                                  | Emitted by                                                                                                                    |
| --------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| `Organization`, `WebSite`, `WebPage`, `BreadcrumbList`, `ImageObject` | **Yoast** (from §1)                                                                                                           |
| `CollectionPage` / `AboutPage` / `ContactPage`                        | **Yoast** - set per page in the Schema tab, §3                                                                                |
| `Service` (partnerships)                                              | **inline JSON-LD** - Yoast cannot express it                                                                                  |
| `FAQPage` + `Question` (partnerships, interviews)                     | **inline JSON-LD** - Yoast can only do this via its Gutenberg FAQ block, which is unavailable inside an Elementor HTML widget |

Eight page files now carry **no** JSON-LD at all (`index`, `guests-partners`,
`experiences`, `coaching`, `about`, `get-involved`, `privacy`, `terms`). Two -
`interview-series` and `universities` - carry a trimmed graph whose `@id`
values match Yoast's node ids, so the two graphs stitch into one:

```
https://theaccessexchange.com/#organization          ← Yoast
https://theaccessexchange.com/{slug}/#webpage        ← Yoast
https://theaccessexchange.com/{slug}/#faq            ← ours, isPartOf the above
```

`universities.html` also carries a `Service` node (the university-engagement
offer) whose `provider` points at Yoast's `#organization` node - it used to
also duplicate its own `WebPage` node pointing at `#org`/`#site`, which don't
match Yoast's real ids (`#organization`/`#website`) and would have shipped as
dangling references the moment Yoast's graph went live. That duplicate node has
been removed; only `Service` and `FAQPage` are inline now, matching the split
below.

**Before launch:** find-and-replace `https://theaccessexchange.com` with the real
domain across **all ten** page files. It is functional in the two JSON-LD blocks
above, and cosmetic in the instruction comments and Canonical fields at the top
of every file - but those comments are served to the browser, so a stale domain
sitting in the page source is a bad look either way.

⚠️ On the two FAQ pages set the Yoast page type to **Web Page** / **Collection
Page**, _not_ **FAQ Page** - picking FAQ Page there would put a second
`FAQPage` node on the URL.

---

## 3. Per-page fields

**Ten pages, plus the two generated types.** For each page open it in the
**WordPress editor screen** (the one with the blue "Edit with Elementor" button),
_not_ the Elementor canvas - the Yoast metabox is reliably there.

Fields map to tabs like this:

- **SEO tab** → focus keyphrase, SEO title, slug, meta description
- **SEO tab → Advanced** → index/follow, breadcrumb title, canonical
- **Social tab** → Facebook and X title/description/image
- **Schema tab** → page type, article type

Paste the SEO title as literal text, not as `%%title%%` - the ones below are
tuned to the 60-character snippet width, which the page's own H1 is not.

> **The strings below are already in the page files.** Every `wordpress/*.html`
> opens with an SEO block carrying exactly these values. If the two ever
> disagree, the page file is the one that was updated with the page.

| #   | Page                        | Slug               | Focus keyphrase                 | Schema page type |
| --- | --------------------------- | ------------------ | ------------------------------- | ---------------- |
| 1   | Home                        | _(front page)_     | `the access exchange`           | Web Page         |
| 2   | Interview Series            | `interview-series` | `leadership interview series`   | Collection Page  |
| 3   | Guests & Partners           | `guests-partners`  | `be a guest interview series`   | Web Page         |
| 4   | Universities & Institutions | `universities`     | `university leadership speaker` | Web Page         |
| 5   | Experiences                 | `experiences`      | `leadership events and panels`  | Web Page         |
| 6   | Coaching                    | `coaching`         | `professional coaching`         | Web Page         |
| 7   | About                       | `about`            | `about the access exchange`     | About Page       |
| 8   | Get Involved                | `get-involved`     | `contact the access exchange`   | Contact Page     |
| 9   | Privacy Policy              | `privacy`          | _(none - see below)_            | Web Page         |
| 10  | Terms & Conditions          | `terms`            | _(none - see below)_            | Web Page         |

**Leave the keyphrase empty on the two legal pages.** They exist to be found by
someone already on the site, not to rank. Yoast will show an orange dot; that is
the correct state for a legal page, not a problem to solve.

### 3.11 Interview episodes → `/interviews/{slug}/`

These are generated, one per interview, and there are eventually a lot of them.
Do not hand-write SEO for each.

- **Content type default**: SEO → Search Appearance → Content Types →
  Interviews. Set the title template to `%%title%% - The Access Exchange` and
  the description template to `%%excerpt%%`.
- That makes the **Excerpt** field the meta description. Fill it in when
  publishing - two sentences on why this conversation is worth an hour.
- Schema: Article. Article type: **Interview**, which Yoast supports natively.
- Interviews are `index, follow` - they are the reason the site exists.

### 3.12 Takeaways → `/takeaways/{slug}/`

Same treatment: title template `%%title%% - The Access Exchange`, description
from the excerpt, Article schema.

A takeaway that is only a clip with no written substance should be
`noindex, follow` - it has nothing for a search result to show, and thin pages
drag the whole site's assessment down. A takeaway with a real write-up should be
indexed. Judge it per piece; the default is index.

### 3.13 Redirects - do these at migration

Three URLs changed in the rebuild. Each needs a **301** (Yoast Premium has a
redirect manager; Redirection is a good free alternative):

| Old                         | New                            |
| --------------------------- | ------------------------------ |
| `/university-partnerships/` | `/universities/`               |
| `/contact/`                 | `/get-involved/`               |
| `/insights/`                | `/interview-series/#takeaways` |

Skipping these means anything already shared - an email, a deck, a link in
someone's notes - lands on a 404. This table is page-level only; the wildcard
rule for individual takeaway URLs (`/insights/(.*) → /takeaways/$1`) is in
`MIGRATE-V1-TO-V2.md` §3b - do that one **before** the `/insights/` row above,
or the page-level rule swallows the post URLs.

## 4. Open Graph images

**1200 × 630 px**, JPG, under 300 KB, real text baked in (not just the logo -
these get scaled to a thumbnail in Slack and WhatsApp).

| File                     | Page               | Suggested content                                                        |
| ------------------------ | ------------------- | ------------------------------------------------------------------------ |
| `og-home.jpg`            | Home                 | The wordmark + "Get closer to the people and ideas shaping what's next." |
| `og-interviews.jpg`      | Interview Series     | The cover-story still + "Interviews worth carrying forward"              |
| `og-guests-partners.jpg` | Guests & Partners    | A guest portrait + "Bring a perspective worth hearing"                   |
| `og-universities.jpg`    | Universities         | The campus photo + "Bring The Access Exchange to your campus."                  |
| `og-experiences.jpg`     | Experiences          | An event/room still + "The Access Exchange goes beyond the screen"              |
| `og-coaching.jpg`        | Coaching             | Wordmark + "Turn access into action."                                    |
| `og-about.jpg`           | About                | The recording-setup still                                                |
| `og-get-involved.jpg`    | Get Involved         | Plain paper ground + wordmark                                            |

Privacy and Terms need no OG image of their own - they inherit the Site basics
fallback (§1.2), which is correct for pages that aren't meant to be shared.

Set these on the **Social tab**, not as the Featured Image - Hello Elementor
would print the featured image into the page body.

If you only make one, put it in **Site basics → Site image** and every page
falls back to it.

---

## 5. What Yoast will complain about, and what to ignore

The content is deliberate editorial prose inside an Elementor HTML widget.
Several Yoast checks will go orange. Here is what is real and what is noise.

**Ignore - the check is wrong for this site**

- **Readability / Flesch score (orange or red).** The house voice is long,
  clause-heavy sentences. That is the brand. Readability is not a Google
  ranking factor and Yoast says so in its own docs.
- **"Text length" on Contact (red).** It is ~200 words because it is one card
  and a strip. A contact page does not need 300 words.
- **"Keyphrase in introduction" on University Partnerships (red).** The page
  opens on the student's problem, not on the product name. Rewriting the
  opening to force the phrase in would make it worse copy for a careers
  officer, who is the only reader that matters here.
- **"No content" / empty analysis.** Elementor stores the layout in post meta,
  not `post_content`. Yoast's Elementor integration usually reads it, but if
  your version does not, the analysis panel goes blank. **The rendered HTML is
  still perfectly crawlable** - verify with View Source or the URL Inspection
  tool rather than trusting the panel.

**Fix - these are real**

- **"Previously used keyphrase."** Every page above has a unique one. If Yoast
  flags a duplicate, you typed the wrong one.
- **"Meta description too long/short."** Every description above is inside the
  window; if Yoast disagrees, check for a trailing space or a smart quote.
- **Any "no alt attribute" warning.** All images already have real alt text.
  A warning means a new image was added without one.
- **Any broken internal link.** The link graph in §6 must resolve.

---

## 6. Internal link graph

Yoast's "internal links" check passes on all ten pages because the design
already links densely. Do not break these when editing:

```
Home              → Interview Series, Guests & Partners, Universities,
                    Experiences, Coaching, /#join
Interview Series  → #episodes, #takeaways, guests-partners#guest, YouTube
Guests & Partners → #guest, #corporate
Universities      → #s1…#s5, #enquire
Experiences       → the three inquiry pathways it routes to (no form of its own)
Coaching          → #coaching, #training
About             → guests-partners#guest, universities, get-involved
Get Involved      → all six specific routes it hands off to
Header (all)      → Home, Interview Series, Guests & Partners, Universities,
                    Experiences, Coaching, About, + persistent Get Involved CTA
Footer (all)      → all ten pages + guests-partners#guest/#corporate +
                    universities#enquire + coaching#coaching/#training +
                    interview-series#takeaways + Privacy + Terms
```

The footer links to `/privacy/` and `/terms/`. Both pages exist (they're
drafts pending legal review, not blank) - set both to `noindex, follow`;
they exist to be found by someone already on the site, not to rank.

---

## 7. Launch checklist

1. **Take the site out of staging mode.** Settings → Reading → _Discourage
   search engines_ must be **unchecked**. Yoast shows a red warning on the
   dashboard while it is on; do not launch past it.
2. **Find-and-replace the domain** in `interview-series.html` and
   `universities.html` (the only two with functional hard-coded URLs, in their
   JSON-LD) - and, cosmetically, in every other page's SEO comment block.
3. **Search Console.** Add the property, verify via Yoast → Settings → Site
   connections, submit `sitemap_index.xml`.
4. **Rich Results Test** every URL - `search.google.com/test/rich-results`.
   Expect: Organization + Breadcrumbs on all ten; FAQ on Interview Series and
   Universities; no duplicate-entity warnings. If you see two `FAQPage` nodes,
   the Yoast page type is set to "FAQ Page" - change it back (§2).
5. **Pick one canonical host** - `https://` and one of www/non-www - and 301
   everything else. Yoast will not fix a split canonical for you.
6. **Redirects.** The full current list - old-slug and v1-migration redirects
   both - lives in `MIGRATE-V1-TO-V2.md` §6, not here; that file is the one
   kept in sync with the live architecture. Free Yoast has no redirect
   manager - use the _Redirection_ plugin either way.
7. **Confirm one `<h1>` per page.** Hello Elementor prints its own page title;
   Elementor → Page Settings → **Hide Title** must be on for all ten (this was
   already in the pink-fix step, but re-check after any template change).
8. **Check the mobile snippet preview** in Yoast for each page - titles are
   truncated harder on mobile than the desktop preview suggests.
