"""Generate the static preview at the repo root from the WordPress sources.

    python tools/build-static.py

WHY THIS EXISTS
The deliverable is a WordPress build, and its page bodies are full of shortcodes
that only render inside WordPress. That makes the real thing impossible to look
at while it is being built. This produces a browsable copy at the repo root from
the same files, so the preview cannot drift from what will actually ship.

THE DIRECTION IS ONE-WAY. wordpress/ is the source of truth. Nothing here ever
edits it, and no fix belongs here that should have been made there. If the
preview looks wrong, the WordPress source is wrong.

HOW IT STAYS HONEST
  · assets/global.css and assets/global.js are byte-identical copies of the
    WordPress ones. Never edited in transit - tests/check.py asserts it.
  · The chrome is wordpress/header.html and wordpress/footer.html verbatim,
    apart from the two things WordPress does at runtime: relative links, and
    aria-current on the active nav item.
  · Page bodies come from wordpress/<page>.html with shortcodes expanded by the
    registry below, which mirrors what each PHP template prints.

BODY SOURCES
  "wp"     body from wordpress/<page>.html - for pages a phase has rebuilt
  "root"   body from the existing root <main> - for pages still awaiting their
           phase. Their markup is already the static form of the same content,
           so re-deriving it would only be a chance to introduce differences.
           Each one flips to "wp" when its phase lands.
"""

import re
import shutil
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
WP = ROOT / "wordpress"


# ── URL map ────────────────────────────────────────────────────────────────
# WordPress pretty permalink → the flat file the preview serves. Applied to
# hrefs in the chrome and to the search index.
URLS = {
    "/": "index.html",
    "/interview-series/": "interview-series.html",
    "/guests-partners/": "guests-partners.html",
    "/universities/": "universities.html",
    "/experiences/": "experiences.html",
    "/coaching/": "coaching.html",
    "/about/": "about.html",
    "/get-involved/": "get-involved.html",
    "/privacy/": "privacy.html",
    "/terms/": "terms.html",
}

# Pages this build has retired. Kept out of URLS so a surviving link to one is a
# hard failure in check.py rather than a quiet 404 on the live site.
RETIRED = ("/insights/", "/university-partnerships/", "/contact/")


def to_local(url):
    """Rewrite one WordPress path, preserving any #fragment."""
    path, sep, frag = url.partition("#")
    if path in URLS:
        return URLS[path] + sep + frag
    if path == "" and sep:            # a bare "#anchor" stays put
        return url
    return url


# ── pages ──────────────────────────────────────────────────────────────────
# nav is the permalink this page answers to, used to place aria-current.
PAGES = [
    dict(out="index.html", nav="/", scope="tae-home", body="wp", src="index.html",
         title="The Access Exchange - Connecting you to the people, knowledge and opportunities that make a difference",
         desc="Access to accomplished leaders through our Interview Series, university engagements, live experiences and professional coaching."),
    dict(out="interview-series.html", nav="/interview-series/", scope="tae-interviews",
         body="wp", src="interview-series.html",
         title="Interview Series - In-depth interviews with people worth hearing | The Access Exchange",
         desc="In-depth interviews with leaders, founders, practitioners and subject-matter experts on what their experience actually taught them."),
    dict(out="guests-partners.html", nav="/guests-partners/", scope="tae-guests",
         body="wp", src="guests-partners.html",
         title="Guests & Partners - Bring a perspective worth hearing | The Access Exchange",
         desc="Be considered as a guest on the Interview Series, sponsor an interview, nominate a leader, or build a partnership with The Access Exchange."),
    dict(out="universities.html", nav="/universities/", scope="tae-universities",
         body="wp", src="universities.html",
         title="Universities & Institutions - Bring The Exchange to your campus | The Access Exchange",
         desc="Leadership talks, moderated discussions, industry sessions and custom campus programming that give students direct access to experienced leaders."),
    dict(out="experiences.html", nav="/experiences/", scope="tae-experiences",
         body="wp", src="experiences.html",
         title="Experiences - Leadership discussions, panels and live events | The Access Exchange",
         desc="Leadership discussions, industry panels, learning experiences and Access Exchange events that bring people, ideas and opportunity into the same room."),
    dict(out="coaching.html", nav="/coaching/", scope="tae-coaching",
         body="wp", src="coaching.html",
         title="Coaching - Professional coaching and coach training | The Access Exchange",
         desc="Personalised professional coaching for clarity, positioning and transitions, plus structured coach training and certification."),
    dict(out="about.html", nav="/about/", scope="tae-about",
         body="wp", src="about.html",
         title="About - Access changes what becomes possible | The Access Exchange",
         desc="Why The Access Exchange exists: to help knowledge, perspective and lived experience move between the people who have it and the people who need it."),
    dict(out="privacy.html", nav="/privacy/", scope="tae-legal",
         body="wp", src="privacy.html",
         title="Privacy Policy - The Access Exchange",
         desc="How The Access Exchange collects, uses and protects personal data."),
    dict(out="terms.html", nav="/terms/", scope="tae-legal",
         body="wp", src="terms.html",
         title="Terms & Conditions - The Access Exchange",
         desc="The terms that govern use of theaccessexchange.com."),
    dict(out="get-involved.html", nav="/get-involved/", scope="tae-involved",
         body="wp", src="get-involved.html",
         title="Get Involved - How do you want to enter The Exchange? | The Access Exchange",
         desc="Share your perspective, partner with The Exchange, bring it to your campus, explore coaching or coach training, or ask us anything else."),
]

