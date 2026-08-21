/* ═══════════════════════════════════════════════════════════════════════════
   THE ACCESS EXCHANGE - GLOBAL SCRIPT
   Paste into: Elementor → Custom Code → new snippet, Location "Body - End"
   (or better: enqueue as a real .js file from the child theme with `defer`)

   One file for the whole site. Every block below is guarded by a DOM check,
   so a page that does not contain a given component simply skips it. Nothing
   here needs jQuery.

     01  Boot + shared helpers
     02  Masthead - collapses on scroll
     03  Menu panel
     04  Site search overlay
     05  Scroll reveals
     06  Click-to-load YouTube
     07  Prototype forms
     08  Home     - guest rail
     09  Insights - search + topic filter with FLIP relayout
     11  Partnerships - term bars, outline rail, accordion
     12  Analytics - the eight conversions

   FORMS  All six forms are Contact Form 7 shortcodes now - see
   wordpress/CF7-SMTP.md. Section 07 below is therefore INERT: it binds to
   form[data-validate] and nothing carries that attribute any more. It is left
   in place so a page can be reverted to the prototype behaviour by re-adding
   the attribute; delete it if you want the file clean.
   ═══════════════════════════════════════════════════════════════════════════ */
(function () {
  "use strict";

  /* ═══ 00 · LOAD GUARD + DIAGNOSTIC HANDLE ══════════════════════════════
     Type `TAE` into the browser console to check this file ran. That test is
     immune to console log-level filters - a `console.log` can be hidden by
     DevTools' filter dropdown, a global object cannot.

       TAE                 → undefined  = the file never executed
       TAE.ready           → true       = it ran and finished
       TAE.errors          → []         = no module threw
       TAE.openMenu()      → opens the panel WITHOUT clicking anything.
                             If this works but clicking does not, the button
                             is fine and something is intercepting the click.

     The duplicate guard matters: if this file is pasted in two places (say a
     Custom Code snippet AND the footer widget), every click listener binds
     twice, so a click opens the menu and immediately closes it again - which
     looks exactly like "clicking does nothing".
     ═══════════════════════════════════════════════════════════════════════ */
  if (window.TAE && window.TAE.loaded) {
    console.warn(
      "[TAE] global.js loaded twice - the second copy is being ignored. " +
        "Remove one of them: every click handler would otherwise fire twice and " +
        "the menu would open then instantly close.",
    );
    return;
  }
  var TAE = (window.TAE = {
    version: "1.1",
    loaded: true,
    ready: false,
    errors: [],
  });

  /* ═══ 01 · BOOT + SHARED HELPERS ═══════════════════════════════════════ */
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  var $ = function (sel, root) {
    return (root || document).querySelector(sel);
  };
  var $$ = function (sel, root) {
    return [].slice.call((root || document).querySelectorAll(sel));
  };

  function ready(fn) {
    if (document.readyState !== "loading") fn();
    else document.addEventListener("DOMContentLoaded", fn);
  }

  /* Reparent a full-screen overlay onto <body>.

     The menu panel and the search overlay are position:fixed, which is
     viewport-relative ONLY while no ancestor establishes a containing block.
     A page builder breaks that constantly - `transform`, `filter`,
     `perspective`, `backdrop-filter`, `will-change` and `contain` all turn an
     ancestor into the containing block for fixed children, and an Elementor
     container picks one up from any motion effect, sticky setting or entrance
     animation. `overflow:hidden` on the header wrapper clips it outright, and
     a z-index on the wrapper traps the overlay in that stacking context.

     In every one of those cases the JS runs perfectly, the class toggles, and
     nothing appears - which is exactly the "clicking the menu does nothing"
     symptom. Hoisting to <body> makes the markup's position in the template
     irrelevant. */
  function liftToBody(el) {
    if (!el || el.parentNode === document.body) return null;
    var trapped = null,
      p = el.parentElement;
    while (p && p !== document.body) {
      var cs = getComputedStyle(p);
      if (
        cs.transform !== "none" ||
        cs.filter !== "none" ||
        cs.perspective !== "none" ||
        cs.contain !== "none" ||
        cs.willChange !== "auto" ||
        cs.overflow !== "visible"
      ) {
        trapped = p.className || p.tagName;
        break;
      }
      p = p.parentElement;
    }
    document.body.appendChild(el);
    return trapped;
  }

  /* The one place the site index lives.

     The tae-content plugin publishes window.TAE_INDEX from real posts, so
     interviews and insights enter site search the moment they are published.
     The literal below is the fallback: it keeps search working if the plugin is
     deactivated, and it is the only thing to maintain by hand if it never gets
     installed. Add a row here when you publish a new PAGE either way. */
  var SITE_INDEX = window.TAE_INDEX || [
    { t: "Home", d: "The platform and every way into it", u: "/" },
    {
      t: "Interview Series",
      d: "In-depth interviews, takeaways and the archive",
      u: "/interview-series/",
    },
    {
      t: "Guests & Partners",
      d: "Guest consideration, sponsorship, nomination",
      u: "/guests-partners/",
    },
    {
      t: "Universities & Institutions",
      d: "Leadership talks, discussions, Q&A, custom programmes",
      u: "/universities/",
    },
    {
      t: "Experiences",
      d: "Live panels, leadership discussions and events",
      u: "/experiences/",
    },
    {
      t: "Coaching",
      d: "Professional coaching, coach training and certification",
      u: "/coaching/",
    },
    {
      t: "About",
      d: "Philosophy, mission and the founder story",
      u: "/about/",
    },
    {
      t: "Get Involved",
      d: "Every route into The Access Exchange",
      u: "/get-involved/",
    },
    {
      t: "Share your perspective",
      d: "Guest consideration form",
      u: "/guests-partners/#guest",
    },
    {
      t: "Partner with The Access Exchange",
      d: "Corporate sponsorship and partnership form",
      u: "/guests-partners/#corporate",
    },
    {
      t: "Bring The Access Exchange to campus",
      d: "University and institutional engagement form",
      u: "/universities/#enquire",
    },
    {
      t: "Explore coaching",
      d: "Professional coaching inquiry",
      u: "/coaching/#coaching",
    },
    {
      t: "Explore coach training",
      d: "Coach training and certification inquiry",
      u: "/coaching/#training",
    },
    {
      t: "Takeaways",
      d: "Standout ideas and clips from the interviews",
      u: "/interview-series/#takeaways",
    },
    {
      t: "The archive",
      d: "Every interview in full",
      u: "/interview-series/#episodes",
    },
    {
      t: "From bootcamp to staff engineer in five years",
      d: "Amara Osei, Staff Engineer",
      u: "/interview-series/",
    },
    {
      t: "What a backend interview loop actually tests",
      d: "Ravi Menon, Engineering Manager",
      u: "/interview-series/",
    },
    {
      t: "Moving from support into product without a technical degree",
      d: "Fiona Bright, Senior Product Manager",
      u: "/interview-series/",
    },
    {
      t: "The parts of a data science job nobody puts in the job spec",
      d: "Dr Leila Haddad, Lead Data Scientist",
      u: "/interview-series/",
    },
    {
      t: "Forty applications, two offers",
      d: "Tobi Adeyinka, Junior Developer",
      u: "/interview-series/",
    },
    {
      t: "Building a portfolio when you have never shipped anything",
      d: "Marta Kowalska, Product Designer",
      u: "/interview-series/",
    },
    {
      t: "Switching from civil engineering at thirty-one",
      d: "Callum Reid, Platform Engineer",
      u: "/interview-series/",
    },
    {
      t: "Internships, placements and the routes people forget exist",
      d: "Grace Nwosu, Early Careers Lead",
      u: "/interview-series/",
    },
  ];

  /* Each module is isolated. Without this, one thrown error in an early
     module (say a plugin removed an element it expects) silently kills every
     module after it - the classic "the menu stopped opening and nothing in
     the console explains why". Now a failure is loud and local. */
  function run(name, fn) {
    try {
      fn();
    } catch (err) {
      TAE.errors.push(name + ": " + err.message);
      console.error('[TAE] module "' + name + '" failed:', err);
    }
  }

  /* boot() is DEFINED here but INVOKED at the very bottom of this file.
     ─────────────────────────────────────────────────────────────────────────
     Do not move the call back up here. `ready()` runs its callback
     SYNCHRONOUSLY when the DOM is already parsed - which is the normal case
     for a script at the end of <body>, or one a plugin defers or injects late.
     Calling it here meant the callback executed while the rest of this module
     had not run yet, so a later top-level line like

         var setMenu = function () {};        // §03, ~100 lines below

     would execute AFTERWARDS and overwrite the real handler that menu() had
     just assigned. Result: every click called a no-op, the panel never
     opened, and whether it happened at all depended on whether the script
     beat DOMContentLoaded - so it "worked in one browser and not another".

     Invoking at the bottom guarantees every top-level binding exists first. */
  function boot() {
    run("masthead", masthead);
    run("menu", menu);
    run("siteSearch", siteSearch);
    run("reveals", reveals);
    run("heroParallax", heroParallax);
    run("lazyVideo", lazyVideo);
    run("episode", episode);
    run("insightsIndex", insightsIndex);
    run("partnershipsGuide", partnershipsGuide);
    run("analytics", analytics);

    /* Load marker. If you are debugging "works logged in, broken logged out",
       open the page in incognito with DevTools → Console: no [TAE] line means
       this file never executed (cache / optimiser / consent blocker), a line
       plus an error means it executed and something inside broke. */
    var veil = document.getElementById("veil");
    var btn = document.getElementById("menuBtn");

    TAE.ready = true;
    TAE.menuBtn = !!btn;
    TAE.veil = !!veil;
    TAE.veilOnBody = !!(veil && veil.parentNode === document.body);
    TAE.openMenu = function () {
      setMenu(true);
    };
    TAE.closeMenu = function () {
      setMenu(false);
    };

    /* One-command remote diagnostic. Opens the panel, then reports the state
       that actually decides whether it is on screen: is it in the DOM, did the
       class land, what did the computed styles resolve to, where is its box,
       and - the question CSS alone cannot answer - what element is physically
       on top of the menu button and of the panel.
       Run TAE.diagnose() in the console and send the output. */
    TAE.diagnose = function () {
      function name(el) {
        if (!el) return null;
        var c = el.className;
        c = c && c.baseVal !== undefined ? c.baseVal : c; // SVG-safe
        return (
          el.tagName.toLowerCase() +
          (c ? "." + String(c).trim().replace(/\s+/g, ".") : "")
        ).slice(0, 90);
      }

      var v = document.getElementById("veil"),
        b = document.getElementById("menuBtn");
      if (!v || !b)
        return {
          fatal: "missing " + (!v ? "#veil" : "") + (!b ? " #menuBtn" : ""),
        };

      setMenu(true);

      var panel = v.querySelector(".veil-panel");
      var cs = getComputedStyle(v);
      var ps = panel && getComputedStyle(panel);
      var r = panel && panel.getBoundingClientRect();
      var bb = b.getBoundingClientRect();
      var overBtn = document.elementFromPoint(
        bb.left + bb.width / 2,
        bb.top + bb.height / 2,
      );

      var out = {
        veilParent:
          v.parentNode === document.body ? "<body>" : name(v.parentElement),
        hiddenAttr: v.hidden,
        hasOpenClass: v.classList.contains("open"),
        veil: {
          display: cs.display,
          visibility: cs.visibility,
          opacity: cs.opacity,
          zIndex: cs.zIndex,
          position: cs.position,
        },
        panel: ps && {
          transform: ps.transform,
          width: ps.width,
          visibility: ps.visibility,
          background: ps.backgroundColor,
        },
        panelBox: r && {
          x: Math.round(r.left),
          y: Math.round(r.top),
          w: Math.round(r.width),
          h: Math.round(r.height),
        },
        onTopOfMenuButton: name(overBtn),
        clickReachesButton: !!(
          overBtn &&
          (overBtn === b || b.contains(overBtn))
        ),
      };

      if (r && r.width > 0 && r.left < innerWidth && r.top < innerHeight) {
        out.onTopOfPanel = name(
          document.elementFromPoint(
            Math.max(1, Math.min(r.left + r.width / 2, innerWidth - 1)),
            Math.max(1, Math.min(r.top + 60, innerHeight - 1)),
          ),
        );
      } else {
        out.onTopOfPanel = "panel box is off-screen or zero-sized";
      }

      console.log("[TAE] diagnose →", JSON.stringify(out, null, 2));
      return out;
    };

    console.log(
      "[TAE] global.js ready -",
      btn ? "menuBtn found" : "NO #menuBtn IN DOM",
      "|",
      veil ? "veil found" : "NO #veil IN DOM",
      "|",
      TAE.veilOnBody ? "veil on <body>" : "veil NOT on body",
      "| type TAE in the console for the full report",
    );
  }

  /* ═══ 02 · MASTHEAD - collapses in place ═══════════════════════════════ */
  function masthead() {
    var mast = document.getElementById("mast");
    if (!mast) return;
    var onScroll = function () {
      /* 70px meant the tall masthead held ~200px of the first screen well into
         the scroll. It collapses as soon as the page moves. */
      mast.classList.toggle("compact", window.scrollY > 12);
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }

  /* ═══ 03 · MENU PANEL ══════════════════════════════════════════════════ */
  var setMenu = function () {}; // hoisted so the search overlay can close it

  function menu() {
    var btn = document.getElementById("menuBtn");
    var veil = document.getElementById("veil");
    if (!btn || !veil) return;

    var trapped = liftToBody(veil);
    if (trapped)
      console.info(
        '[TAE] menu panel was trapped inside "' +
          trapped +
          '" - hoisted to <body>. Harmless, but that container has a transform, ' +
          "overflow or containment set; check its Elementor motion/overflow settings.",
      );

    var icon = btn.querySelector("use");

    setMenu = function (open) {
      btn.setAttribute("aria-expanded", open);
      btn.setAttribute("aria-label", open ? "Close menu" : "Open menu");
      if (icon) {
        var ref = open ? "#i-close" : "#i-menu";
        icon.setAttribute("href", ref);
        /* some engines still only honour the namespaced form on <use> */
        icon.setAttributeNS("http://www.w3.org/1999/xlink", "xlink:href", ref);
      }
      document.body.style.overflow = open ? "hidden" : "";
      if (open) {
        veil.hidden = false;
        requestAnimationFrame(function () {
          veil.classList.add("open");
        });
      } else {
        veil.classList.remove("open");
        setTimeout(
          function () {
            veil.hidden = true;
          },
          reduce ? 0 : 400,
        );
      }
    };

    btn.addEventListener("click", function () {
      setMenu(btn.getAttribute("aria-expanded") !== "true");
    });

    var close = document.getElementById("veilClose");
    if (close)
      close.addEventListener("click", function () {
        setMenu(false);
      });

    window.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && !veil.hidden) setMenu(false);
    });

    veil.addEventListener("click", function (e) {
      if (e.target.closest("a") || !e.target.closest(".veil-panel"))
        setMenu(false);
    });

    /* the header is one shared template, so mark the current page here rather
       than hard-coding aria-current into a copy of the markup per page. Both
       navs are marked: the masthead strip above 1080px, the panel below it. */
    var here = location.pathname.replace(/\/+$/, "") || "/";
    $$(".veil-nav a, .mast-nav a").forEach(function (a) {
      var path =
        a.getAttribute("href").split("#")[0].replace(/\/+$/, "") || "/";
      if (path === here) a.setAttribute("aria-current", "page");
    });
  }

  /* ═══ 04 · SITE SEARCH OVERLAY ═════════════════════════════════════════
     Filters the static index above. Swap `SITE_INDEX` for a fetch() against
     /wp-json/wp/v2/search?search=… when the content moves into WordPress
     posts - the render/keyboard code below does not change.
     ═══════════════════════════════════════════════════════════════════════ */
  function siteSearch() {
    var find = document.getElementById("find");
    var findq = document.getElementById("findq");
    var findout = document.getElementById("findout");
    var trigger = document.querySelector(".srchbtn");
    if (!find || !findq || !findout) return;

    liftToBody(find); // same containing-block problem as the menu

    var sel = -1;

    function esc(s) {
      return String(s).replace(/[&<>"']/g, function (c) {
        return {
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        }[c];
      });
    }

    function render(list) {
      sel = -1;
      if (!list.length) {
        findout.innerHTML = '<p class="find-none">Nothing matches that.</p>';
        return;
      }
      findout.innerHTML = list
        .map(function (r) {
          return (
            '<a href="' +
            esc(r.u) +
            '"><b>' +
            esc(r.t) +
            "</b><span>" +
            esc(r.d) +
            "</span></a>"
          );
        })
        .join("");
    }

    function runFind() {
      var q = findq.value.trim().toLowerCase();
      if (!q) return render(SITE_INDEX.slice(0, 6));
      render(
        SITE_INDEX.filter(function (r) {
          return (r.t + " " + r.d).toLowerCase().indexOf(q) > -1;
        }),
      );
    }

    function setFind(open) {
      if (open) {
        find.hidden = false;
        requestAnimationFrame(function () {
          find.classList.add("open");
          findq.focus();
        });
        runFind();
      } else {
        find.classList.remove("open");
        findq.value = "";
        setTimeout(
          function () {
            find.hidden = true;
          },
          reduce ? 0 : 300,
        );
      }
      document.body.style.overflow = open ? "hidden" : "";
    }

    if (trigger)
      trigger.addEventListener("click", function () {
        setMenu(false);
        setFind(true);
      });
    findq.addEventListener("input", runFind);
    find.addEventListener("click", function (e) {
      if (!e.target.closest(".find-box")) setFind(false);
    });

    window.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && !find.hidden) {
        setFind(false);
        return;
      }

      if (
        (e.key === "/" || (e.key === "k" && (e.metaKey || e.ctrlKey))) &&
        find.hidden &&
        !/^(INPUT|TEXTAREA)$/.test(document.activeElement.tagName)
      ) {
        e.preventDefault();
        setFind(true);
        return;
      }

      if (find.hidden) return;
      var items = $$("a", findout);
      if (!items.length) return;

      if (e.key === "ArrowDown" || e.key === "ArrowUp") {
        e.preventDefault();
        sel =
          (sel + (e.key === "ArrowDown" ? 1 : -1) + items.length) %
          items.length;
        items.forEach(function (a, i) {
          a.classList.toggle("sel", i === sel);
        });
        items[sel].scrollIntoView({ block: "nearest" });
      }
      if (e.key === "Enter" && sel > -1) items[sel].click();
    });
  }

  /* ═══ 05 · SCROLL REVEALS ══════════════════════════════════════════════ */
  /* ═══ 05b · HERO PARALLAX ══════════════════════════════════════════════
     The stage photograph drifts as the page scrolls, so the hero has depth
     rather than being a still behind type.

     This was a CSS scroll-driven animation (animation-timeline: scroll()),
     which is the lighter tool - no listener, runs on the compositor - but it is
     Chrome and Safari only; Firefox needs a flag, so a third of visitors would
     get a still. Since the brief names movement as part of the primary visual
     reference, the parallax runs everywhere instead.

     rAF-throttled, transform only, passive listener, and it does not run at all
     under prefers-reduced-motion - so if a hero looks frozen, check the OS
     animation setting before this function. The travel stays inside the media
     box's overhang, so no edge is ever exposed. */
  function heroParallax() {
    // CLIENT FIX (2026-08-20), 4th pass - a live <video> hero flickered a
    // black overlay in during scroll and cleared once scroll stopped. Two
    // earlier attempts (a CSS layer-promotion hack, then filtering by
    // querySelector("video") at runtime here) didn't clear it, so this hero
    // now carries an explicit .stage-media--static class in the markup
    // (wordpress/index.html §01) instead - a hard-coded flag, not something
    // inferred from what happens to be inside the element. Excluded here,
    // and will-change is stripped from it by that same class in global.css -
    // nothing about disabling this depends on runtime DOM inspection or
    // browser feature support anymore.
    var media = $$(".stage-media").filter(function (el) {
      return !el.classList.contains("stage-media--static");
    });
    if (!media.length) return;
    if (window.matchMedia && matchMedia("(prefers-reduced-motion: reduce)").matches) {
      return;
    }

    /* Travel, in % of the MEDIA BOX height - which is what a percentage
       translate resolves against, not the section's. The box overhangs its
       section by 12% at the head and 24% at the foot (global.css §03), so it
       stands 1.36x the section: 0.12/1.36 = 8.8% of slack above it, and
       0.24/1.36 = 17.6% below. Sitting just inside both keeps every edge
       covered, and the 25.5% between them is ~35% of the section height -
       about 190px on a 74vh hero, which reads as movement.

       The first version budgeted +-9% against a box that overhung by 12% at
       each end, and then spent only three quarters of even that (see p below).
       ~7% of the box travelled over a whole hero: technically parallax,
       visually a still. */
    var DOWN = 8.5;
    var UP = 17;
    var ticking = false;

    function frame() {
      ticking = false;

      for (var i = 0; i < media.length; i++) {
        var el = media[i];
        var box = el.parentNode.getBoundingClientRect();

        /* 0 while the section's top is at the top of the viewport, 1 once the
           section has scrolled fully past it.

           Normalised by the SECTION's own height, not the viewport's. A 74vh
           hero is gone after 74vh of scroll, so dividing by the viewport
           retired at p=0.74 and the last quarter of the range was never once
           on screen. Measured per section rather than from window.scrollY, so
           a stage placed anywhere on a page behaves the same as one at the
           top. */
        var p = Math.min(1, Math.max(0, -box.top / (box.height || 1)));

        /* +DOWN to -UP: as the page scrolls down, the photograph rises inside
           its frame. */
        var shift = DOWN - (DOWN + UP) * p;
        el.style.transform = "translate3d(0," + shift.toFixed(2) + "%,0)";
      }
    }

    function request() {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(frame);
    }

    frame();
    window.addEventListener("scroll", request, { passive: true });
    window.addEventListener("resize", request, { passive: true });
  }

  function reveals() {
    var els = $$("[data-rv],[data-rule]");
    if (!els.length) return;
    if (!("IntersectionObserver" in window)) {
      els.forEach(function (el) {
        el.classList.add("in");
      });
      return;
    }
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) {
            e.target.classList.add("in");
            io.unobserve(e.target);
          }
        });
      },
      /* Positive bottom margin: the root box extends BELOW the viewport, so a
         reveal starts just before its element arrives rather than once it is
         already 10% inside. Paired with the shorter durations in global.css §03,
         the motion lands with the reader instead of trailing them. */
      { rootMargin: "0px 0px 12% 0px", threshold: 0 },
    );
    els.forEach(function (el) {
      io.observe(el);
    });
  }

  /* ═══ 06 · CLICK-TO-LOAD YOUTUBE ═══════════════════════════════════════
     Nothing is requested from YouTube until someone presses play, which is
     what keeps these pages inside the 80+ PageSpeed target.
     ═══════════════════════════════════════════════════════════════════════ */
  function lazyVideo() {
    $$("[data-yt]").forEach(function (v) {
      v.addEventListener("click", function () {
        if (v.classList.contains("on")) return;
        var f = document.createElement("iframe");
        /* data-start is set by a chapter button just before it clicks us; the
           facade is otherwise unchanged, so nothing loads until that happens. */
        var start = parseInt(v.dataset.start, 10);
        f.src =
          "https://www.youtube-nocookie.com/embed/" +
          v.dataset.yt +
          "?autoplay=1&rel=0&modestbranding=1" +
          (start > 0 ? "&start=" + start : "");
        f.title = v.dataset.title || "Interview";
        f.loading = "lazy";
        f.allow =
          "accelerometer; autoplay; encrypted-media; picture-in-picture";
        f.setAttribute("allowfullscreen", "");
        v.appendChild(f);
        v.classList.add("on");
      });
    });
  }

  /* ═══ 06b · EPISODE PAGE - chapter jumps and copy-link ═════════════════
     Only present on /interviews/{slug}/. Both are no-ops elsewhere.

     A chapter does not load the video by itself: it sets the start time and
     then presses play, so the facade's one rule - nothing from youtube.com
     until a deliberate click - still holds.
     ═══════════════════════════════════════════════════════════════════════ */
  function episode() {
    var video = document.querySelector(".ep-video [data-yt]");

    $$(".ep-chapter").forEach(function (b) {
      b.addEventListener("click", function () {
        if (!video) return;
        /* "12:40" and "1:02:15" both land as seconds */
        var secs = b.dataset.t.split(":").reduce(function (acc, part) {
          return acc * 60 + (parseInt(part, 10) || 0);
        }, 0);
        video.dataset.start = secs;
        if (video.classList.contains("on")) {
          /* already playing - rebuild the frame at the new timestamp.

             Clearing .on is what lets §06 build a second iframe, but §12's
             analytics listener reads that same flag to decide whether a click
             is a fresh play. Cleared, every chapter jump on a running video
             counted as another interview_play. The marker below is read and
             consumed by §12 before §06 ever sees the click. Jumping about
             inside one conversation is not five plays. */
          video.dataset.rechapter = "1";
          video.classList.remove("on");
          var old = video.querySelector("iframe");
          if (old) old.remove();
        }
        video.click();
        video.scrollIntoView({ block: "center", behavior: "smooth" });
      });
    });

    $$(".ep-copy").forEach(function (b) {
      var label = b.textContent;
      b.addEventListener("click", function () {
        if (!navigator.clipboard) return; /* http:// or an old browser */
        navigator.clipboard.writeText(b.dataset.copy).then(
          function () {
            b.textContent = "Copied";
            setTimeout(function () {
              b.textContent = label;
            }, 1800);
          },
          function () {
            /* denied - the link is still in the address bar */
          },
        );
      });
    });
  }

  /* ═══ 07 + 08 · REMOVED ═══════════════════════════════════════════════
     Two modules that ran on every page load and could never do anything.

     07 · PROTOTYPE FORMS bound to form[data-validate]. Contact Form 7 took
     the forms over, and no form has carried that attribute since. It also
     reached for .panel and #well, both of which went with the contact page.

     08 · HOME GUEST RAIL bound to #rail, which went with the guest rail in
     Phase 7.

     Neither had a hook left in any page or template, so each was ~45 lines
     parsed and skipped on every request. Deleted rather than left as a
     comment: git has them if the rail is ever designed back in.
     ═══════════════════════════════════════════════════════════════════════ */

  /* ═══ 09 · INSIGHTS - search + topic filter, re-laid out with FLIP ═════
     Cards travel to their new positions rather than snapping. First and Last
     are measured around a single synchronous DOM mutation, then the delta is
     played back with the Web Animations API - transform only, so it stays on
     the compositor.
     ═══════════════════════════════════════════════════════════════════════ */
  function insightsIndex() {
    var stream = document.getElementById("stream");
    if (!stream) return;

    var cards = $$(".it", stream),
      empty = document.getElementById("empty"),
      hunt = document.getElementById("hunt"),
      input = document.getElementById("q"),
      clear = document.getElementById("clear"),
      topics = document.getElementById("topics"),
      tally = document.getElementById("tally"),
      word = document.getElementById("tallyWord"),
      band = stream.querySelector(".band");

    var topic = "all",
      term = "";

    /* cache the searchable text once */
    cards.forEach(function (c) {
      c._hay = c.textContent.toLowerCase().replace(/\s+/g, " ");
    });

    function matches(c) {
      if (topic !== "all" && c.dataset.topic !== topic) return false;
      if (term && c._hay.indexOf(term) === -1) return false;
      return true;
    }

    function apply() {
      /* FIRST - measure everything that is currently on screen */
      var first = new Map();
      cards.forEach(function (c) {
        first.set(c, c.getBoundingClientRect());
      });

      /* MUTATE */
      var shown = 0;
      cards.forEach(function (c) {
        var now = matches(c);
        if (now) shown++;
        c.classList.toggle("hide", !now);
      });
      /* the newsletter band only belongs in the unfiltered stream */
      if (band) band.style.display = topic === "all" && !term ? "" : "none";
      if (empty) empty.hidden = shown > 0;

      /* LAST + INVERT + PLAY */
      if (!reduce) {
        cards.forEach(function (c) {
          if (c.classList.contains("hide")) return;
          var f = first.get(c);
          if (!f || !f.width) {
            c.classList.remove("pop");
            void c.offsetWidth;
            c.classList.add("pop");
            return;
          }
          var l = c.getBoundingClientRect();
          var dx = f.left - l.left,
            dy = f.top - l.top;
          if (!dx && !dy) return;
          if (!c.animate) return;
          c.animate(
            [
              { transform: "translate(" + dx + "px," + dy + "px)" },
              { transform: "none" },
            ],
            { duration: 520, easing: "cubic-bezier(.22,1,.36,1)" },
          );
        });
      }

      if (tally) tally.textContent = shown;
      if (word)
        word.textContent =
          shown === 1
            ? "piece found"
            : topic === "all" && !term
            ? "pieces published"
            : "pieces found";
      if (hunt) hunt.classList.toggle("has", !!term);
    }

    if (topics) {
      topics.addEventListener("click", function (e) {
        var b = e.target.closest("button");
        if (!b) return;
        topic = b.dataset.topic;
        $$("button", topics).forEach(function (x) {
          x.setAttribute("aria-pressed", String(x === b));
        });
        apply();
      });
    }

    if (input) {
      var t = null;
      input.addEventListener("input", function () {
        clearTimeout(t);
        t = setTimeout(function () {
          term = input.value.trim().toLowerCase();
          apply();
        }, 110);
      });
      input.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && input.value) {
          input.value = "";
          term = "";
          apply();
        }
      });
    }
    if (clear && input) {
      clear.addEventListener("click", function () {
        input.value = "";
        term = "";
        apply();
        input.focus();
      });
    }

    apply();
  }

  /* ═══ 11 · PARTNERSHIPS - term bars, outline rail, accordion ═══════════ */
  function partnershipsGuide() {
    /* term bars start at scaleX(0) - zero area - so watch the track, not the
       bars. The observer is held in a variable so it cannot be collected. */
    var track = document.querySelector("[data-bars]");
    if (track && "IntersectionObserver" in window) {
      var barIO = new IntersectionObserver(
        function (es) {
          if (!es[0].isIntersecting) return;
          $$(".bar", track).forEach(function (b) {
            b.classList.add("in");
          });
          barIO.disconnect();
        },
        { rootMargin: "0px 0px -15% 0px", threshold: 0 },
      );
      barIO.observe(track);
    } else if (track) {
      $$(".bar", track).forEach(function (b) {
        b.classList.add("in");
      });
    }

    /* outline: highlight the section being read, and show progress */
    var panel = document.getElementById("outlineList");
    if (panel) {
      var links = {};
      $$("a", panel).forEach(function (a) {
        links[a.dataset.to] = a;
      });

      if ("IntersectionObserver" in window) {
        var hereIO = new IntersectionObserver(
          function (es) {
            es.forEach(function (e) {
              if (!e.isIntersecting) return;
              Object.keys(links).forEach(function (k) {
                links[k].classList.toggle("here", k === e.target.id);
              });
            });
          },
          { rootMargin: "-25% 0px -60% 0px" },
        );
        $$(".sec[id]").forEach(function (sec) {
          hereIO.observe(sec);
        });
      }

      // CLIENT EDIT (2026-08-20): "% read" was always 0 - this looked for
      // #s7, but the Universities page only has sections #s1..#s5, so `last`
      // was always null and the whole progress block below was skipped.
      var bar = document.getElementById("outlineBar"),
        pct = document.getElementById("outlinePct"),
        first = document.getElementById("s1"),
        last = document.getElementById("s5"),
        ticking = false;

      if (bar && pct && first && last) {
        var progress = function () {
          ticking = false;
          var start = first.offsetTop,
            end = last.offsetTop + last.offsetHeight - window.innerHeight;
          var p = (window.scrollY - start) / Math.max(1, end - start);
          p = p < 0 ? 0 : p > 1 ? 1 : p;
          bar.style.width = (p * 100).toFixed(1) + "%";
          pct.textContent = Math.round(p * 100);
        };
        window.addEventListener(
          "scroll",
          function () {
            if (!ticking) {
              ticking = true;
              requestAnimationFrame(progress);
            }
          },
          { passive: true },
        );
        window.addEventListener("resize", progress, { passive: true });
        progress();
      }

      /* the rail only belongs beside the numbered sections - hide it as soon
         as the form starts entering view (so it never reaches the footer),
         and bring it back if you scroll back up past the form */
      var stop = document.getElementById("enquire"),
        rail = document.getElementById("outline");
      if (stop && rail) {
        var formTop = stop.offsetTop;
        var railCheck = function () {
          rail.classList.toggle("gone", window.scrollY >= formTop);
        };
        window.addEventListener("scroll", railCheck, { passive: true });
        window.addEventListener(
          "resize",
          function () {
            formTop = stop.offsetTop;
            railCheck();
          },
          { passive: true },
        );
        railCheck();
      }
    }

    /* one answer open at a time */
    var qs = $$(".qs details");
    if (qs.length) {
      qs.forEach(function (d) {
        d.addEventListener("toggle", function () {
          if (!d.open) return;
          qs.forEach(function (o) {
            if (o !== d) o.open = false;
          });
        });
      });
    }
  }

  /* ═══ 12 · ANALYTICS - the eight conversions ═══════════════════════════
     Handoff §20 names eight things worth counting. They are all here, and all
     of them are DOM facts rather than anything the page has to be told:

       join_the_exchange       the email opt-in sent
       guest_submission        guest consideration sent
       corporate_inquiry       corporate partnership sent
       university_inquiry      university engagement sent
       coaching_inquiry        professional coaching sent
       coach_training_inquiry  coach training sent
       general_inquiry         general contact sent            (bonus, free)
       interview_play          a visitor actually pressed play
       outbound_click          a link that leaves the site

     WHY THE FORM EVENTS KEY OFF A CLASS. CF7 form IDs change the moment
     someone rebuilds a form, and there are seven of them. The html_class on
     each shortcode is already the thing this build guarantees, and check.py
     asserts it, so the class is the stable handle and the ID is not.

     NO GA SNIPPET HERE. Install GA4 with a tag manager or the theme so consent
     tooling can gate it. This only pushes events, and does nothing at all until
     gtag or dataLayer exists - so it is safe to ship before analytics is set up
     and needs no edit afterwards.
     ═══════════════════════════════════════════════════════════════════════ */
  function analytics() {
    var FORM_EVENTS = {
      "form--join": "join_the_exchange",
      "form--guest": "guest_submission",
      "form--corporate": "corporate_inquiry",
      "form--university": "university_inquiry",
      "form--coaching": "coaching_inquiry",
      "form--training": "coach_training_inquiry",
      "form--general": "general_inquiry",
    };

    function send(name, params) {
      if (typeof window.gtag === "function") {
        window.gtag("event", name, params || {});
      } else if (Array.isArray(window.dataLayer)) {
        window.dataLayer.push(Object.assign({ event: name }, params || {}));
      }
      /* Neither present yet? Then analytics is not installed, and dropping the
         event is the correct outcome - not an error worth logging. */
    }
    TAE.track = send; /* for testing from the console */

    /* CF7 fires this on document after a successful send - not on submit, so a
       failed or spam-blocked submission is never counted as a conversion. */
    document.addEventListener("wpcf7mailsent", function (e) {
      var form =
        e.target && e.target.querySelector
          ? e.target.querySelector("form") || e.target
          : null;
      var cls =
        (form && form.className) || (e.target && e.target.className) || "";
      Object.keys(FORM_EVENTS).forEach(function (k) {
        if (cls.indexOf(k) !== -1) send(FORM_EVENTS[k], { form: k });
      });
    });

    /* A play is a real engagement signal; a page view of an interview is not.

       ON DOCUMENT, IN THE CAPTURE PHASE, and both halves of that matter.

       CAPTURE, because the .on guard is what stops a second click on a playing
       video counting twice - and §06 sets .on inside its own click handler. Bound
       per element, this listener registered after §06's (boot runs lazyVideo at
       §272, analytics at §278), so on every first click §06 had already set .on
       by the time this ran and the event was dropped. Not under-counted: never
       sent, on any video, since the day the guard was written. Capture runs
       before any handler on the target, so the flag is read before §06 writes it.

       DOCUMENT, because the archive appends tiles from admin-ajax after boot and
       a per-element loop never sees them. tae-archive.js deliberately does not
       track these itself; this listener already covers them. */
    document.addEventListener(
      "click",
      function (e) {
        var v = e.target.closest ? e.target.closest("[data-yt]") : null;
        if (!v || v.classList.contains("on")) return; /* already playing */
        /* A chapter jump clears .on so §06 will rebuild the frame; without this
           the rebuild would look exactly like a first play. §06b sets the flag
           immediately before it clicks, and this is the only reader. */
        if (v.dataset.rechapter) {
          delete v.dataset.rechapter;
          return;
        }
        send("interview_play", {
          video_id: v.dataset.yt,
          title: v.dataset.title || document.title,
        });
      },
      true,
    );

    /* Outbound, including the YouTube channel links in the header and footer. */
    document.addEventListener("click", function (e) {
      var a = e.target.closest && e.target.closest('a[href^="http"]');
      if (!a) return;
      var host;
      try {
        host = new URL(a.href).host;
      } catch (err) {
        return;
      }
      if (host === location.host) return;
      send("outbound_click", { url: a.href, host: host });
    });
  }

  /* ═══ 13 · GO ══════════════════════════════════════════════════════════
     Last statement in the file, deliberately. Every top-level binding above
     - including `var setMenu` in §03 - is now assigned before boot() can run,
     whether ready() fires synchronously (DOM already parsed) or defers to
     DOMContentLoaded. See the note above boot() for what breaks otherwise. */
  ready(boot);
})();
