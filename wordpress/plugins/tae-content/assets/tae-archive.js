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

  var DEBOUNCE = 110; // matches the insights search in global.js §09

  function request(data, ok, fail) {
    var body = new URLSearchParams();
    body.set("action", "tae_load_interviews");
    body.set("nonce", cfg.nonce);
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
      search = scope.querySelector("[data-tae-search]"),
      busy = false;

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
          // Leave what is on screen alone and let the visitor try again. A
          // failed fetch should not empty a grid that was already correct.
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

    if (search) {
      var timer = null;
      search.addEventListener("input", function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
          state.term = search.value.trim();
          load(true);
        }, DEBOUNCE);
      });
      search.addEventListener("keydown", function (e) {
        if ("Escape" === e.key && search.value) {
          search.value = "";
          state.term = "";
          load(true);
        }
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