# Pages the nav points at that no phase has built yet. Empty since Phase 7 -
# every page in the architecture is real now. Kept because the mechanism is what
# makes it safe to link at a page before building it.
STUBS = []


# ── shortcode registry ─────────────────────────────────────────────────────
# One entry per shortcode the rebuilt pages use, printing what the matching PHP
# template prints. Add to this as each phase introduces a module; an unhandled
# shortcode is a hard error rather than a silent gap in the preview.

def sc_coming_soon(a):
    """templates/coming-soon.php. The preview is always the pre-launch state -
    there is no featured interview in a static build to retire it."""
    body = f'\n\t\t<p class="soon-body">{a["body"]}</p>' if a.get("body") else ""
    cta = (f'\n\t\t<a href="{to_local(a.get("href", "#"))}" class="btn">{a["cta"]}</a>'
           if a.get("cta") else "")
    return ('<div class="soon">\n'
            '\t\t<span class="soon-mark lab" aria-hidden="true"></span>\n'
            f'\t\t<p class="soon-line">{a.get("line", "")}</p>'
            f'{body}{cta}\n'
            '\t</div>')


def sc_interviews(a):
    """Every interview view is empty in the preview, exactly as it is on the
    live site before the first interview is published.

    The preview shows the LAUNCH state, because that is what ships. cover and
    the slot views return '' when no interview holds the slot; wall and archive
    return '' when the query is empty. Both are the real WordPress behaviour,
    not a stand-in for it.

    To preview a populated page, publish an interview in WordPress. Rendering
    fixtures here would mean porting six PHP templates to Python and keeping
    both in step, to show something no visitor will see at launch.
    """
    view = a.get("view", "wall")
    if view in ("featured", "watch", "cover", "wall", "archive", "rail"):
        return ""
    raise KeyError(f'[tae_interviews view="{view}"] has no static renderer yet')


def sc_insights(a):
    """Same reasoning: the takeaways stream renders nothing until a takeaway is
    published, and the shortcode returns '' in that state."""
    view = a.get("view", "stream")
    if view in ("stream", "start"):
        return ""
    raise KeyError(f'[tae_insights view="{view}"] has no static renderer yet')


# Field lists mirror the CF7 specs in wordpress/CF7-SMTP.md. Kept in the same
# order so the preview and the real form read identically.
#   (name, label, type, wide, placeholder-or-options)
GUEST_FIELDS = [
    ("your-name", "Name", "text", False, "Your name"),
    ("role", "Professional title", "text", False, "Chief Operating Officer"),
    ("org", "Organization", "text", False, "Where you do that job"),
    ("email", "Email", "email", False, "you@example.com"),
    ("linkedin", "LinkedIn", "url", False, "linkedin.com/in/..."),
    ("location", "Location", "text", False, "City, country"),
    ("area", "Areas of expertise", "text", True,
     "The two or three things you are genuinely worth asking about"),
    ("why", "What perspective would you bring to The Exchange?", "textarea", True,
     "A few lines is plenty. What have you learned that most people in your position have not?"),
]

