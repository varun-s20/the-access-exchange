"""Build check for the static sources in wordpress/.

Run it after any phase:  python wordpress/tests/check.py

It exists because this build cannot be verified in a browser from here, so the
things that would otherwise only surface on a live page are asserted instead:
the stylesheet parsing, measured colour contrast, the mobile masthead geometry
that puts the wordmark under the CTA if it drifts, the shared nav matching the
agreed architecture, retired slugs staying gone, the handoff's own copy being
present verbatim, and every class on a page having a rule behind it.

Sections: Phase 0 covers the shared chrome, Phase 1 the home page. Add a block
per phase rather than a file per phase.
"""
import re
import sys
from html.parser import HTMLParser
from pathlib import Path

WP = Path(__file__).resolve().parent.parent
CSS = (WP / "assets" / "global.css").read_text(encoding="utf-8")

fails = []


def words(html):
    """Visible text, tags and entities out, whitespace collapsed.

    Copy assertions are about the words the handoff dictated, not the markup
    they sit in. A line broken across <br> and <em> for typesetting is still
    the same sentence, and the check should say so.
    """
    t = re.sub(r"<!--.*?-->", " ", html, flags=re.S)
    t = re.sub(r"<[^>]+>", " ", t)
    t = (t.replace("&amp;", "&").replace("&nbsp;", " ")
          .replace("&mdash;", "—").replace("&rsquo;", "’"))
    return re.sub(r"\s+", " ", t)


def check(name, ok, detail=""):
    print(f"{'PASS' if ok else 'FAIL'}  {name}{'  - ' + detail if detail else ''}")
    if not ok:
        fails.append(name)


# ══ PHASE 0 · SHARED CHROME ════════════════════════════════════════════════

# ── 1 · stylesheet parses ──────────────────────────────────────────────────
stripped = re.sub(r"/\*.*?\*/", "", CSS, flags=re.S)
check("global.css braces balance", stripped.count("{") == stripped.count("}"),
      f"{stripped.count('{')} open / {stripped.count('}')} close")

# every declaration block ends in a semicolon-or-brace, not a stray colon
bad = [ln for ln in stripped.splitlines() if ln.count("{") > 1 and "}" not in ln]
check("no doubled-up opening braces on one line", not bad, str(bad[:2]))


# ── 2 · tokens are the handoff palette ─────────────────────────────────────
_start = CSS.index(":root{")
_end = CSS.index("\n}", _start)
root, rest = CSS[_start:_end], CSS[_end:]
tokens = dict(re.findall(r"^\s*(--[\w-]+):\s*([^;]+);", root, flags=re.M))
for name, want in [("--paper", "#FAF9F6"), ("--paper-2", "#F3EBDD"),
                   ("--paper-3", "#E8DFCD"), ("--ink", "#0A0A0A"),
                   ("--ink-2", "#4A4844"), ("--ink-3", "#736F68"),
                   ("--accent", "#C6A15B")]:
    check(f"token {name}", tokens.get(name, "").strip().upper() == want,
          f"got {tokens.get(name)!r}")

# a page scope re-declaring a palette token is how half the site quietly keeps
# the old colours through a rebrand - .tae-interviews did exactly that
overrides = [(m.group(1), m.group(2).strip())
             for m in re.finditer(r"^\s*(--(?:paper|ink)[\w-]*):\s*([^;]+);",
                                  rest, flags=re.M)]
# warm means red leads blue. A true neutral (r==g==b) is fine - it has no cast.
cool = [(k, v) for k, v in overrides
        if re.fullmatch(r"#[0-9A-Fa-f]{6}", v)
        and len({v[1:3].lower(), v[3:5].lower(), v[5:7].lower()}) > 1
        and int(v[5:7], 16) >= int(v[1:3], 16)]
check("no page scope re-declares a cool palette token", not cool, str(cool))


# ── 3 · contrast, the numbers written into the token comment ───────────────
def lum(hexs):
    c = [int(hexs[i:i + 2], 16) / 255 for i in (1, 3, 5)]
    c = [x / 12.92 if x <= 0.03928 else ((x + 0.055) / 1.055) ** 2.4 for x in c]
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2]


def ratio(a, b):
    la, lb = lum(a), lum(b)
    hi, lo = max(la, lb), min(la, lb)
    return (hi + 0.05) / (lo + 0.05)


paper, ink = "#FAF9F6", "#0A0A0A"
r3 = ratio("#736F68", paper)
check("--ink-3 clears AA 4.5:1 on --paper", r3 >= 4.5, f"{r3:.2f}:1")
r2 = ratio("#4A4844", paper)
check("--ink-2 clears AA 4.5:1 on --paper", r2 >= 4.5, f"{r2:.2f}:1")
rg = ratio("#C6A15B", ink)
check("gold-on-black CTA clears AA 4.5:1", rg >= 4.5, f"{rg:.2f}:1")
rgp = ratio("#C6A15B", paper)
check("gold-on-paper is decorative only (documented as failing)", rgp < 3.0,
      f"{rgp:.2f}:1 - comment must keep saying so")


# ── 4 · mobile masthead: wordmark must not sit under the CTA ───────────────
# At 360px: --chrome-pad clamps to 18px, hamburger 24px, .mark offset by pad+40.
# CTA is padding 0 12px, .6rem/9.6px uppercase with .1em tracking.
VW = 360
pad = max(18, min(0.04 * VW, 64))
mark_left = pad + 40
mark_font = max(0.94 * 16, min(0.039 * VW, 1.18 * 16))
mark_w = len("The Access Exchange") * mark_font * 0.52   # ~0.52em average advance
mark_right = mark_left + mark_w

cta_font = 0.6 * 16
cta_w = len("Get Involved") * cta_font * 0.72 + 24       # uppercase + .1em tracking
cta_left = VW - pad - cta_w

check("mobile wordmark clears the CTA at 360px", mark_right < cta_left,
      f"wordmark ends {mark_right:.0f}px, CTA starts {cta_left:.0f}px")


# ── 5 · masthead height reserves the nav strip ─────────────────────────────
tight = CSS.replace(" ", "")
check("--mast-full defined as tall + nav",
      "--mast-full:calc(var(--mast-tall)+var(--mast-nav))" in tight)
check(".mast uses --mast-full", "height:var(--mast-full)" in CSS)
check(".mast-spacer uses --mast-full",
      ".mast-spacer{height:var(--mast-full)}" in CSS.replace("\n", ""))
