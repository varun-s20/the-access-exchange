/*
 * The Access Exchange — shared script (loads on all 6 pages).
 * WordPress-safe: everything lives inside one DOMContentLoaded listener,
 * nothing leaks to the global scope. Page-specific behavior feature-detects
 * its markup before attaching listeners.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ----------------------------------------------------------------------
     * Active nav link.
     * The header markup is byte-identical on every page, so the current page
     * is declared on <body data-page="..."> instead of hardcoded in the nav.
     * In WordPress this becomes an is_page() conditional in header.php.
     * -------------------------------------------------------------------- */
    var page = document.body.getAttribute('data-page');
    if (page) {
      var activeLink = document.querySelector('.nav-link[data-nav="' + page + '"]');
      if (activeLink) {
        activeLink.classList.add('is-active');
        activeLink.setAttribute('aria-current', 'page');
      }
    }

    /* ----------------------------------------------------------------------
     * Mobile nav toggle (off-canvas below 768px)
     * -------------------------------------------------------------------- */
    var navToggle = document.getElementById('nav-toggle');
    var navMenu = document.getElementById('nav-menu');
    if (navToggle && navMenu) {
      var setNav = function (open) {
        document.body.classList.toggle('nav-open', open);
        navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      };

      navToggle.addEventListener('click', function () {
        setNav(!document.body.classList.contains('nav-open'));
      });

      navMenu.addEventListener('click', function (event) {
        if (event.target.closest('a')) {
          setNav(false);
        }
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && document.body.classList.contains('nav-open')) {
          setNav(false);
          navToggle.focus();
        }
      });
    }

    /* ----------------------------------------------------------------------
     * Sticky header — shadow after ~50px of scroll
     * -------------------------------------------------------------------- */
    var header = document.getElementById('site-header');
    if (header) {
      var onScroll = function () {
        header.classList.toggle('is-scrolled', window.scrollY > 50);
      };
      onScroll();
      window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ----------------------------------------------------------------------
     * Client-side form validation + fake submit.
     * Prototype only — a WordPress form plugin replaces this wholesale.
     * -------------------------------------------------------------------- */
    var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    var showError = function (field, message) {
      var wrap = field.closest('.form-field');
      if (!wrap) return;
      wrap.classList.add('has-error');
      field.setAttribute('aria-invalid', 'true');
      var error = wrap.querySelector('.form-error');
      if (!error) {
        error = document.createElement('p');
        error.className = 'form-error';
        error.id = field.id + '-error';
        wrap.appendChild(error);
      }
      field.setAttribute('aria-describedby', error.id);
      error.textContent = message;
    };

    var clearError = function (field) {
      var wrap = field.closest('.form-field');
      if (!wrap) return;
      wrap.classList.remove('has-error');
      field.removeAttribute('aria-invalid');
      field.removeAttribute('aria-describedby');
      var error = wrap.querySelector('.form-error');
      if (error) error.remove();
    };

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();

        var firstInvalid = null;
        form.querySelectorAll('[required]').forEach(function (field) {
          clearError(field);
          var value = field.value.trim();
          if (!value) {
            showError(field, 'This field is required.');
          } else if (field.type === 'email' && !EMAIL_RE.test(value)) {
            showError(field, 'Please enter a valid email address.');
          } else {
            return;
          }
          if (!firstInvalid) firstInvalid = field;
        });

        if (firstInvalid) {
          firstInvalid.focus();
          return;
        }

        // Honeypot filled → almost certainly a bot; drop silently.
        var honeypot = form.querySelector('[name="hp-field"]');
        if (honeypot && honeypot.value) return;

        var success = document.createElement('div');
        success.className = 'form-success';
        success.setAttribute('role', 'status');
        success.setAttribute('tabindex', '-1');
        success.innerHTML =
          '<h3>Thanks — we’ll be in touch.</h3>' +
          '<p>Your message has been received. We usually reply within two business days.</p>';
        form.replaceWith(success);
        success.focus();
      });

      // Clear a field's error as soon as the user starts fixing it.
      form.addEventListener('input', function (event) {
        if (event.target.matches('[required]')) {
          clearError(event.target);
        }
      });
    });

    /* ----------------------------------------------------------------------
     * Card-grid filtering (Interviews & Insights page only)
     * -------------------------------------------------------------------- */
    var filterBar = document.querySelector('[data-filter-bar]');
    var filterGrid = document.querySelector('[data-filter-grid]');
    if (filterBar && filterGrid) {
      var buttons = filterBar.querySelectorAll('[data-filter]');
      var cards = filterGrid.querySelectorAll('.card[data-category]');

      buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var filter = btn.getAttribute('data-filter');
          buttons.forEach(function (b) {
            b.classList.toggle('is-active', b === btn);
            b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
          });
          cards.forEach(function (card) {
            card.hidden = filter !== 'all' && card.getAttribute('data-category') !== filter;
          });
        });
      });
    }

    /* ----------------------------------------------------------------------
     * Smooth scroll for on-page anchor links
     * -------------------------------------------------------------------- */
    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
      link.addEventListener('click', function (event) {
        var id = link.getAttribute('href');
        if (id.length < 2) return;
        var target = document.querySelector(id);
        if (!target) return;
        event.preventDefault();
        target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth' });
        target.setAttribute('tabindex', '-1');
        target.focus({ preventScroll: true });
      });
    });
  });
})();