CORPORATE_FIELDS = [
    ("your-name", "Name", "text", False, "Your name"),
    ("role", "Title", "text", False, "Your role"),
    ("org", "Organization", "text", False, "Company name"),
    ("email", "Work email", "email", False, "you@company.com"),
    ("site", "Company website", "url", False, "company.com"),
    ("interest", "Partnership interest", "select", False,
     ["Select one", "Sponsor an Interview", "Nominate a Leader",
      "Build a Partnership", "Not sure yet"]),
    ("leader", "Leader being nominated, if applicable", "text", True,
     "Name and role - leave blank if this is not a nomination"),
    ("explore", "What would you like to explore?", "textarea", True,
     "What you have in mind, and anything that would help us come back usefully."),
]

UNIVERSITY_FIELDS = [
    ("your-name", "Name", "text", False, "Your name"),
    ("role", "Your role", "text", False, "Careers Manager, Head of Department..."),
    ("institution", "Institution", "text", False, "University or organisation"),
    ("email", "Email", "email", False, "you@institution.edu"),
    ("audience", "Audience", "text", False,
     "Who would be in the room - course, year, society"),
    ("format", "Requested format", "select", False,
     ["Select a format", "Leadership Talk + Q&A", "Moderated Leadership Discussion",
      "Industry Session", "Custom Campus Experience", "Not sure yet"]),
    ("when", "Preferred timing, if known", "text", False, "A term, a month, or a date"),
    ("size", "Expected attendance, if known", "text", False, "A rough number is fine"),
    ("goals", "Goals and topics", "textarea", True,
     "What you want students to come away with, and any themes you already have in mind."),
]

COACHING_FIELDS = [
    ("your-name", "Name", "text", False, "Your name"),
    ("email", "Email", "email", False, "you@example.com"),
    ("role", "Where you are professionally", "text", True,
     "Your role, and the kind of organisation"),
    ("focus", "What would you want to work on?", "select", True,
     ["Select a focus", "Career and professional clarity",
      "Transitions and advancement", "Professional positioning and visibility",
      "Relationship and network strategy",
      "Interview and opportunity preparation",
      "Development planning and accountability", "More than one of these"]),
    ("goal", "What would make this worth doing?", "textarea", True,
     "A few lines. What would have to change for this to have been worth your time?"),
]

TRAINING_FIELDS = [
    ("your-name", "Name", "text", False, "Your name"),
    ("email", "Email", "email", False, "you@example.com"),
    ("stage", "Where you are with coaching", "select", True,
     ["Select one", "Already coaching, seeking certification",
      "Moving toward coaching",
      "Building coaching capability inside an organisation",
      "Exploring the idea"]),
    ("why", "What are you hoping to get from the programme?", "textarea", True,
     "A few lines about who you want to be able to help, and how."),
]

GENERAL_FIELDS = [
    ("your-name", "Name", "text", False, "Your name"),
    ("email", "Email", "email", False, "you@example.com"),
    ("topic", "What is this about?", "select", True,
     ["Select one", "Press or media", "Speaking or moderating", "Collaboration",
      "Careers and working with us", "Something else"]),
    ("message", "Your message", "textarea", True,
     "As much or as little as you like."),
]

CF7_FORMS = {
    "form--coaching": (COACHING_FIELDS, "Request coaching",
                       "Replies come from a person, within two working days"),
    "form--training": (TRAINING_FIELDS, "Explore coach training",
                       "We reply within two working days"),
    "form--general": (GENERAL_FIELDS, "Send message",
                      "We read every message ourselves"),
    "form--university": (UNIVERSITY_FIELDS, "Request an engagement",
                         "No cost to ask. We reply within two working days"),
    "form--guest": (GUEST_FIELDS, "Submit for consideration",
                    "We read every submission ourselves"),
    "form--corporate": (CORPORATE_FIELDS, "Send inquiry",
                        "We reply within two working days"),
}


def _field(name, label, kind, wide, extra, idx):
    fid = f"f{idx}-{name}"
    cls = "field field--wide" if wide else "field"
    if kind == "textarea":
        control = f'<textarea id="{fid}" name="{name}" placeholder="{extra}"></textarea>'
    elif kind == "select":
        opts = "".join(f'<option>{o}</option>' for o in extra)
        control = f'<select id="{fid}" name="{name}">{opts}</select>'
    else:
        control = (f'<input type="{kind}" id="{fid}" name="{name}" '
                   f'placeholder="{extra}">')
    return (f'<div class="{cls}">'
            f'<label class="lab" for="{fid}">{label}</label>'
            f'<span class="wpcf7-form-control-wrap" data-name="{name}">{control}</span>'
            f'</div>')


