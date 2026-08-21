"""DISPOSABLE. 27 fake interviews, and Python copies of the four interview
templates, so the populated site can be looked at before a single real episode
exists.

    python tools/build-static.py --demo     # preview WITH the dummy library
    python tools/build-static.py            # back to the launch state, files removed

WHY IT IS SEPARATE
build-static.py renders the LAUNCH state, which is what ships: every interview
view returns '' because no interview is published. That is correct and it stays
correct - this file is loaded only when --demo is passed, and the default build
deletes everything it wrote.

WHAT IS NOT REAL HERE
  · Pagination. The live archive pages server-side through admin-ajax and
    tae-archive.js; a flat file has no server, so all 27 tiles are in the DOM and
    a 20-line script hides all but the first twelve. What you are looking at is
    the pager's BEHAVIOUR, not its mechanism.
  · Every episode shares one YouTube id, and posters cycle through the stock
    photographs already used elsewhere on the site.
  · Eight episodes carry chapters and key takeaways; the rest do not, because
    both fields are optional and a library where every record is complete is not
    the library anybody actually ends up with.

TO DELETE THIS FOR GOOD: run the plain build once, then remove this file and the
--demo branch in build-static.py.
"""

# ── the library ────────────────────────────────────────────────────────────
# slots: cover (cover story), minor (the two cards beside it), feature (home).
# watched: rank in the Most watched rail. Everything else is optional.

Y = "aqz-KE-bpKQ"  # one video id for all of them

PHOTOS = [
    ("photo-1573496546038-82f9c39f6365", "Two people in a quiet one-to-one conversation by a window"),
    ("photo-1594122230689-45899d9e6f69", "Two people in a moderated discussion on stage"),
    ("photo-1675609459162-41621fb7bee3", "Two people in conversation"),
    ("photo-1653566031535-bcf33e1c2893", "People talking together after a session"),
    ("photo-1515187029135-18ee286d815b", "A speaker with a small seated group in a warm room"),
    ("photo-1478737270239-2f02b77fc618", "A microphone in a recording studio"),
    ("photo-1517048676732-d65bc937f952", "Hands taking notes around a shared table"),
    ("photo-1544531586-fde5298cdd40", "A speaker facing a full auditorium"),
    ("photo-1524178232363-1fb2b075b655", "An audience listening closely during a session"),
    ("photo-1540575467063-178a50c2df87", "A speaker addressing a full lecture hall"),
]

SIZES = {
    "tae-tile": (640, 360),
    "tae-cover": (1400, 875),
    "tae-minor": (560, 420),
    "tae-feature": (1800, 1013),
}

CATEGORIES = [
    ("career", "Career &amp; Transitions"),
    ("founders", "Founders &amp; Builders"),
    ("industry", "Industry &amp; Craft"),
    ("leadership", "Leadership"),
    ("campus", "On Campus"),
]