check("--mast-nav collapses under 1080px",
      re.search(r"@media\(max-width:1079px\)\{:root\{--mast-nav:0px\}\}",
                CSS.replace(" ", "")) is not None)


# ── 6 · nav links resolve against the agreed architecture ──────────────────
WANT = {"/", "/interview-series/", "/guests-partners/", "/universities/",
        "/experiences/", "/coaching/", "/about/"}


class Nav(HTMLParser):
    def __init__(self):
        super().__init__()
        self.depth = 0
        self.hrefs = []
        self.cta = []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "nav" and "mast-nav" in (a.get("class") or ""):
            self.depth = 1
        elif tag == "a" and self.depth:
            self.hrefs.append(a.get("href"))
        elif tag == "a" and "cta" in (a.get("class") or ""):
            self.cta.append(a.get("href"))

    def handle_endtag(self, tag):
        if tag == "nav":
            self.depth = 0


header = (WP / "header.html").read_text(encoding="utf-8")
nav = Nav()
nav.feed(header)
check("masthead nav is the 7 agreed pages", set(nav.hrefs) == WANT,
      f"got {nav.hrefs}")
check("persistent CTA points at /get-involved/", nav.cta == ["/get-involved/"],
      f"got {nav.cta}")

# dead slugs must be gone from both shared templates
footer = (WP / "footer.html").read_text(encoding="utf-8")
for dead in ("/insights/", "/university-partnerships/", "/contact/"):
    check(f"retired slug {dead} absent from header+footer",
          dead not in header and dead not in footer)


# ── 7 · aria-current is applied to both navs ───────────────────────────────
js = (WP / "assets" / "global.js").read_text(encoding="utf-8")
# Prettier may run over this file and flip ' to ". Normalise before matching so
# a formatter pass is never mistaken for a behavioural change.
jsq = js.replace('"', "'")
check("global.js marks both navs", "'.veil-nav a, .mast-nav a'" in jsq)


# ══ PHASE 1 · HOME ═════════════════════════════════════════════════════════
print()
home = (WP / "index.html").read_text(encoding="utf-8")

# ── 8 · the handoff's nine homepage sections are all present ───────────────
# Sections 7 and 8 of the brief (Live Experiences, Coaching) share one split
# component, so eight markers cover nine sections.
# CLIENT EDIT (2026-08-20): 04/05 swapped (Universities now 04, the four
# pillars now 05) per client request - see index.html's own section-order
# comment near the top of the file.
for marker in ["01 · HERO", "02 · WHY THE EXCHANGE", "03 · INTERVIEW SERIES",
               "04 · UNIVERSITIES", "05 · WHAT MOVES THROUGH THE EXCHANGE",
               "06 · LEADERS", "07 · LIVE EXPERIENCES + COACHING",
               "08 · EMAIL CAPTURE"]:
    check(f"home section {marker}", marker in home)

# ── 9 · handoff copy is verbatim where the brief dictated it ───────────────
# CLIENT EDIT (2026-08-20): "Go beyond the biography." moved out of home
# (wordpress/index.html) and into templates/home-launch.php - the §03
# heading is now printed by the shortcode itself, not static page markup.
# Checked against that template instead of home for this one phrase.
home_launch = (WP / "plugins/tae-content/templates/home-launch.php").read_text(encoding="utf-8")
check("copy · Go beyond the biography.", "Go beyond the biography." in words(home_launch))

for phrase in [
    "Get closer to the people and ideas",
    "Knowledge becomes more powerful when it moves.",
    "The Access Exchange gives that",
    "Bring The Access Exchange into the room.",
    "Experience should not disappear inside the people who earned it.",
    "The Access Exchange goes beyond the screen.",
    "Turn insight into action.",
    "Stay close to what's coming.",
    "New interviews. New perspectives. New rooms.",
]:
    check(f"copy · {phrase[:44]}", phrase in words(home))

for pillar in ["Knowledge", "Perspective", "Access", "Opportunity"]:
    check(f"pillar · {pillar}", f"<h3>{pillar}</h3>" in home)

for cta in ["Enter The Access Exchange", "Partner with us", "Join The Access Exchange",
            "Bring The Access Exchange to campus", "Be considered as a guest",
            "Explore partnerships", "Explore experiences", "Explore coaching"]:
    check(f"CTA · {cta}", cta in home)

# ── 10 · launch state retires itself ───────────────────────────────────────
# CLIENT EDIT (2026-08-20): §03 used to be two shortcodes
# ([tae_coming_soon until="feature"] + [tae_interviews view="featured"]),
# mutually exclusive by construction. Replaced with the single
# [tae_interviews view="home"], which resolves itself to one of two fully
# self-contained states - a featured pick (interview-featured.php) or the
# pre-launch heading+coming-soon+sneak-peek row (home-launch.php) - see
# inc/shortcodes.php case 'home'. coming-soon.php is unchanged and still used
# as-is by the Interview Series page's own [tae_coming_soon until="any"] call.
check("home shortcode present", '[tae_interviews view="home"]' in home)
sc = (WP / "plugins/tae-content/inc/shortcodes.php").read_text(encoding="utf-8")
check("view=home case registered", "case 'home':" in sc)
check("view=home covers all three states",
      all(t in sc for t in ("'interview-featured'", "'home-recent'", "'home-launch'")))
check("home-launch template exists",
      (WP / "plugins/tae-content/templates/home-launch.php").exists())
check("home-recent template exists",
      (WP / "plugins/tae-content/templates/home-recent.php").exists())
check("home-recent-item template exists",
      (WP / "plugins/tae-content/templates/home-recent-item.php").exists())
check("tae_coming_soon registered", "add_shortcode( 'tae_coming_soon'" in sc)
check("coming-soon hides once featured", "if ( $live ) {" in sc)
check("coming-soon template exists",
      (WP / "plugins/tae-content/templates/coming-soon.php").exists())

# ── 11 · home no longer points at retired pages ────────────────────────────
for dead in ("/insights/", "/university-partnerships/", "/contact/"):
    check(f"home free of {dead}", dead not in home)

# ── 12 · every class the page uses is actually styled ──────────────────────
for cls in (".series", ".form--join"):
    check(f"CSS exists for {cls}", f".tae-home {cls}" in CSS)
# Components a second page picked up get promoted out of the home scope.
for cls in (".soon", ".hero-visual"):
    check(f"CSS exists for {cls} (shared, not page-scoped)",
          f".tae {cls}{{" in CSS and f".tae-home {cls}" not in CSS)