def sc_cf7(a):
    """CF7's own output shape.

    The classes matter: global.css §11b and the per-page submit rules key off
    .wpcf7 and the html_class, so a preview form that omitted them would look
    nothing like the real one.

    The forms are INERT here - no action, no method. This is a preview of the
    layout, not a working endpoint, and a form that silently posted nowhere
    would be worse than one that plainly does not submit.
    """
    cls = a.get("html_class", "")

    if "form--join" in cls:
        return ('<div class="wpcf7">\n'
                f'\t\t<form class="wpcf7-form {cls}" novalidate>\n'
                '\t\t\t<span class="wpcf7-form-control-wrap" data-name="your-email">\n'
                '\t\t\t\t<input type="email" name="your-email" placeholder="Your email address" required>\n'
                '\t\t\t</span>\n'
                '\t\t\t<input type="submit" value="Join The Exchange" class="wpcf7-submit btn">\n'
                '\t\t\t<div class="wpcf7-response-output" aria-hidden="true"></div>\n'
                '\t\t</form>\n'
                '\t</div>')

    for key, (fields, submit, note) in CF7_FORMS.items():
        if key in cls:
            idx = key.split("--")[1][:2]
            rows = "\n\t\t\t".join(
                _field(n, l, k, w, x, idx) for n, l, k, w, x in fields)
            return ('<div class="wpcf7">\n'
                    f'\t\t<form class="wpcf7-form {cls}" novalidate>\n'
                    f'\t\t\t{rows}\n'
                    '\t\t\t<div class="form-foot">\n'
                    f'\t\t\t\t<p class="lab">{note}</p>\n'
                    f'\t\t\t\t<input type="submit" value="{submit}" class="wpcf7-submit btn">\n'
                    '\t\t\t</div>\n'
                    '\t\t\t<div class="wpcf7-response-output" aria-hidden="true"></div>\n'
                    '\t\t</form>\n'
                    '\t</div>')

    raise KeyError(f'[contact-form-7 html_class="{cls}"] has no static renderer yet')


SHORTCODES = {
    "tae_coming_soon": sc_coming_soon,
    "tae_interviews": sc_interviews,
    "tae_insights": sc_insights,
    "contact-form-7": sc_cf7,
}

SC_RE = re.compile(r"\[(" + "|".join(map(re.escape, SHORTCODES)) + r")([^\]]*)\]")
ATTR_RE = re.compile(r'(\w[\w-]*)="([^"]*)"')


COMMENT_RE = re.compile(r"<!--.*?-->", re.S)


def expand(markup):
    """Expand shortcodes outside HTML comments only.

    The page comments document the shortcodes they sit next to - expanding those
    would inject markup into a comment and lose the note that explains it.
    """
    def one(m):
        name, raw = m.group(1), m.group(2)
        return SHORTCODES[name](dict(ATTR_RE.findall(raw)))

    parts, last, out = [], 0, []
    for c in COMMENT_RE.finditer(markup):
        parts.append((markup[last:c.start()], True))
        parts.append((c.group(0), False))
        last = c.end()
    parts.append((markup[last:], True))

    for text, live in parts:
        out.append(SC_RE.sub(one, text) if live else text)
    joined = "".join(out)

    leftover = re.findall(r"\[(tae_\w+|contact-form-7)[^\]]*\]",
                          COMMENT_RE.sub("", joined))
    if leftover:
        raise KeyError(f"unhandled shortcode(s): {sorted(set(leftover))}")
    return joined


# ── assembly ───────────────────────────────────────────────────────────────

def relink(markup):
    """Every WordPress permalink becomes the flat file that serves it.

    Applies to page bodies as well as the chrome - the home page's own CTAs
    point at /coaching/ and friends just as the nav does.
    """
    return re.sub(r'\bhref="([^"]*)"',
                  lambda m: f'href="{to_local(m.group(1))}"', markup)


def mark_current(markup, nav):
    """Stamp aria-current on the active nav item.

    global.js does this at runtime by comparing href to location.pathname, which
    cannot work against flat files. Doing it at build time is the one sanctioned
    difference between the chrome here and the chrome in WordPress - and only
    inside the two nav lists, never on the CTA or the wordmark.
    """
    target = URLS[nav]

    def mark(m):
        return (m.group(0)[:-1] + ' aria-current="page">'
                if m.group(1) == target else m.group(0))

    for block in (r'<nav class="mast-nav"[^>]*>.*?</nav>', r'<ul class="veil-nav">.*?</ul>'):
        m = re.search(block, markup, re.S)
        if m:
            markup = markup.replace(
                m.group(0), re.sub(r'<a href="([^"]*)"[^>]*>', mark, m.group(0)))
    return markup


