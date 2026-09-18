/**
 * Hostdigi child theme - homepage interactions.
 *
 * Loaded site-wide by the ClientAreaFooterOutput hook, but every handler is
 * guarded so it no-ops on pages that don't contain the homepage markup.
 */
(function () {
  'use strict';

  var root = document.querySelector('.hd-root');
  if (!root) return;

  /* ---------------------------------------------------------- colour theme */
  try {
    var saved = localStorage.getItem('hostdigi-theme');
    if (saved) root.setAttribute('data-theme', saved);
  } catch (e) { /* private mode - keep the default */ }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-hd-theme-toggle]');
    if (!btn) return;
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('hostdigi-theme', next); } catch (err) {}
  });

  /* ------------------------------------------------------------ plan tabs */
  var tabs = root.querySelectorAll('[data-hd-type]');
  Array.prototype.forEach.call(tabs, function (tab) {
    tab.addEventListener('click', function () {
      var type = tab.getAttribute('data-hd-type');
      Array.prototype.forEach.call(tabs, function (t) {
        t.setAttribute('aria-selected', String(t === tab));
      });
      Array.prototype.forEach.call(root.querySelectorAll('[data-hd-panel]'), function (panel) {
        panel.hidden = panel.getAttribute('data-hd-panel') !== type;
      });
    });
  });

  /* ------------------------------------------------------- billing cycle */
  var cycle = document.getElementById('hdCycle');
  if (cycle) {
    cycle.addEventListener('click', function () {
      var annual = cycle.getAttribute('aria-checked') !== 'true';
      cycle.setAttribute('aria-checked', String(annual));

      Array.prototype.forEach.call(root.querySelectorAll('[data-hd-monthly]'), function (el) {
        var monthly = el.getAttribute('data-hd-monthly');
        var perMonth = el.getAttribute('data-hd-annual');
        // Fall back to whichever cycle the product actually has priced.
        var next = annual ? (perMonth || monthly) : (monthly || perMonth);
        if (next) el.textContent = next;
      });

      Array.prototype.forEach.call(root.querySelectorAll('[data-hd-note]'), function (note) {
        var isAnnualNote = note.getAttribute('data-hd-note') === 'annual';
        // Never reveal an annual note for a product with no annual price.
        if (isAnnualNote && !note.textContent.trim()) { note.hidden = true; return; }
        note.hidden = isAnnualNote !== annual;
      });
    });
  }

  /* --------------------------------------------------------------- reveal */
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.style.opacity = 1;
        entry.target.style.transform = 'none';
        io.unobserve(entry.target);
      });
    }, { threshold: 0.12 });

    Array.prototype.forEach.call(root.querySelectorAll('.hd-section-head, .hd-card, .hd-plan'), function (el) {
      el.style.opacity = 0;
      el.style.transform = 'translateY(16px)';
      el.style.transition = 'opacity .5s ease, transform .5s ease';
      io.observe(el);
    });
  }
})();