INTERVIEWS = [
    dict(
        ep=27, date="2026-08-06", cat="leadership", slug="marcus-bell-turnaround",
        title="The first ninety days of a turnaround",
        guest="Marcus Bell", role="Chief Executive", org="Halden Industries",
        duration="48:12", slots=["cover", "feature"],
        stand="The three decisions he made before he understood the business, and the one he wishes he had waited on.",
        chapters=[("02:40", "Walking into a company nobody wanted to run"),
                  ("13:05", "The meeting where he stopped talking and started counting"),
                  ("27:18", "Cutting the thing everybody was proud of"),
                  ("39:44", "What he would tell someone in week one")],
        takeaways=["Decide what you will not fix. A turnaround dies of too many priorities long before it dies of the wrong one.",
                   "The people who explain the problem best are rarely the ones who caused it, and almost never the ones in the room.",
                   "Announce the painful decision before you are certain you can deliver it. The delay costs more credibility than the error.",
                   "Ninety days is not a deadline. It is how long people will let you say you are still learning."],
    ),
    dict(
        ep=26, date="2026-08-01", cat="founders", slug="priya-raghavan-selling",
        title="Selling the company you meant to keep",
        guest="Priya Raghavan", role="Co-founder", org="Meridian Labs",
        duration="52:30", slots=["minor"],
        stand="Eleven years, one term sheet, and a decision she made in the space of a weekend.",
        chapters=[("04:12", "The offer nobody on the team knew about"),
                  ("18:50", "Telling forty people at once"),
                  ("33:07", "What the earn-out actually asked of her"),
                  ("45:22", "The first Monday afterwards")],
        takeaways=["An acquisition is a change of employer for everyone except the founder, who becomes a line item.",
                   "The number is the least negotiated part of the deal. Control, timing and who stays are where the argument is.",
                   "Tell the team in one room, once. Anything staged leaks, and the leak is the version they remember."],
    ),
    dict(
        ep=25, date="2026-07-28", cat="industry", slug="daniel-okonjo-operations",
        title="What twenty years in operations teaches about risk",
        guest="Daniel Okonjo", role="Chief Operating Officer", org="Vantage Freight",
        duration="44:05", slots=["minor"],
        stand="Why the failures that end careers are almost never the ones on the risk register.",
        chapters=[("03:30", "The register everybody signs and nobody reads"),
                  ("16:14", "Two incidents, eight years apart, same cause"),
                  ("31:02", "Teaching a graduate to notice"),
                  ("40:11", "The question he asks in every review")],
        takeaways=["Registers catch the risks somebody already imagined. The expensive ones live in the handoffs between teams that each assumed the other had it.",
                   "A near miss reported is worth more than an incident prevented, because only one of them teaches the organisation anything.",
                   "If the same person keeps spotting it first, that is not talent. That is everyone else not looking."],
    ),
    dict(
        ep=24, date="2026-07-23", cat="career", slug="helen-voss-partner-track",
        title="Leaving the partner track at forty-one",
        guest="Helen Voss", role="General Counsel", org="Arbor Health",
        duration="39:47", watched=1,
        stand="Fourteen years, two years from the decision, and the conversation that made it obvious.",
        takeaways=["The sunk cost is real. It is also not an argument.",
                   "Nobody senior will tell you the job is not worth it. They cannot, and stay.",
                   "The pay cut was temporary. The recovered evenings were not."],
    ),
    dict(
        ep=23, date="2026-07-18", cat="leadership", slug="ade-fashola-scale",
        title="Managing people who are better than you at the job",
        guest="Ade Fashola", role="VP Engineering", org="Northwind",
        duration="41:19", watched=2,
        stand="On giving up the work he was best at, and what replaced it.",
        chapters=[("05:02", "The last thing he ever shipped himself"),
                  ("17:35", "Hiring somebody stronger than him"),
                  ("29:48", "When to overrule an expert"),
                  ("36:20", "What he does all day now")],
        takeaways=["Your judgement stops being technical about eighteen months after you stop doing the work. Plan for that rather than pretending it is not happening.",
                   "Overrule on consequences, never on method. The moment you argue method you have hired an expensive pair of hands.",
                   "The best sign the team is working is that you find out about good decisions afterwards."],
    ),
    dict(
        ep=22, date="2026-07-14", cat="founders", slug="lena-brandt-second-company",
        title="Starting the second company, having failed at the first",
        guest="Lena Brandt", role="Founder", org="Kestrel",
        duration="46:52", watched=3,
        stand="What she kept from a business that closed, and what she was careful not to.",
    ),
    dict(
        ep=21, date="2026-07-09", cat="campus", slug="rowan-mitchell-careers",
        title="What careers services see that students never do",
        guest="Rowan Mitchell", role="Director of Careers", org="Fairhaven University",
        duration="35:28", watched=4,
        stand="Twenty thousand graduates, and the same four mistakes every single year.",
    ),
    dict(
        ep=20, date="2026-07-04", cat="industry", slug="sofia-marchetti-craft",
        title="Thirty years of building things that outlive their brief",
        guest="Sofia Marchetti", role="Principal Architect", org="Marchetti Studio",
        duration="50:16", watched=5,
        stand="On work that has to survive the people who commissioned it.",
    ),
    dict(
        ep=19, date="2026-06-29", cat="leadership", slug="james-oyelaran-inheriting",
        title="Inheriting a team that did not want you",
        guest="James Oyelaran", role="Managing Director", org="Sterling Group",
        duration="37:41",
        stand="The internal candidate he beat stayed. This is what he did about it.",
    ),
    dict(
        ep=18, date="2026-06-24", cat="career", slug="mei-chen-sideways",
        title="The sideways move that turned into the whole career",
        guest="Mei Chen", role="Head of Product", org="Loop Financial",
        duration="42:33",
        stand="She took a demotion in title to move department. Nine years later it reads differently.",
    ),
    dict(
        ep=17, date="2026-06-19", cat="founders", slug="tobias-krause-bootstrap",
        title="Fourteen years without raising a round",
        guest="Tobias Krause", role="Founder", org="Halberd Software",
        duration="47:09",
        stand="What staying small bought him, and the two moments he nearly changed his mind.",
    ),
    dict(
        ep=16, date="2026-06-14", cat="industry", slug="amara-diallo-regulation",
        title="Working inside an industry the public has decided about",
        guest="Amara Diallo", role="Head of Policy", org="Cardinal Energy",
        duration="43:57",
        stand="On defending decisions in rooms where nobody starts on your side.",
    ),
    dict(
        ep=15, date="2026-06-09", cat="campus", slug="peter-nkemelu-lecture",
        title="Teaching a subject that changes faster than the syllabus",
        guest="Dr Peter Nkemelu", role="Professor of Computing", org="Ashfield College",
        duration="38:22",
        stand="What he stopped teaching, and the argument it started.",
    ),
    dict(
        ep=14, date="2026-06-04", cat="leadership", slug="claire-benoit-crisis",
        title="The week everything was on the front page",
        guest="Claire Benoit", role="Chief Communications Officer", org="Vantage Freight",
        duration="45:14",
        stand="Six days of coverage, one statement that worked, and three that did not.",
    ),
    dict(
        ep=13, date="2026-05-30", cat="career", slug="idris-kane-late-start",
        title="Starting again at forty-eight, on purpose",
        guest="Idris Kane", role="Programme Manager", org="Beacon Trust",
        duration="36:48",
        stand="Two decades in one industry, and the decision to be junior again.",
    ),
    dict(
        ep=12, date="2026-05-25", cat="industry", slug="hannah-reilly-manufacturing",
        title="Keeping a factory floor when the work moved offshore",
        guest="Hannah Reilly", role="Operations Director", org="Kerrow Manufacturing",
        duration="40:31",
        stand="The maths that made staying possible, and what it cost to prove.",
    ),
    dict(
        ep=11, date="2026-05-20", cat="founders", slug="victor-almeida-cofounder",
        title="When the co-founder relationship ends before the company does",
        guest="Victor Almeida", role="Co-founder", org="Sable",
        duration="49:03",
        stand="Eight years, one hard conversation, and the agreement they should have written on day one.",
    ),
    dict(
        ep=10, date="2026-05-15", cat="leadership", slug="grace-abiodun-board",
        title="Your first board seat, and what nobody briefs you on",
        guest="Grace Abiodun", role="Non-Executive Director", org="Three listed boards",
        duration="44:26",
        stand="On asking the naive question in a room of people paid to look certain.",
    ),
    dict(
        ep=9, date="2026-05-10", cat="campus", slug="simon-hartley-placement",
        title="What a good placement year actually looks like",
        guest="Simon Hartley", role="Early Careers Lead", org="Northwind",
        duration="33:55",
        stand="Six hundred placements later, the difference between a year that counts and a year that passes.",
    ),
    dict(
        ep=8, date="2026-05-05", cat="career", slug="nadia-farouk-visibility",
        title="Being good at the job is not the same as being known for it",
        guest="Nadia Farouk", role="Director of Data", org="Loop Financial",
        duration="37:12",
        stand="On the two years her work was credited to somebody else, and how that ended.",
    ),
    dict(
        ep=7, date="2026-04-30", cat="industry", slug="eoin-gallagher-trade",
        title="A trade, a licence and a business, in that order",
        guest="Eoin Gallagher", role="Founder", org="Gallagher Electrical",
        duration="34:40",
        stand="From apprentice to forty staff, without a single day of formal management training.",
    ),
    dict(
        ep=6, date="2026-04-25", cat="leadership", slug="yuki-tanaka-remote",
        title="Running a company nobody has ever met in person",
        guest="Yuki Tanaka", role="Chief Executive", org="Orrery",
        duration="41:58",
        stand="Four years, eleven countries, and the meetings she reinstated after removing them.",
    ),
    dict(
        ep=5, date="2026-04-20", cat="founders", slug="rachel-osei-nonprofit",
        title="Building an organisation that is not allowed to fail quietly",
        guest="Rachel Osei", role="Executive Director", org="Beacon Trust",
        duration="43:11",
        stand="On running something where the customers cannot go elsewhere.",
    ),
    dict(
        ep=4, date="2026-04-15", cat="career", slug="tom-whitfield-industry-switch",
        title="Moving industry without starting over",
        guest="Tom Whitfield", role="Head of Supply Chain", org="Cardinal Energy",
        duration="38:36",
        stand="What transferred, what did not, and the eight months he spent finding out.",
    ),
    dict(
        ep=3, date="2026-04-10", cat="campus", slug="fatima-siddiqui-research",
        title="From research group to industry lab",
        guest="Dr Fatima Siddiqui", role="Principal Scientist", org="Meridian Labs",
        duration="46:20",
        stand="The parts of academia she misses, and the ones she does not.",
    ),
    dict(
        ep=2, date="2026-04-05", cat="industry", slug="anders-holm-quality",
        title="The unglamorous discipline that keeps everything else standing",
        guest="Anders Holm", role="Head of Quality", org="Kerrow Manufacturing",
        duration="35:07",
        stand="On being the person who says no, and staying employed.",
    ),
    dict(
        ep=1, date="2026-04-01", cat="leadership", slug="miriam-adeyemi-first",
        title="What we are trying to do here",
        guest="Miriam Adeyemi", role="Founder", org="The Access Exchange",
        duration="29:44",
        stand="The first conversation: why access is the thing worth building a platform around.",
        takeaways=["Experience is the only asset that appreciates and is never inventoried.",
                   "Most careers are shaped by three or four conversations. Almost nobody plans for them.",
                   "Access is not a favour. It is a distribution problem."],
    ),
]

