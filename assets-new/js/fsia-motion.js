/* FSIA form pages: scroll reveal, reading progress, hero sparkles and a light tilt on the gold card.
   Only adds classes and decorative elements; it never touches the form fields' values or the submit logic.
   Does nothing for visitors who prefer reduced motion. */
(function () {
  'use strict';
  if (!window.matchMedia || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (!('IntersectionObserver' in window)) return;

  function ready(fn) { if (document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }

  ready(function () {
    var vh = window.innerHeight;

    /* Reveal blocks as they scroll in. Anything already on screen stays visible from the start. */
    var targets = [];
    function add(sel, stagger) {
      Array.prototype.forEach.call(document.querySelectorAll(sel), function (el, i) {
        if (el.closest('.fm-reveal') || el.getBoundingClientRect().top < vh * 0.92) return;
        el.classList.add('fm-reveal');
        if (stagger) el.style.setProperty('--fm-delay', Math.min(i % 6, 5) * 0.07 + 's');
        targets.push(el);
      });
    }
    add('.form-section .info-box, .form-section h2, .fsia-s3d', false);
    add('#registrationForm > *', true);
    add('.fsia-crit', true);
    add('section h2, footer .fsia-ft > *, footer > div > *', true);
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('fm-in'); io.unobserve(e.target); } });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.05 });
    targets.forEach(function (el) { io.observe(el); });
    // The last lines of a page can never rise past the trigger line, so reveal whatever is left at the bottom.
    function revealRest() { targets.forEach(function (el) { el.classList.add('fm-in'); io.unobserve(el); }); }

    /* Thin gold bar at the top showing how far down the page the visitor is. */
    var bar = document.createElement('div');
    bar.className = 'fm-progress'; bar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(bar);
    var ticking = false;
    function progress() {
      var max = document.documentElement.scrollHeight - window.innerHeight;
      bar.style.setProperty('--fm-p', max > 0 ? Math.min(1, window.scrollY / max).toFixed(4) : 0);
      if (window.scrollY >= max - 4) revealRest();
      ticking = false;
    }
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(progress); } }, { passive: true });
    progress();

    /* Sparkles and soft glows behind the hero. */
    var hero = document.querySelector('.form-section');
    if (hero) {
      hero.classList.add('fm-hero');
      var box = document.createElement('div');
      box.className = 'fm-sparkles'; box.setAttribute('aria-hidden', 'true');
      for (var i = 0; i < 18; i++) {
        var s = document.createElement('i');
        s.style.setProperty('--x', (Math.random() * 100).toFixed(1) + '%');
        s.style.setProperty('--y', (Math.random() * 90).toFixed(1) + '%');
        s.style.setProperty('--s', (4 + Math.random() * 6).toFixed(1) + 'px');
        s.style.setProperty('--d', (3 + Math.random() * 4).toFixed(2) + 's');
        s.style.setProperty('--w', (Math.random() * 4).toFixed(2) + 's');
        box.appendChild(s);
      }
      ['a', 'b'].forEach(function (k) { var g = document.createElement('div'); g.className = 'fm-glow ' + k; g.setAttribute('aria-hidden', 'true'); box.appendChild(g); });
      hero.insertBefore(box, hero.firstChild);
      var h1 = hero.querySelector('h1');
      if (h1) h1.classList.add('fm-title');
    }

    /* The gold intro card leans a little toward the pointer (desktop only). */
    var card = document.querySelector('.fsia-s3d');
    if (card && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
      card.classList.add('fm-tilt');
      card.addEventListener('pointermove', function (e) {
        var r = card.getBoundingClientRect();
        card.style.setProperty('--fm-ry', (((e.clientX - r.left) / r.width - 0.5) * 5).toFixed(2) + 'deg');
        card.style.setProperty('--fm-rx', ((0.5 - (e.clientY - r.top) / r.height) * 4).toFixed(2) + 'deg');
      });
      card.addEventListener('pointerleave', function () { card.style.setProperty('--fm-rx', '0deg'); card.style.setProperty('--fm-ry', '0deg'); });
    }
  });
})();
