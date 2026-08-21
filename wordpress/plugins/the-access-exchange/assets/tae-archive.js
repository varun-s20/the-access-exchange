/* ═══════════════════════════════════════════════════════════════════════════
   THE ACCESS EXCHANGE - ARCHIVE LOAD-MORE
   ───────────────────────────────────────────────────────────────────────────
   Why this exists at all.

   The category filter in global.css is pure CSS - :has() plus a sibling
   combinator - and it can only hide what is already in the DOM. Paired with a
   pager that loads twelve at a time, filtering to "Design" would show two
   results when seven exist, and nothing would tell the visitor they were
   looking at a partial answer.

   So a chip click refetches page one for that category server-side and replaces
   the grid. Load-more then pages within whatever category is active. The CSS
   rules stay in place and still fire first, which is what stops the grid
   flashing the wrong tiles while the request is in flight - and they are the
   whole filter when JavaScript does not run.

   This file does NOT depend on global.js. README-WORDPRESS.md §7b documents a
   fallback route where global.js is pasted into the footer widget with no
   script handle, and a declared dependency would silently drop this file there.
   ═══════════════════════════════════════════════════════════════════════════ */
(function () {
  "use strict";

  var cfg = window.TAE_ARCHIVE;
  if (!cfg || !window.fetch) return;

  function request(data, ok, fail) {
    var body = new URLSearchParams();
    body.set("action", "tae_load_interviews");
    /* No nonce is sent, and none is expected - inc/ajax.php has the reasoning.
       The short version: this is a public read, and a nonce printed into
       cacheable HTML goes stale inside the cache and silently kills this file. */
    Object.keys(data).forEach(function (k) {
      body.set(k, data[k]);
    });

    fetch(cfg.url, { method: "POST", credentials: "same-origin", body: body })
      .then(function (r) {
        return r.json();
      })
      .then(function (json) {
        if (json && json.success) {
          ok(json.data);
        } else {
          fail();
        }
      })
      .catch(fail);
  }

  /* Tiles arriving after boot have never been seen by global.js §05, whose
     IntersectionObserver only ever observed the nodes present at load. Marking
     them revealed is simpler and more reliable than re-observing something that
     is already inside the viewport by the time it is appended. */
  function reveal(root) {
    [].slice.call(root.querySelectorAll("[data-rv]")).forEach(function (el) {
      el.classList.add("in");
    });
  }

  function wire(list) {
    var view = list.getAttribute("data-tae-list"),
      scope = list.parentNode,
      sorts = scope.querySelector("[data-tae-sorts]"),
      more = scope.querySelector("[data-tae-more]"),
      busy = false;

    /* No search field. This wired one on [data-tae-search], which was the home
       wall's box - and the wall went in Phase 7, so nothing has carried the
       attribute since. The AJAX handler still accepts a search term; put the
       attribute on an input and restore ten lines here if the archive ever wants
       one of its own. */
    var state = {
      page: parseInt(list.getAttribute("data-tae-page"), 10) || 1,
      count: parseInt(list.getAttribute("data-tae-count"), 10) || 12,
      cat: list.getAttribute("data-tae-cat") || "",
      term: "",
    };

    function load(reset) {
      if (busy) return;
      busy = true;

      var next = reset ? 1 : state.page + 1;

      request(
        {
          view: view,
          page: next,
          count: state.count,
          category: state.cat,
          search: state.term,
        },
        function (data) {
          if (reset) {
            list.innerHTML = data.html;
          } else {
            list.insertAdjacentHTML("beforeend", data.html);
          }
          state.page = next;
          if (more) more.style.display = data.has_more ? "" : "none";
          reveal(list);
          busy = false;
        },
        function () {
          /* Leave what is on screen alone - a failed fetch should not empty a
             grid that was already correct - but SAY SO. This used to fail
             mute: the visitor pressed the button, nothing moved, and nothing
             told them to try again. A button that does nothing reads as a
             broken site, which is worse than the failure it is hiding. */
          if (more) {
            var label = more.getAttribute("data-label") || more.textContent;
            more.setAttribute("data-label", label);
            more.textContent = "Didn't load - tap to retry";
            setTimeout(function () {
              more.textContent = label;
            }, 4000);
          }
          busy = false;
        },
      );
    }

    if (sorts) {
      sorts.addEventListener("change", function (e) {
        var input = e.target;
        if (!input || "radio" !== input.type) return;
        state.cat = input.getAttribute("data-cat") || "";
        load(true);
      });
    }

    if (more) {
      more.addEventListener("click", function (e) {
        e.preventDefault();
        load(false);
      });
    }

  }

  /* Click-to-load YouTube for appended tiles.

     global.js §06 binds a listener per element at boot, so a tile that arrives
     later has none. This is a single delegated listener that mirrors it. Both
     guard on the .on class, and the element's own listener fires first while
     bubbling - so an original tile sets .on, this sees it, and returns without
     appending a second iframe. */
  function delegateVideo() {
    document.addEventListener("click", function (e) {
      var v = e.target.closest ? e.target.closest("[data-yt]") : null;
      if (!v || v.classList.contains("on")) return;

      /* No interview_play call here. global.js §12 now listens on document in
         the CAPTURE phase, which fires before any element's own handler and so
         covers tiles that arrived from admin-ajax as well as tiles that were in
         the page at boot. Tracking here too would count those twice. */

      var f = document.createElement("iframe");
      f.src =
        "https://www.youtube-nocookie.com/embed/" +
        v.dataset.yt +
        "?autoplay=1&rel=0&modestbranding=1";
      f.title = v.dataset.title || "Interview";
      f.loading = "lazy";
      f.allow = "accelerometer; autoplay; encrypted-media; picture-in-picture";
      f.setAttribute("allowfullscreen", "");
      v.appendChild(f);
      v.classList.add("on");
    });
  }

  function boot() {
    var lists = [].slice.call(document.querySelectorAll("[data-tae-list]"));
    if (!lists.length) return;
    lists.forEach(wire);
    delegateVideo();
  }

  if ("loading" !== document.readyState) {
    boot();
  } else {
    document.addEventListener("DOMContentLoaded", boot);
  }
})();