# and nothing styles what the page no longer has
for gone in (".thesis-strip", ".ts-t"):
    check(f"dead CSS {gone} removed", gone not in CSS and gone not in home)

# ── 13 · image loading discipline ──────────────────────────────────────────
# CLIENT EDIT (2026-08-20): hero swapped from <img> to a looped <video> (same
# fetchpriority="high" contract), so the eager-media tally now counts both.
media = re.findall(r"<(?:img|video)\s[^>]*>", home)
check("exactly one eager hero image",
      sum('fetchpriority="high"' in i for i in media) == 1)
imgs = re.findall(r"<img\s[^>]*>", home)
lazy_missing = [i[:60] for i in imgs
                if 'fetchpriority="high"' not in i and 'loading="lazy"' not in i]
check("every below-fold image is lazy", not lazy_missing, str(lazy_missing))

# ── 14 · the opt-in form is wired for its styles and flagged for its id ────
check("join form carries both classes",
      'html_class="form form--join"' in home)
check("join form id still flagged as a placeholder", "REPLACE_ID_JOIN" in home)
# CLIENT EDIT (2026-08-20): the §03 CTA's href="#join" moved from page markup
# into inc/shortcodes.php's case 'home' defaults along with the rest of that
# copy, so it is checked there now instead of in the page source.
check("email capture is the #join anchor the CTAs point at",
      'id="join"' in home and "'href' " in sc and "'#join'" in sc)


# ── 15 · every class on the page has a rule somewhere ──────────────────────
# A restructured page is the easiest place to leave a class behind that no
# longer matches anything. Attribute selectors (data-rv, data-rule) and classes
# the plugin templates supply are excluded.
FROM_PLUGIN = {"feature", "feature-in", "feature-panel", "f-meta", "stand",
               "chapters", "cap", "t", "c", "b", "dot", "soon", "soon-mark",
               "soon-line", "soon-body", "form", "form--join"}
used = set()
for attr in re.findall(r'class="([^"]+)"', home):
    used.update(attr.split())
unstyled = sorted(c for c in used - FROM_PLUGIN
                  if f".{c}" not in CSS)
check("every class used on home is styled", not unstyled, str(unstyled))


# ══ PHASE 2 · INTERVIEW SERIES ═════════════════════════════════════════════
print()
PLUG = WP / "plugins" / "tae-content"
series = (WP / "interview-series.html").read_text(encoding="utf-8")
types = (PLUG / "inc" / "post-types.php").read_text(encoding="utf-8")
meta = (PLUG / "inc" / "meta-boxes.php").read_text(encoding="utf-8")
shorts = (PLUG / "inc" / "shortcodes.php").read_text(encoding="utf-8")
rend = (PLUG / "inc" / "render.php").read_text(encoding="utf-8")

# ── 25 · interviews are real pages now ─────────────────────────────────────
iv = types[types.index("'tae_interview',"):types.index("'tae_insight',")]
check("tae_interview is public", "'public'             => true" in iv)
check("tae_interview is publicly queryable", "'publicly_queryable' => true" in iv)
check("tae_interview lives under /interviews/", "'slug'       => 'interviews'" in iv)
check("tae_interview supports editor + excerpt",
      "'editor'" in iv and "'excerpt'" in iv)
check("episode template exists",
      (PLUG / "templates" / "single-tae_interview.php").exists())
check("episode template is routed", "'tae_interview' => 'templates/single-tae_interview.php'" in types)
check("cards link to the episode, not an archive anchor",
      "return get_permalink( $post );" in rend
      and "'/interview-series/#episodes'" not in rend)

# ── 26 · the handoff's episode fields all have somewhere to live ───────────
episode = (PLUG / "templates" / "single-tae_interview.php").read_text(encoding="utf-8")
for field, token in [
    ("guest name", "tae_guest_name"), ("professional title", "tae_guest_role"),
    ("organization", "tae_org"), ("episode title", "the_title()"),
    ("hook", "tae_standfirst"), ("YouTube embed", "tae_vid"),
    ("key takeaways", "tae_takeaways"), ("chapters", "tae_chapters"),
    ("related", "tae_related"), ("social sharing", "ep-share"),
    ("join CTA", "Join The Access Exchange"),
]:
    check(f"episode page carries {field}", token in episode)
check("tae_org is an editable field", "'tae_org'" in meta)
check("tae_takeaways is an editable field", "'tae_takeaways'" in meta)
check("takeaways get a parser", "function tae_takeaways(" in meta)
check("insights can name their parent interview", "'tae_parent'" in meta)
check("post_select only stores a real interview id",
      "array_key_exists( $candidate, tae_interview_choices() )" in meta)

# ── 27 · every module retires its own launch state ─────────────────────────
check("series page prints the launch state", 'tae_coming_soon until="any"' in series)
check("archive returns nothing when empty",
      "if ( ! $query->posts ) {" in shorts and shorts.count("if ( ! $query->posts ) {") >= 2)
check("stream can drop its own h1 when embedded", "'search'  => 'yes'" in shorts)
check("series page embeds the stream headerless", 'view="stream" search="no"' in series)
# an empty module must take its own section furniture with it
check("section furniture belongs to the shortcodes",
      "function tae_divider(" in shorts and "function tae_stream_head(" in shorts)
check("no orphanable divider markup left on the series page",
      '<div class="divider lab">The archive</div>' not in series
      and '<div class="divider lab">Takeaways</div>' not in series)
check("archive carries its own divider", 'divider="The archive"' in series)
check("takeaways carry their own heading", 'divider="Takeaways"' in series)

# ── 28 · Insights is gone, Takeaways took its place ────────────────────────
check("insights page deleted", not (WP / "insights.html").exists())
check("no .tae-insights scope left", ".tae-insights" not in CSS)
check("takeaways scope exists", ".tae-takeaways" in CSS)
check("insights CPT moved to /takeaways/", "'slug'       => 'takeaways'" in types)
check("series page hosts the takeaways section", 'id="takeaways"' in series)

# ── 29 · the taxonomy swap landed everywhere it had to ─────────────────────
NEW = ("leadership", "founders", "industry", "career", "campus")
OLD = ("engineering", "product", "breaking-in", "levelling-up", "on-campus")
check("seeded categories are the new set", all(f"'{s}'" in types for s in NEW))
fcss = (PLUG / "inc" / "filter-css.php").read_text(encoding="utf-8")
tests = (PLUG / "tests" / "test-tae.php").read_text(encoding="utf-8")
# data/seed.php was the fifth source here and is deleted along with the importer.
for label, text in [("filter-css ids", fcss), ("global.css fallback", CSS),
                    ("php tests", tests),
                    ("post types", types)]:
    # Slug-shaped only. "product" and "engineering" are ordinary English and
    # appear in prose and demo titles; a bare substring search flags those.
    stale = [s for s in OLD
             if re.search(rf"""(['"]){s}\1""", text)]
    check(f"no stale category slug in {label}", not stale, str(stale))