CAT_NAME = dict(CATEGORIES)
BY_SLUG = {i["slug"]: i for i in INTERVIEWS}

# Which flat file an episode is served from. Prefix chosen so the plain build can
# glob "interviews-*.html" without ever matching interview-series.html.
def page_file(item):
    return "interviews-" + item["slug"] + ".html"


# ── the templates, in Python ───────────────────────────────────────────────
# One function per PHP template. Each prints what its counterpart prints; the
# comments name the file so a change over there has an obvious address here.

def _esc(s):
    return str(s).replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;").replace('"', "&quot;")


def _photo(item, size, eager=False):
    """inc/render.php tae_img(). WordPress crops one upload; here the crop is
    asked of Unsplash, which is why the dimensions are read from SIZES."""
    slug, alt = PHOTOS[item["ep"] % len(PHOTOS)]
    w, h = SIZES[size]
    load = 'fetchpriority="high" loading="eager"' if eager else 'loading="lazy"'
    return (f'<img src="https://images.unsplash.com/{slug}?auto=format&amp;fit=crop&amp;w={w}&amp;h={h}&amp;q=72" '
            f'width="{w}" height="{h}" class="attachment-{size} size-{size}" '
            f'alt="{_esc(alt)}" decoding="async" {load}>')


def _vid(item, classes, size, eager=False):
    """inc/render.php tae_vid(). The data-yt contract is what global.js §06 binds
    to - nothing is requested from youtube.com until the poster is pressed."""
    t = _esc(item["title"])
    return (f'<button class="vid {classes}" type="button" data-yt="{Y}" data-title="{t}" aria-label="Play: {t}">'
            f'{_photo(item, size, eager)}'
            '<span class="vid-play" aria-hidden="true"><span><svg viewBox="0 0 24 24" aria-hidden="true">'
            '<path d="M8 5v14l11-7z"/></svg></span></span>'
            f'<span class="vid-dur lab">{item["duration"]}</span></button>')


MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]


def _time(item, long=False):
    y, m, d = item["date"].split("-")
    shown = f"{MONTHS[int(m) - 1]} {int(d)}" + (f", {y}" if long else "")
    return f'<time datetime="{item["date"]}">{shown}</time>'


def _guest(item):
    return ", ".join(x for x in (item.get("guest"), item.get("role")) if x)


def _dest(item):
    return page_file(item)


# templates/interview-archive-item.php
def _archive_item(item):
    ep = f'Ep {item["ep"]} &nbsp;·&nbsp; '
    who = _guest(item)
    return (f'<li data-cat="{item["cat"]}">\n'
            '\t\t\t\t<article class="epc">\n'
            f'\t\t\t\t\t{_vid(item, "epc-vid", "tae-tile")}\n'
            '\t\t\t\t\t<div class="epc-meta">\n'
            f'\t\t\t\t\t\t<span class="epc-tag lab">{CAT_NAME[item["cat"]]}</span>\n'
            f'\t\t\t\t\t\t<span class="lab">{ep}{_time(item)}</span>\n'
            '\t\t\t\t\t</div>\n'
            f'\t\t\t\t\t<h3><a href="{_dest(item)}">{_esc(item["title"])}</a></h3>\n'
            + (f'\t\t\t\t\t<p class="epc-who">{_esc(who)}</p>\n' if who else "")
            + '\t\t\t\t</article>\n\t\t\t</li>')