def localise(markup, nav):
    return mark_current(relink(markup), nav)


def site_index(js_literal_source):
    """Reuse the plugin's own extension point.

    global.js reads `window.TAE_INDEX || [ …literal… ]`. Publishing the rewritten
    index here means global.js can be copied byte-for-byte instead of patched.
    """
    rows = re.findall(r"\{ t: '(.*?)',\s*d: '(.*?)',\s*u: '(.*?)' \}", js_literal_source)
    out = ",".join(
        "{{t:{!r},d:{!r},u:{!r}}}".format(t, d, to_local(u)).replace("'", '"')
        for t, d, u in rows
    )
    return "window.TAE_INDEX=[" + out + "];"


def page(title, desc, scope, nav, body, header, footer, index_js):
    return f"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{title}</title>
<meta name="description" content="{desc}">
<meta name="theme-color" content="#FAF9F6">
<meta property="og:type" content="website">
<meta property="og:title" content="The Access Exchange">
<meta property="og:description" content="{desc}">
<!-- GENERATED by tools/build-static.py from wordpress/. Do not edit by hand:
     the next build overwrites it. Change the WordPress source instead. -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://images.pexels.com" crossorigin>
<link rel="stylesheet" href="assets/global.css">
</head>

<body>

<header>
{header}
</header>

<main>
{body}
</main>

<footer>
{footer}
</footer>

<script>{index_js}</script>
<script src="assets/global.js" defer></script>
</body>
</html>
"""


STUB_BODY = """<div class="tae tae-home">
  <section class="series">
    <div class="shell">
      <div class="tag lab"><span class="s">-</span><span>Not built yet</span></div>
      <h2 class="h2">{name}</h2>
      <p class="lede">This page is scheduled for <strong>{phase}</strong> of the build plan. The
      navigation points here already so the structure can be reviewed end to end.</p>
      <div class="soon">
        <span class="soon-mark lab" aria-hidden="true"></span>
        <p class="soon-line">{phase} builds this page.</p>
        <p class="soon-body">See docs/BUILD-PLAN.md for what it will contain.</p>
        <a href="index.html" class="btn">Back to the home page</a>
      </div>
    </div>
  </section>
</div>"""


def main():
    header_src = (WP / "header.html").read_text(encoding="utf-8")
    footer_src = (WP / "footer.html").read_text(encoding="utf-8")
    js_src = (WP / "assets" / "global.js").read_text(encoding="utf-8")
    index_js = site_index(js_src)

    # byte-identical copies - the whole point of the exercise
    (ROOT / "assets").mkdir(exist_ok=True)
    for name in ("global.css", "global.js"):
        shutil.copyfile(WP / "assets" / name, ROOT / "assets" / name)

    written = []

    for p in PAGES:
        if p["body"] == "wp":
            body = expand((WP / p["src"]).read_text(encoding="utf-8"))
        else:
            existing = (ROOT / p["out"]).read_text(encoding="utf-8")
            m = re.search(r"<main>(.*)</main>", existing, re.S)
            if not m:
                raise SystemExit(f"{p['out']}: no <main> to lift a body from")
            inner = m.group(1).strip()
            # After the first build the body carries its own scope wrapper, and
            # this reads its own output. Wrapping again would nest them.
            body = (inner if inner.startswith('<div class="tae ')
                    else f'<div class="tae {p["scope"]}">\n{inner}\n</div>')

        (ROOT / p["out"]).write_text(
            page(p["title"], p["desc"], p["scope"], p["nav"], relink(body),
                 localise(header_src, p["nav"]), localise(footer_src, p["nav"]),
                 index_js),
            encoding="utf-8")
        written.append(p["out"])

    for out, nav, name, phase in STUBS:
        (ROOT / out).write_text(
            page(f"{name} - The Access Exchange",
                 f"{name}. Scheduled for {phase} of the build.",
                 "tae-home", nav, STUB_BODY.format(name=name, phase=phase),
                 localise(header_src, nav), localise(footer_src, nav), index_js),
            encoding="utf-8")
        written.append(out)

    print(f"wrote assets/global.css, assets/global.js and {len(written)} pages:")
    for w in written:
        print(f"  {w}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