# ── 30 · chapter jumps cannot break the no-request-until-play rule ─────────
check("facade honours a start time",
      "v.dataset.start" in jsq and "'&start=' + start" in jsq)
check("chapters set the time then press play",
      "video.dataset.start = secs" in jsq and "video.click()" in jsq)


# ══ PHASE 3 · GUESTS & PARTNERS ════════════════════════════════════════════
print()
gp = (WP / "guests-partners.html").read_text(encoding="utf-8")
cf7 = (WP / "CF7-SMTP.md").read_text(encoding="utf-8")

# ── 31 · the handoff's copy, verbatim where it dictated it ─────────────────
for phrase in [
    "Bring a perspective worth hearing.",
    "We look for leaders, practitioners and thinkers with experience that can help others see something differently.",
    "Your title tells us where you are. We're interested in what you know now.",
    "Some of your strongest voices are already inside your organisation.",
    "Sponsor an interview. Nominate a leader. Build something meaningful with The Access Exchange.",
]:
    check(f"guests copy · {phrase[:44]}", phrase in words(gp))

for value in ("Thought leadership", "Legacy", "Reach", "Impact"):
    check(f"guest value · {value}", f"<h3>{value}</h3>" in gp)
for option in ("Sponsor an Interview", "Nominate a Leader", "Build a Partnership"):
    check(f"corporate option · {option}", f"<b>{option}</b>" in gp)

# ── 32 · the anchors the rest of the site already points at ────────────────
for anchor in ("guest", "corporate"):
    check(f"#{anchor} anchor exists", f'id="{anchor}"' in gp)
    users = [n for n, h in (("header", header), ("footer", footer),
                            ("home", home), ("series", series))
             if f"/guests-partners/#{anchor}" in h]
    check(f"#{anchor} is linked from elsewhere", users, str(users))

# ── 33 · two forms, two notification categories ────────────────────────────
check("guest form placed", 'html_class="form form--guest"' in gp)
check("corporate form placed", 'html_class="form form--corporate"' in gp)
check("form ids still flagged as placeholders",
      "REPLACE_ID_GUEST" in gp and "REPLACE_ID_CORPORATE" in gp)
check("both forms are specified in CF7-SMTP.md",
      "### 4.8 TAE - Guest consideration" in cf7
      and "### 4.9 TAE - Corporate partnership" in cf7)
check("the two route to separate categories",
      "GUEST - [your-name]" in cf7 and "CORPORATE - [org]" in cf7)
check("both carry the confirmation autoresponder the brief asks for",
      cf7.count("**C · Mail (2) - ON.**") >= 2)
check("the retired be-a-guest form is marked retired", "RETIRED - replaced by 4.8" in cf7)

# Every field the handoff lists, checked inside its own §4.x block - a
# whole-document search would pass on a field belonging to the other form.
guest_spec = cf7[cf7.index("### 4.8"):cf7.index("### 4.9")]
corp_spec = cf7[cf7.index("### 4.9"):cf7.index("### 4.7")]
for f in ("your-name", "role", "org", "email", "linkedin", "location", "area", "why"):
    check(f"guest form field · {f}", re.search(rf"\[\w+\*?\s+{re.escape(f)}\s+id:", guest_spec))
for f in ("your-name", "role", "org", "email", "site", "interest", "leader", "explore"):
    check(f"corporate form field · {f}", re.search(rf"\[\w+\*?\s+{re.escape(f)}\s+id:", corp_spec))

# ── 33b · CF7's idle response div must not paint an empty strip ────────────
# It ships on page load, empty and aria-hidden. Given a background and padding
# unconditionally, it is a bare cream bar under the submit button on every form.
check("the response output is hidden until CF7 fills it",
      ".tae .form>.wpcf7-response-output{display:none}" in CSS)
check("it only paints on a real form state",
      ".tae .form:is(.sent,.invalid,.failed,.spam,.unaccepted,.aborted)" in CSS)

# ── 33c · the menu panel scrolls without showing a scrollbar ───────────────
check("menu panel hides its scrollbar",
      "scrollbar-width:none" in CSS and ".veil-panel::-webkit-scrollbar" in CSS)

# ── 33d · GET INVOLVED sits where the handoff puts it, and only there ──────
# §7 names it as the persistent CTA, so the masthead button stays. The six-route
# list that used to duplicate the Get Involved page inside the menu was ours.
check("persistent CTA is in the masthead", 'class="cta">Get Involved' in header)
check("the menu does not re-list the six routes",
      header.count("/guests-partners/#guest") == 0)
check("Get Involved is not a nav item", "/get-involved/" not in str(nav.hrefs))

# ── 34 · the form base was shared, not copied a fourth time ────────────────
check("shared form base exists", ".tae .form{display:grid" in CSS)
check("guests page uses the base, not its own copy",
      ".tae-guests .field input" not in CSS and "--field-bg:var(--paper)" in CSS)
# Phase 7 finished the consolidation the Phase 3 note promised: there is one
# form implementation on the site and every page drives it with tokens.
legacy = [p for p in ("contact", "interviews", "universities", "guests")
          if f".tae-{p} .field input," in CSS]
check("no page carries its own copy of the form CSS", not legacy, str(legacy))
check("the shared base is the only .form implementation",
      CSS.count(".form{display:grid") == 1)


# ══ PHASE 4 · UNIVERSITIES & INSTITUTIONS ══════════════════════════════════
print()
uni = (WP / "universities.html").read_text(encoding="utf-8")
cf7 = (WP / "CF7-SMTP.md").read_text(encoding="utf-8")

# ── 35 · the page moved, and nothing still points at the old slug ──────────
check("universities.html exists", (WP / "universities.html").exists())
check("university-partnerships.html retired",
      not (WP / "university-partnerships.html").exists())
check("no .tae-partnerships scope left", ".tae-partnerships" not in CSS)
check("the twelve-week term gantt is gone",
      ".term{" not in CSS and "data-bars" not in uni)