# inc/filter-css.php tae_filter_markup( 'archive', 1 )
FILTER_IDS = {"": "f-all", "leadership": "f-lead", "founders": "f-fnd",
              "industry": "f-ind", "career": "f-car", "campus": "f-cam"}


def _filter():
    chips = ('<input type="radio" name="cat" id="f-all" data-cat="" checked>'
             '<label class="chip" for="f-all">All</label>')
    rules = []
    for slug, name in CATEGORIES:          # get_terms() default order is by name
        rid = FILTER_IDS[slug]
        chips += (f'<input type="radio" name="cat" id="{rid}" data-cat="{slug}">'
                  f'<label class="chip" for="{rid}">{name}</label>')
        rules.append(f'.tae-interviews .index-head:has(#{rid}:checked) ~ .eps li:not([data-cat="{slug}"])')
    return chips, "<style>" + ",".join(rules) + "{display:none}</style>"


# DEMO ONLY. The live pager refetches page one per category through admin-ajax
# (assets/tae-archive.js); a flat file cannot, so all 27 tiles ship in the DOM and
# this caps what is on screen. Same behaviour, no server.
PAGER_JS = """<script>
/* DEMO ONLY - the live site pages server-side via tae-archive.js. */
(function () {
  var list = document.querySelector(".eps"),
    sorts = document.querySelector(".sorts"),
    more = document.querySelector("[data-tae-more]");
  if (!list) return;
  var items = [].slice.call(list.children), per = 12, shown = per;
  function apply() {
    var on = sorts && sorts.querySelector("input:checked"),
      cat = on ? on.getAttribute("data-cat") : "",
      n = 0;
    items.forEach(function (li) {
      var ok = !cat || li.getAttribute("data-cat") === cat;
      li.style.display = ok && n < shown ? "" : "none";
      if (ok) n++;
    });
    if (more) more.style.display = n > shown ? "" : "none";
  }
  if (sorts) sorts.addEventListener("change", function () { shown = per; apply(); });
  if (more) more.addEventListener("click", function (e) { e.preventDefault(); shown += per; apply(); });
  apply();
})();
</script>"""


# templates/interview-archive.php
def view_archive(a):
    chips, style = _filter()
    heading = a.get("heading") or "Every episode, in full."
    divider = (f'<div class="divider lab">{_esc(a["divider"])}</div>\n' if a.get("divider") else "")
    tiles = "\n\t\t\t".join(_archive_item(i) for i in INTERVIEWS)
    return (divider + style + '\n<section class="index" id="episodes" aria-labelledby="index-h">\n'
            '\t<div class="shell">\n'
            '\t\t<div class="index-head">\n'
            f'\t\t\t<h2 id="index-h" data-rv>{_esc(heading)}</h2>\n'
            '\t\t\t<div class="sorts" role="group" aria-label="Filter episodes" data-tae-sorts>\n'
            f'\t\t\t\t{chips}\n'
            '\t\t\t</div>\n'
            '\t\t</div>\n\n'
            f'\t\t<ul class="eps" data-tae-list="archive" data-tae-page="1" data-tae-count="{a.get("count", 12)}" data-tae-cat="">\n'
            f'\t\t\t{tiles}\n'
            '\t\t</ul>\n\n'
            '\t\t<div class="pager">\n'
            '\t\t\t<a href="#episodes" class="btn btn--out" data-tae-more>Earlier episodes</a>\n'
            # tae_option('tae_channel_url'). A static build has no options table,
            # so this is the shipped default - which is what an untouched install
            # renders too.
            '\t\t\t<a href="https://www.youtube.com/@theaccessexchange" rel="noopener" class="btn">Subscribe</a>\n'
            '\t\t</div>\n'
            '\t</div>\n'
            '</section>\n' + PAGER_JS)


