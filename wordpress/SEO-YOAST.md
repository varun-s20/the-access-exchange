# Yoast SEO — full setup for The Access Exchange

Everything you need to type in, page by page. Work through §1 once, then §3
six times.

Menu paths are Yoast 20+ ("Yoast SEO → Settings" with the left-hand nav). If
your install is older the same options exist under "Search Appearance".

---

## 1. Site-wide setup — do this first

Per-page work inherits from these. Getting them wrong makes every page wrong.

### 1.1 Site representation
**Yoast SEO → Settings → Site representation**

| Field | Value |
|---|---|
| Organization or person | **Organization** |
| Organization name | `The Access Exchange` |
| Alternate name | `Access Exchange` |
| Organization logo | Square PNG, **at least 512×512**, ideally 1024×1024, transparent or `#F8F8F3` background |
| Organization description | `A tech media and professional-development platform: long-form interviews with people working in tech, and partnership programmes that bring those conversations onto campus.` |
| Email | `hello@theaccessexchange.com` |

This is what produces the `Organization` node the partnerships `Service` schema
points at. If you skip it, that `@id` reference dangles.

### 1.2 Site basics
**Yoast SEO → Settings → Site basics**

| Field | Value |
|---|---|
| Website name | `The Access Exchange` |
| Alternate name | `Access Exchange` |
| Site image | The 1200×630 default OG image (see §4) |
| Title separator | `—` (em dash — matches the wordmark's typography) |

### 1.3 Social profiles
**Yoast SEO → Settings → Site representation → Other profiles**

Add the YouTube URL. Add X/LinkedIn/Instagram only if they actually exist —
an empty profile in `sameAs` is worse than no profile.

### 1.4 Content types
**Yoast SEO → Settings → Content types → Pages**

| Setting | Value |
|---|---|
| Show Pages in search results | **Yes** |
| SEO title template | `%%title%% %%sep%% %%sitename%%` |
| Meta description template | *leave empty* — every page gets a hand-written one |
| Social image | leave empty — set per page |

**Posts**: if you are not blogging yet, set *Show Posts in search results* →
**No**, so an empty blog does not get indexed.

### 1.5 Kill the empty archives
**Yoast SEO → Settings → Advanced**

| Setting | Value | Why |
|---|---|---|
| Author archives | **Off** | Single-author site — the author archive duplicates the blog |
| Date archives | **Off** | Never useful, always thin |
| Format archives | **Off** | — |
| Media pages | **On** (redirect attachment URLs to the file) | Yoast's default; stops one indexable page per image |

### 1.6 Breadcrumbs
**Yoast SEO → Settings → Advanced → Breadcrumbs → Enable**

Separator `›`, "Anchor text for the homepage" `Home`, prefix empty.

You do **not** have to render the breadcrumb trail visually — the design has no
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

| Node | Emitted by |
|---|---|
| `Organization`, `WebSite`, `WebPage`, `BreadcrumbList`, `ImageObject` | **Yoast** (from §1) |
| `CollectionPage` / `AboutPage` / `ContactPage` | **Yoast** — set per page in the Schema tab, §3 |
| `Service` (partnerships) | **inline JSON-LD** — Yoast cannot express it |
| `FAQPage` + `Question` (partnerships, interviews) | **inline JSON-LD** — Yoast can only do this via its Gutenberg FAQ block, which is unavailable inside an Elementor HTML widget |

Four page files now carry **no** JSON-LD at all (`index`, `about`, `contact`,
`insights`). Two carry a trimmed graph whose `@id` values match Yoast's node
ids, so the two graphs stitch into one:

```
https://theaccessexchange.com/#organization          ← Yoast
https://theaccessexchange.com/{slug}/#webpage        ← Yoast
https://theaccessexchange.com/{slug}/#faq            ← ours, isPartOf the above
https://theaccessexchange.com/university-partnerships/#service   ← ours
```

**Before launch:** find-and-replace `https://theaccessexchange.com` with the real
domain across **all six** page files. It is functional in the two JSON-LD blocks
above, and cosmetic in the instruction comments at the top of every file — but
those comments are served to the browser, so a stale domain sitting in the page
source is a bad look either way.

⚠️ On the two FAQ pages set the Yoast page type to **Web Page** / **Collection
Page**, *not* **FAQ Page** — picking FAQ Page there would put a second
`FAQPage` node on the URL.

---

## 3. Per-page fields

Six blocks below. For each page open it in the **WordPress editor screen** (the
one with the blue "Edit with Elementor" button), *not* the Elementor canvas —
the Yoast metabox is reliably there. Scroll to the Yoast box.

Fields map to tabs like this:

* **SEO tab** → focus keyphrase, SEO title, slug, meta description
* **SEO tab → Advanced** (accordion at the bottom) → index/follow, breadcrumb title, canonical
* **Social tab** → Facebook and X title/description/image
* **Schema tab** → page type, article type

Paste the SEO title as literal text, not as `%%title%%` — the ones below are
tuned to fit the 60-character snippet width, which the page's own H1 is not.

Every string below has been length-checked against Yoast's own thresholds, so
all six pages should come up green on the four checks Yoast weights hardest:

| Page | Title | Desc | Keyphrase in title | in description |
|---|---|---|---|---|
| Home | 55 | 144 | ✓ | ✓ |
| Interviews | 53 | 149 | ✓ | ✓ |
| Partnerships | 53 | 141 | ✓ | ✓ |
| Insights | 51 | 147 | ✓ | ✓ |
| About | 40 | 147 | ✓ | ✓ |
| Contact | 51 | 135 | ✓ | ✓ |

*(Yoast green: title ≤ 60 chars, description 120–156.) Retype rather than
copy-paste if your editor converts the em dashes — a smart-quote substitution
is the usual reason a "green" description suddenly reads as too long.*

---

### 3.1 Home  → `/`

| Field | Value |
|---|---|
| **Focus keyphrase** | `career advice in tech` |
| **SEO title** | `The Access Exchange — Career Advice in Tech, Unfiltered` |
| **Slug** | *(front page — none)* |
| **Meta description** | `The best career advice in tech is not secret, just unevenly distributed. Long-form interviews with people doing the job, plus campus programmes.` |
| **Cornerstone content** | **Yes** |
| **Schema → Page type** | Web Page |
| **Schema → Article type** | None |
| **Breadcrumb title** | `Home` |
| **Canonical** | *(leave empty — Yoast self-canonicalises)* |
| **Index / follow** | Yes / Yes |

**Social tab**
* Title: `The Access Exchange`
* Description: `The best career advice in tech is not secret. It is just unevenly distributed.`
* Image: `og-home.jpg` (§4)

Keyphrase lands in the H1 verbatim ("Career advice in tech is not secret"),
which is the check Yoast weights most.

---

### 3.2 The Interview Series  → `/interview-series/`

| Field | Value |
|---|---|
| **Focus keyphrase** | `tech career interviews` |
| **SEO title** | `Tech Career Interviews, In Full — The Access Exchange` |
| **Slug** | `interview-series` |
| **Meta description** | `Long-form tech career interviews with engineers, product managers, designers and data scientists. Real timelines, real rejections, nothing rehearsed.` |
| **Cornerstone content** | **Yes** |
| **Schema → Page type** | **Collection Page** |
| **Schema → Article type** | None |
| **Breadcrumb title** | `Interviews` |
| **Index / follow** | Yes / Yes |

**Social tab**
* Title: `The Interview Series`
* Description: `People are honest when honesty is safe. One guest, one conversation, and they approve the cut.`
* Image: `og-interviews.jpg`

⚠️ Page type must **not** be "FAQ Page" — the file already emits a `FAQPage`
node with the four format questions.

---

### 3.3 University Partnerships  → `/university-partnerships/`

The highest-intent page on the site. Give it the most attention.

| Field | Value |
|---|---|
| **Focus keyphrase** | `university careers partnership` |
| **SEO title** | `University Careers Partnerships — The Access Exchange` |
| **Slug** | `university-partnerships` |
| **Meta description** | `A university careers partnership scoped around your term dates: what it includes, what it costs, and what your careers team keeps afterwards.` |
| **Cornerstone content** | **Yes** |
| **Schema → Page type** | **Web Page** |
| **Schema → Article type** | None |
| **Breadcrumb title** | `For Universities` |
| **Index / follow** | Yes / Yes |

**Social tab**
* Title: `University Partnerships`
* Description: `We bring the conversation. You already have the room.`
* Image: `og-partnerships.jpg`

⚠️ Page type must **not** be "FAQ Page" — the file emits `FAQPage` with the six
cost/commitment questions plus the `Service` node.

**Related keyphrases** (Yoast Premium only, skip on free): `careers service
partnership`, `guest speakers for universities`, `employability programme`.

---

### 3.4 Insights  → `/insights/`

| Field | Value |
|---|---|
| **Focus keyphrase** | `tech career insights` |
| **SEO title** | `Tech Career Insights & Guides — The Access Exchange` |
| **Slug** | `insights` |
| **Meta description** | `Tech career insights from the whole archive: interview write-ups, breaking-in guides, levelling-up notes and what happens on campus. Search it all.` |
| **Cornerstone content** | No |
| **Schema → Page type** | **Collection Page** |
| **Breadcrumb title** | `Insights` |
| **Index / follow** | Yes / Yes |

**Social tab**
* Title: `Insights — The Access Exchange`
* Description: `Everything we have published, in one place.`
* Image: `og-insights.jpg`

---

### 3.5 About  → `/about/`

| Field | Value |
|---|---|
| **Focus keyphrase** | `the access exchange` |
| **SEO title** | `About The Access Exchange — Why We Exist` |
| **Slug** | `about` |
| **Meta description** | `Why The Access Exchange exists, how the interviews are made, and the five things we will not do however tempting. Free at the point of use, always.` |
| **Cornerstone content** | No |
| **Schema → Page type** | **About Page** |
| **Breadcrumb title** | `About` |
| **Index / follow** | Yes / Yes |

**Social tab**
* Title: `About — The Access Exchange`
* Description: `Access is a conversation somebody else already had.`
* Image: `og-about.jpg`

---

### 3.6 Contact  → `/contact/`

| Field | Value |
|---|---|
| **Focus keyphrase** | `contact the access exchange` |
| **SEO title** | `Contact The Access Exchange — Guests & Universities` |
| **Slug** | `contact` |
| **Meta description** | `Contact The Access Exchange: tell us which route you are on, guest, university or press, and we will come back within two working days.` |
| **Cornerstone content** | No |
| **Schema → Page type** | **Contact Page** |
| **Breadcrumb title** | `Contact` |
| **Index / follow** | Yes / Yes |

**Social tab**
* Title: `Contact — The Access Exchange`
* Description: `One short message reaches us. Two working days to reply.`
* Image: `og-contact.jpg`

---

## 4. Open Graph images

**1200 × 630 px**, JPG, under 300 KB, real text baked in (not just the logo —
these get scaled to a thumbnail in Slack and WhatsApp).

| File | Suggested content |
|---|---|
| `og-home.jpg` | The wordmark + "Career advice in tech is not secret." |
| `og-interviews.jpg` | The cover-story still + "The Interview Series" |
| `og-partnerships.jpg` | The campus photo + "Bring the industry onto your campus." |
| `og-insights.jpg` | Wordmark + "Everything we have published." |
| `og-about.jpg` | The recording-setup still |
| `og-contact.jpg` | Plain paper ground + wordmark |

Set these on the **Social tab**, not as the Featured Image — Hello Elementor
would print the featured image into the page body.

If you only make one, put it in **Site basics → Site image** and every page
falls back to it.

---

## 5. What Yoast will complain about, and what to ignore

The content is deliberate editorial prose inside an Elementor HTML widget.
Several Yoast checks will go orange. Here is what is real and what is noise.

**Ignore — the check is wrong for this site**

* **Readability / Flesch score (orange or red).** The house voice is long,
  clause-heavy sentences. That is the brand. Readability is not a Google
  ranking factor and Yoast says so in its own docs.
* **"Text length" on Contact (red).** It is ~200 words because it is one card
  and a strip. A contact page does not need 300 words.
* **"Keyphrase in introduction" on University Partnerships (red).** The page
  opens on the student's problem, not on the product name. Rewriting the
  opening to force the phrase in would make it worse copy for a careers
  officer, who is the only reader that matters here.
* **"No content" / empty analysis.** Elementor stores the layout in post meta,
  not `post_content`. Yoast's Elementor integration usually reads it, but if
  your version does not, the analysis panel goes blank. **The rendered HTML is
  still perfectly crawlable** — verify with View Source or the URL Inspection
  tool rather than trusting the panel.

**Fix — these are real**

* **"Previously used keyphrase."** Every page above has a unique one. If Yoast
  flags a duplicate, you typed the wrong one.
* **"Meta description too long/short."** Every description above is inside the
  window; if Yoast disagrees, check for a trailing space or a smart quote.
* **Any "no alt attribute" warning.** All images already have real alt text.
  A warning means a new image was added without one.
* **Any broken internal link.** The link graph in §6 must resolve.

---

## 6. Internal link graph

Yoast's "internal links" check passes on all six pages because the design
already links densely. Do not break these when editing:

```
Home            → Interviews, Partnerships, Insights, Contact, be-a-guest
Interviews      → #episodes, #be-a-guest, YouTube
Partnerships    → #s1…#s7, #enquire
Insights        → Interviews (#episodes), Partnerships (#s1, #s5)
About           → be-a-guest, Partnerships, Contact
Contact         → Partnerships
Header (all)    → Home, Interviews, Partnerships, Insights, About, Contact
Footer (all)    → all six + be-a-guest + #episodes + Privacy + Terms
```

The footer links to `/privacy/` and `/terms/`. **Create those two pages** or
the site ships two 404s on every page — Yoast's crawl and Search Console will
both flag it. Set both to `noindex, follow`.

---

## 7. Launch checklist

1. **Take the site out of staging mode.** Settings → Reading → *Discourage
   search engines* must be **unchecked**. Yoast shows a red warning on the
   dashboard while it is on; do not launch past it.
2. **Find-and-replace the domain** in `interview-series.html` and
   `university-partnerships.html` (the only remaining hard-coded URLs).
3. **Search Console.** Add the property, verify via Yoast → Settings → Site
   connections, submit `sitemap_index.xml`.
4. **Rich Results Test** every URL — `search.google.com/test/rich-results`.
   Expect: Organization + Breadcrumbs on all six; FAQ on Interviews and
   Partnerships; no duplicate-entity warnings. If you see two `FAQPage` nodes,
   the Yoast page type is set to "FAQ Page" — change it back (§2).
5. **Pick one canonical host** — `https://` and one of www/non-www — and 301
   everything else. Yoast will not fix a split canonical for you.
6. **Redirect the old prototype URLs** if they were ever live:
   `/index.html → /`, `/about.html → /about/`, `/contact.html → /contact/`,
   `/insights.html → /insights/`, `/interview-series.html → /interview-series/`,
   `/university-partnerships.html → /university-partnerships/`.
   Free Yoast has no redirect manager — use the *Redirection* plugin.
7. **Confirm one `<h1>` per page.** Hello Elementor prints its own page title;
   Elementor → Page Settings → **Hide Title** must be on for all six (this was
   already in the pink-fix step, but re-check after any template change).
8. **Check the mobile snippet preview** in Yoast for each page — titles are
   truncated harder on mobile than the desktop preview suggests.