# ── 36 · handoff §11 copy, verbatim ────────────────────────────────────────
for phrase in [
    "Bring The Access Exchange to your campus.",
    "Give students direct access to experienced leaders, industry perspective and conversations that make the world beyond the classroom more visible.",
    "Some lessons only experience can teach.",
    "The classroom builds knowledge. The Access Exchange brings students closer to the people who have lived the decisions, changes and challenges behind it.",
    "Bring the experience of industry closer to the people preparing to shape it.",
]:
    check(f"universities copy · {phrase[:44]}", phrase in words(uni))
for fmt in ("Leadership Talk + Q&amp;A", "Moderated Leadership Discussion",
            "Industry Session", "Custom Campus Experience"):
    check(f"format · {fmt[:34]}", f"<b>{fmt}</b>" in uni)
check("primary CTA", "Request an engagement" in uni)
check("secondary CTA", "Explore formats" in uni)

# ── 37 · the outline rail and the sections agree ───────────────────────────
rail = re.findall(r'<a href="#(s\d)" data-to="\1"', uni)
secs = re.findall(r'<section class="sec" id="(s\d)"', uni)
check("outline rail matches the sections it indexes", rail == secs,
      f"rail {rail} vs sections {secs}")

# ── 38 · the FAQ schema and the accordion cannot drift apart ───────────────
# The page comment promises they stay in sync; that promise is worth an assert.
schema_qs = re.findall(r'"@type": "Question", "name": "([^"]+)"', uni)
markup_qs = re.findall(r"<summary><h3>([^<]+)</h3>", uni)
check("every FAQ schema question is in the accordion",
      sorted(schema_qs) == sorted(markup_qs),
      f"{len(schema_qs)} in schema, {len(markup_qs)} in markup")

# ── 39 · the six fields the handoff names for this form, by name ───────────
check("university form placed", 'html_class="form form--university"' in uni)
check("form id still flagged as a placeholder", "REPLACE_ID_UNIVERSITY" in uni)
check("form specified in CF7-SMTP.md", "### 4.10 TAE - University engagement" in cf7)
uni_spec = cf7[cf7.index("### 4.10"):cf7.index("### 4.7")]
for f in ("institution", "audience", "format", "when", "size", "goals"):
    check(f"university form field · {f}",
          re.search(rf"\[\w+\*?\s+{f}\s+id:", uni_spec))
check("university inquiry has its own notification category",
      "UNIVERSITY - [institution]" in cf7)
check("the retired partnership form is marked retired",
      "RETIRED - replaced by 4.10" in cf7)

# ── 40 · the inverse form uses the shared base, not a copy ─────────────────
check("field label and placeholder are tokens",
      "--field-label" in CSS and "--field-ph" in CSS)
check("the dark band drives the base with tokens",
      ".tae-universities .cta-band{" in CSS
      and "--field-ink:var(--paper)" in CSS)
# the band button inverts; the head button must not
check("the inverse button is scoped to the band",
      ".tae-universities .cta-band .btn{background:var(--paper)" in CSS)
check("the article head has a normal dark button",
      ".tae-universities .btn{" in CSS
      and "background:var(--ink);color:var(--paper)" in
          CSS[CSS.index(".tae-universities .btn{"):CSS.index(".tae-universities .btn:hover")])


# ══ PHASE 5 · EXPERIENCES ══════════════════════════════════════════════════
print()
xp = (WP / "experiences.html").read_text(encoding="utf-8")

# ── 41 · handoff §12 copy, verbatim ────────────────────────────────────────
for phrase in [
    "The Access Exchange goes beyond the screen.",
    "Leadership discussions, industry panels, learning experiences and Access Exchange events bring people, ideas, knowledge and opportunity together in the same room.",
]:
    check(f"experiences copy · {phrase[:44]}", phrase in words(xp))
for kind in ("Leadership discussions", "Industry panels",
             "Learning experiences", "Access Exchange events"):
    check(f"experience kind · {kind}", f"<h3>{kind}</h3>" in xp)

# ── 42 · image-led, as the brief asks, without paying for it twice ─────────
imgs = re.findall(r"<img\s[^>]*>", xp)
check("the page is genuinely image-led", len(imgs) >= 6, f"{len(imgs)} images")
check("exactly one eager image",
      sum('fetchpriority="high"' in i for i in imgs) == 1)
check("every other image is lazy",
      all('loading="lazy"' in i for i in imgs if 'fetchpriority="high"' not in i))

# ── 43 · no invented form, no invented CMS ─────────────────────────────────
# §16 lists seven forms and none is an events form; §20 names only the
# Interview Series CMS as a requirement. Both absences are deliberate.
check("no form of its own", "contact-form-7" not in xp)
check("no events post type was invented",
      "tae_event" not in (PLUG / "inc" / "post-types.php").read_text(encoding="utf-8"))
check("no Event schema on a page with no dated events",
      '"@type": "Event"' not in xp)

# ── 44 · the inquiry pathways reach real forms ─────────────────────────────
ROUTES = ("/universities/#enquire", "/guests-partners/#corporate", "/get-involved/")
for r in ROUTES:
    check(f"pathway · {r}", r in xp)
# and those targets must be anchors that actually exist
check("the campus pathway lands on a real anchor", 'id="enquire"' in uni)
check("the partnership pathway lands on a real anchor", 'id="corporate"' in gp)


# ══ PHASE 6 · COACHING, ABOUT, GET INVOLVED ════════════════════════════════
print()
co = (WP / "coaching.html").read_text(encoding="utf-8")
ab = (WP / "about.html").read_text(encoding="utf-8")
gi = (WP / "get-involved.html").read_text(encoding="utf-8")
cf7 = (WP / "CF7-SMTP.md").read_text(encoding="utf-8")

# ── 45 · every page scope used anywhere has CSS behind it ──────────────────
# Phase 2 renamed .tae-insights and single-insight.php kept referencing it for
# two whole phases. Scope classes live in templates as well as pages, so scan
# both. This is the assertion that would have caught it.
sources = list(WP.glob("*.html")) + list((PLUG / "templates").glob("*.php"))
# a template may style its own modifier inline, so both haystacks count
inline = "".join(re.findall(r"<style>(.*?)</style>", "".join(
    f.read_text(encoding="utf-8") for f in sources), re.S))
unscoped = []
for f in sources:
    text = f.read_text(encoding="utf-8")
    for scope in re.findall(r'class="tae ([a-z0-9 -]+)"', text):
        for cls in scope.split():
            if cls.startswith("tae-") and f".{cls}" not in CSS and f".{cls}" not in inline:
                unscoped.append(f"{f.name}:{cls}")