def _slot(name):
    return [i for i in INTERVIEWS if name in i.get("slots", [])]


# templates/interview-cover.php
def view_cover(a):
    story = (_slot("cover") or [None])[0]
    minors = _slot("minor")[:2]
    rail = sorted((i for i in INTERVIEWS if i.get("watched")), key=lambda i: i["watched"])[:5]
    if not story and not minors and not rail:
        return ""

    out = ['<section class="cover" aria-labelledby="cover-h">\n\t<div class="shell cover-grid">\n\n\t\t<div>']
    for m in minors:
        who = _guest(m)
        out.append('\t\t\t<article class="minor">\n'
                   f'\t\t\t\t{_vid(m, "minor-vid", "tae-minor")}\n'
                   f'\t\t\t\t<h3><a href="{_dest(m)}">{_esc(m["title"])}</a></h3>\n'
                   + (f'\t\t\t\t<p class="who">{_esc(who)}</p>\n' if who else "")
                   + '\t\t\t</article>')
    out.append('\t\t</div>\n')

    if story:
        bits = [f'Episode {story["ep"]}', CAT_NAME[story["cat"]], story["duration"]]
        out.append('\t\t<article class="story">\n'
                   f'\t\t\t{_vid(story, "story-vid", "tae-cover", True)}\n'
                   f'\t\t\t<p class="lab dim" style="margin-bottom:12px">{" &nbsp;·&nbsp; ".join(bits)}</p>\n'
                   f'\t\t\t<h2 id="cover-h"><a href="{_dest(story)}">{_esc(story["title"])}</a></h2>\n'
                   f'\t\t\t<p class="stand">{_esc(story["stand"])}</p>\n'
                   f'\t\t\t<span class="byline"><em>With {_esc(story["guest"])}</em></span>\n'
                   '\t\t</article>\n')

    out.append('\t\t<div>')
    if rail:
        out.append('\t\t\t<p class="rail-h lab">Most watched</p>\n\t\t\t<ul class="watched">')
        for n, item in enumerate(rail, 1):
            out.append(f'\t\t\t\t<li><a href="{_dest(item)}"><span class="n">{n:02d}</span>'
                       f'<p>{_esc(item["title"])}</p></a></li>')
        out.append('\t\t\t</ul>')
    out.append('\t\t</div>\n\n\t</div>\n</section>')
    return "\n".join(out)


# templates/interview-featured.php - including its standfirst-to-lead rule
SAFE_OPENERS = ("The", "A", "An", "Her", "His", "Their", "She", "He", "They",
                "It", "What", "How", "Why", "Three", "Two", "Five")


def view_featured(a):
    posts = _slot("feature")
    if not posts:
        return ""
    p = posts[0]
    who, stand = _guest(p), p.get("stand", "")
    lead = stand
    if who and stand:
        first = stand.split(" ")[0]
        lead = (f"{who}, on {stand[0].lower()}{stand[1:]}" if first in SAFE_OPENERS
                else f"{who} - {stand}")

    chapters = ""
    if p.get("chapters"):
        rows = "\n".join(
            f'\t\t\t\t\t\t<li><a href="{_dest(p)}#chapters"><span class="t lab">{t}</span>'
            f'<span class="c">{_esc(label)}</span>'
            '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow"/></svg></a></li>'
            for t, label in p["chapters"])
        chapters = ('\n\t\t\t<div class="chapters">\n'
                    '\t\t\t\t<span class="cap lab">In this conversation</span>\n'
                    f'\t\t\t\t<ul>\n{rows}\n\t\t\t\t</ul>\n'
                    '\t\t\t</div>\n')

    # CLIENT FIX (2026-08-20): mirrors templates/interview-featured.php's
    # rebuild - full-bleed background photo -> boxed series-grid layout, same
    # structure home-launch.php already uses, so the image is confined to the
    # right column instead of bleeding behind the text panel.
    return ('<section class="series feature" aria-labelledby="feature-h">\n'
            '\t<div class="shell series-grid">\n'
            '\t\t<div class="series-left">\n'
            '\t\t\t<div class="tag lab"><span class="s">03</span><span>Featured interview</span></div>\n'
            '\t\t\t<div class="f-meta lab">\n'
            f'\t\t\t\t<span class="b">{CAT_NAME[p["cat"]]}</span>\n'
            f'\t\t\t\t{_time(p, True)}\n'
            f'\t\t\t\t<span class="dot"></span><span>{p["duration"]}</span>\n'
            '\t\t\t</div>\n'
            f'\t\t\t<h2 id="feature-h">{_esc(p["title"])}</h2>\n'
            f'\t\t\t<p class="stand">{_esc(lead)}</p>\n'
            f'\t\t\t<a href="{_dest(p)}" class="ln">Watch the interview '
            '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow"/></svg></a>\n'
            f'{chapters}'
            '\t\t</div>\n'
            f'\t\t<div class="series-right">{_photo(p, "tae-feature")}</div>\n'
            '\t</div>\n</section>')