check("every tae-* page scope has CSS", not unscoped, str(sorted(set(unscoped))))

# ── 46 · Coaching · handoff §13 ────────────────────────────────────────────
for phrase in [
    "Turn access into action.",
    "Great information can change how you think. The right guidance can help change what you do next.",
    "Develop the people who help others move forward.",
    "The Access Exchange provides professional coaching designed to help people clarify goals",
    "The Access Exchange provides structured coach training and certification",
]:
    check(f"coaching copy · {phrase[:42]}", phrase in words(co))
for area in ("Career and professional clarity", "Transitions and advancement",
             "Professional positioning and visibility",
             "Relationship and network strategy",
             "Interview and opportunity preparation",
             "Development planning and accountability"):
    check(f"coaching focus · {area[:38]}", area in co)
check("coaching anchors exist", 'id="coaching"' in co and 'id="training"' in co)
check("two separate inquiry paths",
      'form--coaching' in co and 'form--training' in co)

# ── 47 · About · handoff §14 ───────────────────────────────────────────────
for phrase in [
    "Access changes what becomes possible.",
    "The right conversation can change how someone sees an industry.",
    "The Access Exchange exists to help more of those connections happen.",
    "Experience should not disappear inside the people who earned it.",
    "We create places for knowledge, perspective and lived experience to move",
]:
    check(f"about copy · {phrase[:42]}", phrase in words(ab))
check("founder section present", 'class="who"' in ab)
check("founder bio is flagged as unsupplied", "[FOUNDER BIOGRAPHY" in ab)
# single-insight.php borrows this page's vocabulary; renaming silently breaks it
si = (PLUG / "templates" / "single-insight.php").read_text(encoding="utf-8")
for cls in ("sheet", "sheet-head", "spread", "copy", "plate", "facts", "ends"):
    if f'class="{cls}"' in si or f'class="{cls} ' in si:
        check(f"about keeps .{cls}, which single-insight.php borrows",
              f".tae-about .{cls}" in CSS)

# ── 48 · Get Involved · handoff §15 ────────────────────────────────────────
check("get-involved hero", "How do you want to enter The Access Exchange?" in gi)
ROUTES = [("Share your perspective", "/guests-partners/#guest"),
          ("Partner with The Access Exchange", "/guests-partners/#corporate"),
          ("Bring The Access Exchange to campus", "/universities/#enquire"),
          ("Explore coaching", "/coaching/#coaching"),
          ("Explore coach training", "/coaching/#training"),
          ("General inquiry", "#general")]
for label, href in ROUTES:
    check(f"route · {label}", f"<h3>{label}</h3>" in gi and href in gi)
check("only the general route carries a form here",
      gi.count("contact-form-7") == 1 and "form--general" in gi)
check("contact.html retired", not (WP / "contact.html").exists())
check("the direct email is flagged as unsupplied", "BLOCKED: the business email" in gi)

# ── 49 · the seven forms the handoff asks for all exist ────────────────────
for n, name in [("4.8", "Guest consideration"), ("4.9", "Corporate partnership"),
                ("4.10", "University engagement"), ("4.11", "Professional coaching"),
                ("4.12", "Coach training"), ("4.13", "General inquiry")]:
    check(f"CF7 §{n} · {name}", f"### {n} TAE - {name}" in cf7)
check("the email opt-in is the seventh", "REPLACE_ID_JOIN" in home)
check("the old contact forms are retired",
      cf7.count("RETIRED - replaced by 4.13") == 4)
# every placed form must have a spec, and every id must still be a placeholder
placed = set(re.findall(r'html_class="form (form--[a-z]+)"',
                        home + series + gp + uni + co + gi))
check("all seven forms placed across the site", len(placed) == 7, str(sorted(placed)))
ids = re.findall(r'contact-form-7 id="([^"]+)"', home + series + gp + uni + co + gi)
check("no form id was accidentally hard-coded",
      all(i.startswith("REPLACE_ID_") for i in ids), str(ids))


# ══ PHASE 7 · LAUNCH ═══════════════════════════════════════════════════════
print()
# header.html and footer.html are template parts, not pages
PARTS = {"header", "footer"}
PAGES = {f.stem: f.read_text(encoding="utf-8")
         for f in WP.glob("*.html") if f.stem not in PARTS}
priv = PAGES["privacy"]
terms = PAGES["terms"]

# ── 50 · the eight conversions from §20 are all wired ──────────────────────
for ev in ("join_the_exchange", "guest_submission", "corporate_inquiry",
           "university_inquiry", "coaching_inquiry", "coach_training_inquiry",
           "interview_play", "outbound_click"):
    check(f"conversion event · {ev}", f"'{ev}'" in jsq)
check("form events key off the class, not the CF7 id",
      "FORM_EVENTS" in jsq and "contactFormId" not in jsq)
check("analytics is inert until GA exists",
      "typeof window.gtag === 'function'" in jsq
      and "Array.isArray(window.dataLayer)" in jsq)
check("no GA snippet hard-coded into the site",
      "googletagmanager.com" not in jsq and "G-XXXX" not in jsq)
check("a play is counted, not a page view",
      "if (v.classList.contains('on')) return;" in jsq)
# every form class the analytics map knows must actually be placed somewhere
mapped = set(re.findall(r"'(form--[a-z]+)':", jsq))
check("every tracked form exists on a page", mapped == placed,
      f"tracked {sorted(mapped - placed)}, untracked {sorted(placed - mapped)}")

# ── 51 · the legal pages describe THIS site, not a template ────────────────
check("privacy exists", "Privacy Policy" in priv)
check("terms exist", "Terms &amp; Conditions" in terms or "Terms & Conditions" in terms)
for thing in ("Google Analytics", "YouTube", "Google Fonts", "unsubscribe",
              "click-to-load"):
    check(f"privacy covers · {thing}", thing in priv)
check("privacy names the actual form fields it collects",
      "LinkedIn" in priv and "areas of expertise" in priv)
for clause in ("Guest contributions", "Coaching and coach training",
               "Intellectual property", "Limitation of liability"):
    check(f"terms cover · {clause}", clause in terms)
check("both are marked as drafts needing legal review",
      "NOT LEGAL ADVICE" in priv and "NOT LEGAL ADVICE" in terms)
check("footer links reach both", "/privacy/" in footer and "/terms/" in footer)

# ── 52 · launch blockers are flagged, not silently shipped ─────────────────
# These are placeholders that MUST be replaced. Each is asserted so that
# removing the marker without supplying the real value fails here.
BLOCKERS = [
    ("business email", "[BUSINESS EMAIL]", priv + terms),
    ("postal address", "[POSTAL ADDRESS]", priv),
    ("governing law", "[STATE/COUNTRY]", terms),
    ("founder biography", "[FOUNDER BIOGRAPHY", ab),
    ("form ids", "REPLACE_ID_", "".join(PAGES.values())),
]
for label, marker, hay in BLOCKERS:
    check(f"launch blocker still flagged · {label}", marker in hay)

# ── 53 · accessibility floor ───────────────────────────────────────────────
# Contrast is asserted at the token level in §3. These are the structural ones.
for name, html in PAGES.items():
    # the page comments discuss <h1> and <img>; only real markup counts
    live = re.sub(r"<!--.*?-->", "", html, flags=re.S)
    h1s = re.findall(r"<h1[^>]*>", live)
    check(f"{name}: exactly one h1", len(h1s) == 1, f"{len(h1s)} found")
    imgs = re.findall(r"<img\s[^>]*>", live)
    noalt = [i[:60] for i in imgs if "alt=" not in i]
    check(f"{name}: every image has alt text", not noalt, str(noalt[:1]))
# an icon-only control with no accessible name is invisible to a screen reader
icon_btns = re.findall(r"<button[^>]*>\s*<svg", header)
named = re.findall(r'<button[^>]*aria-label="[^"]+"[^>]*>\s*<svg', header)
check("every icon-only button in the chrome is labelled",
      len(icon_btns) == len(named), f"{len(icon_btns)} buttons, {len(named)} labelled")

# ── 54 · the cleanup this build kept promising actually happened ───────────
check("no PARKED components left", "PARKED" not in CSS)
check("the contact page scope is gone", ".tae-contact" not in CSS)
check("deleted views are gone from the shortcode",
      "case 'wall':" not in shorts and "case 'rail':" not in shorts)
for gone in ("interview-wall.php", "interview-rail.php", "interview-watch.php"):
    check(f"orphan template removed · {gone}",
          not (PLUG / "templates" / gone).exists())
# and nothing still asks for a template that no longer exists
# only literal names - 'interview-' . $view is resolved at runtime
tmpl_calls = set(re.findall(r"tae_template\(\s*'([a-z-]+)'\s*,", shorts
                            + (PLUG / "inc" / "ajax.php").read_text(encoding="utf-8")))
missing = [t for t in tmpl_calls if not (PLUG / "templates" / f"{t}.php").exists()]
check("every template a shortcode calls exists", not missing, str(missing))


# ══ STATIC PREVIEW · generated by tools/build-static.py ════════════════════
# The preview only means anything if it cannot drift from the WordPress source.
print()
REPO = WP.parent

# ── 16 · the shared assets are byte-identical copies, not near-copies ──────
for name in ("global.css", "global.js"):
    a, b = WP / "assets" / name, REPO / "assets" / name
    check(f"preview {name} is byte-identical to the WordPress one",
          b.exists() and a.read_bytes() == b.read_bytes())

# ── 17 · every generated page exists and is marked as generated ────────────
GENERATED = ["index.html", "interview-series.html", "guests-partners.html",
             "universities.html", "experiences.html", "coaching.html",
             "about.html", "get-involved.html", "privacy.html", "terms.html"]
pages = {}
for name in GENERATED:
    f = REPO / name
    if not f.exists():
        check(f"preview page {name}", False, "missing - run tools/build-static.py")
        continue
    pages[name] = f.read_text(encoding="utf-8")
    check(f"preview page {name}", "GENERATED by tools/build-static.py" in pages[name])

# ── 18 · nothing shortcode-shaped survived into the preview ────────────────
for name, html in pages.items():
    body = re.sub(r"<!--.*?-->", "", html, flags=re.S)
    left = re.findall(r"\[(?:tae_\w+|contact-form-7)[^\]]*\]", body)
    check(f"{name} has no unexpanded shortcode", not left, str(left[:1]))

# ── 19 · no WordPress-only link survived the rewrite ───────────────────────
for name, html in pages.items():
    wp_links = sorted({h for h in re.findall(r'href="(/[^"]*)"', html)})
    check(f"{name} links are all local", not wp_links, str(wp_links[:3]))

# ── 20 · the chrome really is the same chrome on every page ────────────────
# aria-current is the one sanctioned difference, so strip it before comparing.
def chrome(html, tag):
    m = re.search(rf"<{tag}>(.*?)</{tag}>", html, re.S)
    return re.sub(r'\s*aria-current="page"', "", m.group(1)) if m else None

for tag in ("header", "footer"):
    shapes = {chrome(h, tag) for h in pages.values()}
    check(f"{tag} identical across all {len(pages)} preview pages",
          len(shapes) == 1 and None not in shapes, f"{len(shapes)} variants")

# ── 21 · exactly one nav item is marked current, and only where it should be ─
IN_NAV = {"index.html", "interview-series.html", "guests-partners.html",
          "universities.html", "experiences.html", "coaching.html", "about.html"}
for name, html in pages.items():
    marked = sorted(set(re.findall(r'href="([^"]*)" aria-current="page"', html)))
    if name in IN_NAV:
        check(f"{name} marks itself current", marked == [name], str(marked))
    else:
        check(f"{name} marks nothing current (not in the nav)", not marked, str(marked))

# ── 22 · the preview's search index points at files, not permalinks ────────
idx = pages.get("index.html", "")
m = re.search(r"window\.TAE_INDEX=\[(.*?)\];", idx, re.S)
check("preview publishes its own TAE_INDEX", m is not None)
# An empty index is truthy, so `window.TAE_INDEX || [ …literal… ]` would use it
# and site search would answer "nothing matches" to every query. It shipped that
# way once, after Prettier reformatted the literal out from under the parser.
entries = idx.count("{t:")
check("the search index is not empty", entries >= 8, f"{entries} entries")
if m:
    bad = [u for u in re.findall(r'u:"([^"]*)"', m.group(1)) if u.startswith("/")]
    check("every search result is a local file", not bad, str(bad[:3]))

# ── 23 · home carries the Phase 1 build, not the old prototype ─────────────
check("preview home is the rebuilt page",
      "Get closer to the people and ideas" in idx
      and "Career advice in tech" not in idx)