# templates/single-tae_interview.php - the share glyphs, same four paths
SHARE_ICONS = {
    "li": '<svg class="s-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.47-.9 1.63-1.85 3.36-1.85 3.59 0 4.26 2.37 4.26 5.45v6.29zM5.34 7.43a2.07 2.07 0 1 1 0-4.13 2.07 2.07 0 0 1 0 4.13zm1.78 13.02H3.55V9h3.57v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.72V1.72C24 .77 23.2 0 22.22 0z"/></svg>',
    "x": '<svg class="s-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.9 1.15h3.68l-8.04 9.19L24 22.85h-7.41l-5.8-7.58-6.64 7.58H.46l8.6-9.83L0 1.15h7.59l5.24 6.93 6.07-6.93zm-1.29 19.5h2.04L6.49 3.24H4.3l13.31 17.41z"/></svg>',
    "em": '<svg class="s-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm9 7.13L19.6 7H4.4L12 12.13zM4 8.5V17h16V8.5l-7.42 4.98a1 1 0 0 1-1.16 0L4 8.5z"/></svg>',
    "cp": '<svg class="s-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7a5 5 0 0 0 0 10h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4a5 5 0 0 0 0-10z"/></svg>',
}


def episode_body(p):
    credit = ", ".join(x for x in (p.get("role"), p.get("org")) if x)
    # inc/query.php tae_related(): for an interview, whatever is picked in "Watch
    # next" leads, then recent other interviews fill the row. No demo record sets
    # a next, so this is the fallback in both places.
    #
    # The episode page also grows a "From this interview" section listing the
    # takeaways whose tae_parent points at it. There are no demo takeaways, so
    # that section never renders here and has no Python twin - the PHP template
    # prints nothing in exactly the same situation.
    related = [i for i in INTERVIEWS if i is not p][:3]

    takeaways = ""
    if p.get("takeaways"):
        rows = "\n".join(
            f'\t\t\t\t\t\t<li><span class="n lab">{n:02d}</span><p>{_esc(line)}</p></li>'
            for n, line in enumerate(p["takeaways"], 1))
        takeaways = ('\n\t\t\t\t<section class="ep-take" aria-labelledby="take-h">\n'
                     '\t\t\t\t\t<h2 id="take-h">What we took from it</h2>\n'
                     f'\t\t\t\t\t<ol>\n{rows}\n\t\t\t\t\t</ol>\n'
                     '\t\t\t\t</section>\n')

    chapters = ""
    if p.get("chapters"):
        rows = "\n".join(
            f'\t\t\t\t\t\t<li><button type="button" class="ep-chapter" data-t="{t}">'
            f'<span class="t lab">{t}</span><span class="c">{_esc(label)}</span></button></li>'
            for t, label in p["chapters"])
        chapters = ('\t\t\t\t<section class="ep-chapters" id="chapters" aria-labelledby="chap-h">\n'
                    '\t\t\t\t\t<h2 id="chap-h" class="lab">In this conversation</h2>\n'
                    f'\t\t\t\t\t<ul>\n{rows}\n\t\t\t\t\t</ul>\n'
                    '\t\t\t\t</section>\n')

    nxt = "\n".join(
        f'\t\t\t\t\t\t<li><a href="{_dest(r)}"><span class="k lab">Interview</span>'
        f'<span class="t">{_esc(r["title"])}</span>'
        f'<span class="w">{_esc(_guest(r))}</span></a></li>' for r in related)

    url = "https://theaccessexchange.com/interviews/" + p["slug"] + "/"
    return f'''<div class="tae tae-episode">

\t<article>
\t\t<header class="ep-head">
\t\t\t<div class="shell">
\t\t\t\t<p class="ep-crumb lab">
\t\t\t\t\t<a href="/interview-series/">Interview Series</a>
\t\t\t\t\t<span class="dot"></span><span>{CAT_NAME[p["cat"]]}</span>
\t\t\t\t</p>
\t\t\t\t<h1>{_esc(p["title"])}</h1>
\t\t\t\t<p class="ep-stand">{_esc(p["stand"])}</p>
\t\t\t\t<p class="ep-guest">
\t\t\t\t\t<span class="ep-guest-name">{_esc(p["guest"])}</span>
\t\t\t\t\t<span class="ep-guest-role">{_esc(credit)}</span>
\t\t\t\t</p>
\t\t\t\t<p class="ep-meta lab">Episode {p["ep"]}<span class="dot"></span>{_time(p, True)}<span class="dot"></span><span>{p["duration"]}</span></p>
\t\t\t</div>
\t\t</header>

\t\t<div class="shell">
\t\t\t<div class="ep-video">{_vid(p, "ep-vid", "tae-cover", True)}</div>
\t\t</div>

\t\t<div class="shell ep-grid">
\t\t\t<div class="ep-main">{takeaways}\t\t\t</div>

\t\t\t<aside class="ep-side">
{chapters}\t\t\t\t<section class="ep-share" aria-labelledby="share-h">
\t\t\t\t\t<h2 id="share-h" class="lab">Share this</h2>
\t\t\t\t\t<div class="ep-share-row">
\t\t\t\t\t\t<a class="s-li" href="https://www.linkedin.com/sharing/share-offsite/?url={url}" rel="noopener nofollow" target="_blank" aria-label="Share on LinkedIn" title="Share on LinkedIn">{SHARE_ICONS["li"]}</a>
\t\t\t\t\t\t<a class="s-x" href="https://x.com/intent/tweet?url={url}" rel="noopener nofollow" target="_blank" aria-label="Share on X" title="Share on X">{SHARE_ICONS["x"]}</a>
\t\t\t\t\t\t<a class="s-em" href="mailto:?body={url}" aria-label="Share by email" title="Share by email">{SHARE_ICONS["em"]}</a>
\t\t\t\t\t\t<button type="button" class="ep-copy" data-copy="{url}">{SHARE_ICONS["cp"]}Copy link</button>
\t\t\t\t\t</div>
\t\t\t\t</section>
\t\t\t</aside>
\t\t</div>

\t\t<section class="ep-next" aria-labelledby="next-h">
\t\t\t<div class="shell">
\t\t\t\t<h2 id="next-h" class="lab">Carry on from here</h2>
\t\t\t\t<ul class="ep-next-list">
{nxt}
\t\t\t\t</ul>
\t\t\t</div>
\t\t</section>

\t\t<section class="ep-join" aria-labelledby="join-h">
\t\t\t<div class="shell">
\t\t\t\t<h2 id="join-h">Stay close to what's coming.</h2>
\t\t\t\t<p>New interviews. New perspectives. New rooms.</p>
\t\t\t\t<a href="/#join" class="btn">Join The Access Exchange</a>
\t\t\t</div>
\t\t</section>
\t</article>

</div>'''


# CLIENT EDIT (2026-08-20): "home" added, aliased to view_featured - the demo
# library always puts one interview in the feature slot (line 68 above), so
# under --demo, [tae_interviews view="home"] is always state 1 (a featured
# pick exists), same as the real plugin's inc/shortcodes.php case 'home'.
VIEWS = {"archive": view_archive, "cover": view_cover, "featured": view_featured, "home": view_featured}


def render(a):
    """The --demo replacement for build-static.py's sc_interviews()."""
    return VIEWS.get(a.get("view", "archive"), lambda _a: "")(a)