# CLIENT EDIT (2026-08-20): the card now ships with a default sneak-peek
# image (inc/settings.php tae_teaser_image), so the preview renders the
# split `class="soon soon-wrap"` variant rather than the bare `class="soon"`
# one - both are still exactly one card.
check("preview home renders the coming-soon card",
      len(re.findall(r'<div class="soon(?:"| )', idx)) == 1)
check("preview home renders the opt-in form",
      'class="wpcf7-form form form--join"' in idx)


# ── 24 · the generated markup is actually well-formed ──────────────────────
# A generator that splices markup fails by leaving a container unclosed, and an
# unclosed div silently swallows the rest of the page.
VOID = {"area", "base", "br", "col", "embed", "hr", "img", "input", "link",
        "meta", "param", "source", "track", "wbr"}


class Balance(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.stack, self.errors = [], []

    def handle_starttag(self, tag, attrs):
        if tag not in VOID:
            self.stack.append(tag)

    def handle_startendtag(self, tag, attrs):
        pass

    def handle_endtag(self, tag):
        if tag in VOID:
            return
        if not self.stack:
            self.errors.append(f"stray </{tag}>")
        elif self.stack[-1] == tag:
            self.stack.pop()
        elif tag in self.stack:
            while self.stack and self.stack.pop() != tag:
                pass
            self.errors.append(f"</{tag}> closed across an open tag")
        else:
            self.errors.append(f"stray </{tag}>")


for name, html in pages.items():
    b = Balance()
    b.feed(html)
    problems = b.errors + ([f"unclosed {b.stack}"] if b.stack else [])
    check(f"{name} is balanced markup", not problems, str(problems[:2]))


# ── 50 · the handoff audit's findings, each one pinned ─────────────────────
# Everything below closed a gap between what the handoff asks for and what the
# plugin did. Each check is here so the gap cannot quietly reopen.

query = (PLUG / "inc" / "query.php").read_text(encoding="utf-8")
single = (PLUG / "templates" / "single-insight.php").read_text(encoding="utf-8")
settings = (PLUG / "inc" / "settings.php").read_text(encoding="utf-8")
schema = (PLUG / "inc" / "schema.php").read_text(encoding="utf-8")
archive = (PLUG / "templates" / "interview-archive.php").read_text(encoding="utf-8")

# Handoff §9, "Related clips / written takeaways". tae_parent was saved by the
# meta box and read by nothing at all, so this was the one episode-template
# bullet with no implementation behind it.
check("tae_parent is read, not only written", "function tae_children(" in query)
check("takeaways resolve their interview", "function tae_parent(" in query)
check("episode page lists what came out of it",
      "tae_children(" in episode and "From this interview" in episode)
check("takeaway page links back to its interview",
      "tae_parent(" in single and "Watch the full interview" in single)
# An interview carries no tae_topic term, so the old topic-only tae_related()
# could never return anything and fell through to "recent interviews" every time.
check("related honours a chosen next interview", "'tae_next'" in query or "tae_next" in query)
check("watch-next is an editable field", "'tae_next'" in meta)

# build-static.py lists both of these in RETIRED, but check.py reads the
# generated pages and this template has no static twin - so two dead buttons
# shipped on every takeaway page.
# In an href, not in prose: the template's comment explains what these two used
# to be, and that history is worth keeping.
for dead in ("/insights/", "/university-partnerships/"):
    check(f"no retired URL {dead} in the takeaway template",
          f'href="{dead}"' not in single)

# OWNER-HANDOFF §9 promises the owner a revision history.
check("interviews and takeaways keep revisions", types.count("'revisions'") == 2)

# Registered for the wall, guest rail and watch poster - all three deleted in
# Phase 7. Generated on every upload, printed nowhere.
for dead in ("tae-card", "tae-rail", "tae-watch"):
    check(f"unused crop {dead} is gone", f"'{dead}'" not in types)

# Controls that did nothing: a slot whose section was deleted, and a flag whose
# shortcode was never placed on a page.
# The field key, quoted. Both are named in comments explaining why they went.
check("no slot for a deleted section", "'tae_slot_watch'" not in meta)
check("no flag for an unplaced shortcode", "'tae_start_here'" not in meta)
check("insights-start template removed",
      not (PLUG / "templates" / "insights-start.php").exists())

# The likeliest owner mistake: pasting the address bar into a field labelled
# "the ID only". It failed silently - poster fine, player empty.
check("the video field takes a pasted URL", "function tae_youtube_id(" in meta)
check("the video field is wired to it", "'type'  => 'youtube'" in meta)

# Handoff §23.5: the channel is not confirmed yet, so it cannot live in a template.
check("channel URL is a setting", "tae_option( 'tae_channel_url' )" in archive)
check("join CTA is a setting", "tae_option( 'tae_join_url' )" in episode)
# A fresh install has no options row and must render what the prototype rendered.
check("channel default is unchanged",
      "https://www.youtube.com/@theaccessexchange" in settings)
check("join default is unchanged", "'/#join'" in settings)

# Handoff §20 asks for indexing readiness. Yoast does not know an interview is a
# video, and the video result is the surface that matters for an interview series.
check("episodes emit VideoObject", "'VideoObject'" in schema)
check("the guest is named in the schema", "'actor'" in schema)
check("duration is converted for schema", "function tae_iso_duration(" in schema)

# Both bases 404'd: has_archive is false and the listing is an Elementor page.
check("bare rewrite bases redirect", "function tae_redirect_bare_bases(" in types)

# interview_play never fired: §06 sets .on inside its click handler and boot()
# runs lazyVideo before analytics, so the guard was always true by the time the
# per-element analytics listener read it.
# jsq has every double quote folded to a single one - see its definition.
check("play tracking reads .on before section 06 writes it",
      "send('interview_play'" in jsq
      and "true," in jsq.split("send('interview_play'")[1][:400])
check("play tracking is delegated, not per element",
      "$$('[data-yt]').forEach" not in jsq.split("function analytics()")[1])

# Deleted: one Tools-menu click from putting the prototype's placeholder copy on
# a live site, for an import that runs once.
check("importer removed", not (PLUG / "inc" / "importer.php").exists())
check("seed data removed", not (PLUG / "data" / "seed.php").exists())
check("nothing still requires the importer", "importer.php" not in
      (PLUG / "tae-content.php").read_text(encoding="utf-8").split("/*")[0])


print()
if fails:
    print(f"{len(fails)} FAILED: {', '.join(fails)}")
    sys.exit(1)
print("all checks passed")
